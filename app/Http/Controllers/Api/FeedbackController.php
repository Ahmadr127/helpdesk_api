<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Feedback\ReplyFeedbackRequest;
use App\Http\Requests\Api\Feedback\StoreFeedbackRequest;
use App\Http\Resources\Api\FeedbackResource;
use App\Models\Feedback;
use App\Services\Api\FeedbackService;
use Illuminate\Http\Request;

class FeedbackController extends BaseApiController
{
    public function __construct(protected FeedbackService $service){}

    public function index(Request $request)
    {
        // if admin, show all; if user show own
        $user = $request->user();
        $isAdmin = $user->role === 'admin' && strtolower($user->position)==='it';
        if ($isAdmin) {
            $filters = $request->only(['search']);
            $paginator = $this->service->list($filters, (int)$request->get('per_page',15));
        } else {
            $paginator = $this->service->listForUser($user, (int)$request->get('per_page',15));
        }
        return response()->json([
            'success'=>true,
            'message'=>'Daftar feedback',
            'data'=> FeedbackResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function store(StoreFeedbackRequest $request)
    {
        $feedback = $this->service->create($request->user(), $request->validated());
        return $this->success(new FeedbackResource($feedback->load('user')), 'Feedback berhasil dikirim', 201);
    }

    public function show(Feedback $feedback)
    {
        // user can only see own unless admin
        $user = auth()->user();
        $isAdmin = $user->role==='admin' && strtolower($user->position)==='it';
        if (!$isAdmin && $feedback->user_id !== $user->id) {
            return $this->error('Unauthorized',403);
        }
        return $this->success(new FeedbackResource($feedback->load('user')), 'Detail feedback');
    }

    public function reply(ReplyFeedbackRequest $request, Feedback $feedback)
    {
        // only admin
        $user = $request->user();
        if (!($user->role==='admin' && strtolower($user->position)==='it')) {
            return $this->error('Unauthorized - admin only',403);
        }
        $updated = $this->service->reply($feedback, $request->validated()['admin_reply']);
        return $this->success(new FeedbackResource($updated), 'Balasan berhasil dikirim');
    }

    public function destroy(Request $request, Feedback $feedback)
    {
        $user = $request->user();
        if (!($user->role==='admin' && strtolower($user->position)==='it')) {
            return $this->error('Unauthorized',403);
        }
        $this->service->delete($feedback);
        return $this->success(null, 'Feedback berhasil dihapus');
    }
}
