<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\Api\UserService;
use Illuminate\Http\Request;

class UserManagementController extends BaseApiController
{
    public function __construct(protected UserService $userService){}

    public function index(Request $request)
    {
        $filters = $request->only(['search','role','status']);
        $perPage = (int)$request->get('per_page',15);
        $paginator = $this->userService->list($filters, $perPage);
        return response()->json([
            'success'=>true,
            'message'=>'Daftar user',
            'data'=> UserResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());
        return $this->success(new UserResource($user), 'User berhasil dibuat', 201);
    }

    public function show(User $user)
    {
        return $this->success(new UserResource($user), 'Detail user');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $updated = $this->userService->update($user, $request->validated());
        return $this->success(new UserResource($updated), 'User berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        $this->userService->delete($user);
        return $this->success(null, 'User berhasil dihapus');
    }
}
