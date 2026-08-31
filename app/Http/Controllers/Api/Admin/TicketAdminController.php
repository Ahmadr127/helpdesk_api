<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Ticket\AdminRespondRequest;
use App\Http\Requests\Api\Ticket\AdminUpdateRequest;
use App\Http\Resources\Api\TicketResource;
use App\Models\Ticket;
use App\Services\Api\TicketService;
use Illuminate\Http\Request;

class TicketAdminController extends BaseApiController
{
    public function __construct(protected TicketService $ticketService){}

    public function index(Request $request)
    {
        $perPage = (int)$request->get('per_page', 15);
        $filters = $request->only(['search','status','start_date','end_date','priority']);
        $paginator = $this->ticketService->listForAdmin($filters, $perPage);
        return response()->json([
            'success'=>true,
            'message'=>'Daftar ticket (admin)',
            'data'=> TicketResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ],
            'stats'=>[
                'totalTickets'=> \App\Models\Ticket::count(),
                'openTickets'=> \App\Models\Ticket::where('status','open')->count(),
                'inProgressTickets'=> \App\Models\Ticket::where('status','in_progress')->count(),
                'closedTickets'=> \App\Models\Ticket::whereIn('status',['closed','confirmed'])->count(),
            ]
        ]);
    }

    public function show(Ticket $ticket)
    {
        return $this->success(new TicketResource($ticket->load(['user','photos'])), 'Detail ticket');
    }

    public function respond(AdminRespondRequest $request, Ticket $ticket)
    {
        $validated = $request->validated();
        $updated = $this->ticketService->adminRespond($request->user(), $ticket, $validated['notes'], $validated['status'], $request->file('photo'));
        return $this->success(new TicketResource($updated), 'Response berhasil ditambahkan');
    }

    public function update(AdminUpdateRequest $request, Ticket $ticket)
    {
        $validated = $request->validated();
        $updated = $this->ticketService->adminUpdate($request->user(), $ticket, $validated['notes'], $validated['status'] ?? null, $validated['action'] ?? null, $request->file('photo'));
        return $this->success(new TicketResource($updated), $validated['action']==='reply' ? 'Reply terkirim' : 'Ticket diperbarui');
    }

    public function history(Request $request)
    {
        $query = Ticket::where('status','confirmed')->where('user_confirmation',true);
        if ($request->filled('date_from')) $query->whereDate('created_at','>=',$request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at','<=',$request->date_to);
        $paginator = $query->orderBy('user_confirmed_at','desc')->paginate($request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'History ticket confirmed',
            'data'=> TicketResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function historyShow(Ticket $ticket)
    {
        if ($ticket->status !== 'confirmed') {
            return $this->error('Ticket belum dikonfirmasi', 422);
        }
        return $this->success(new TicketResource($ticket->load(['user','photos'])), 'Detail history');
    }

    public function all(Request $request)
    {
        return $this->index($request);
    }

    public function open(Request $request)
    {
        $filters = array_merge($request->only(['search','start_date','end_date']), ['status'=>'open']);
        if ($request->filled('month')) {
            $filters['search'] = $filters['search'] ?? '';
        }
        $paginator = $this->ticketService->listForAdmin($filters, $request->get('per_page',15));
        // apply month filter manual if needed
        return response()->json([
            'success'=>true,
            'message'=>'Open tickets',
            'data'=>TicketResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function inProgress(Request $request)
    {
        $filters = array_merge($request->only(['search','start_date','end_date']), ['status'=>'in_progress']);
        $paginator = $this->ticketService->listForAdmin($filters, $request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'In progress tickets',
            'data'=>TicketResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function closed(Request $request)
    {
        $filters = array_merge($request->only(['search','start_date','end_date']), ['status'=>'closed']);
        $paginator = $this->ticketService->listForAdmin($filters, $request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'Closed tickets (pending confirmation)',
            'data'=>TicketResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }
}
