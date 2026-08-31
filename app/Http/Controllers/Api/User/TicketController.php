<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Ticket\ConfirmTicketRequest;
use App\Http\Requests\Api\Ticket\ReplyTicketRequest;
use App\Http\Requests\Api\Ticket\StoreTicketRequest;
use App\Http\Requests\Api\Ticket\UpdateTicketRequest;
use App\Http\Resources\Api\TicketResource;
use App\Models\Ticket;
use App\Services\Api\TicketService;
use Illuminate\Http\Request;

class TicketController extends BaseApiController
{
    public function __construct(protected TicketService $ticketService){}

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $filters = $request->only(['status','priority','search','start_date','end_date']);
        $paginator = $this->ticketService->listForUser($request->user(), $filters, $perPage);
        return response()->json([
            'success' => true,
            'message' => 'Daftar ticket',
            'data' => TicketResource::collection($paginator->items()),
            'meta' => [
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        try {
            $ticket = $this->ticketService->create($request->user(), $request->validated(), $request->file('photo'));
            return $this->success(new TicketResource($ticket->load('photos','user')), 'Ticket berhasil dibuat', 201);
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403,422]) ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function show(Request $request, Ticket $ticket)
    {
        if ($ticket->user_id !== $request->user()->id) {
            // admin can also view? For user endpoint only owner
            return $this->error('Unauthorized', 403);
        }
        return $this->success(new TicketResource($ticket->load(['user','photos'])), 'Detail ticket');
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        try {
            $updated = $this->ticketService->updateUserTicket($request->user(), $ticket, $request->validated(), $request->file('photo'));
            return $this->success(new TicketResource($updated), 'Ticket berhasil diperbarui');
        } catch (\Exception $e) {
            $code = $e->getCode() >=400 && $e->getCode()<600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        try {
            $this->ticketService->deleteUserTicket($request->user(), $ticket);
            return $this->success(null, 'Ticket berhasil dihapus');
        } catch (\Exception $e) {
            $code = $e->getCode() >=400 && $e->getCode()<600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function reply(ReplyTicketRequest $request, Ticket $ticket)
    {
        try {
            $updated = $this->ticketService->reply($request->user(), $ticket, $request->validated()['message'], $request->file('photo'));
            return $this->success(new TicketResource($updated), 'Reply berhasil dikirim');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function confirm(ConfirmTicketRequest $request, Ticket $ticket)
    {
        try {
            $validated = $request->validated();
            $updated = $this->ticketService->confirm($request->user(), $ticket, $validated['confirmation_notes'], $validated['action'], $request->file('photo'));
            return $this->success(new TicketResource($updated), $validated['action']==='confirm' ? 'Ticket dikonfirmasi selesai' : 'Ticket dikembalikan ke in_progress');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function filterByStatus(Request $request, $status)
    {
        $filters = $request->only(['priority','search','start_date','end_date','ticket_number','category','department']);
        // map legacy filter status param
        $filters['status'] = $status;
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->ticketService->listForUser($request->user(), $filters, $perPage);
        return response()->json([
            'success'=>true,
            'message'=>"Filter status {$status}",
            'data'=> TicketResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
                'status'=>$status,
            ]
        ]);
    }
}
