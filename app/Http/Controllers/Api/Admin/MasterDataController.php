<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\MasterData\StoreMasterDataRequest;
use App\Http\Requests\Api\MasterData\UpdateMasterDataRequest;
use App\Services\Api\MasterDataService;
use Illuminate\Http\Request;

class MasterDataController extends BaseApiController
{
    public function __construct(protected MasterDataService $service){}

    public function index(Request $request, string $type)
    {
        try {
            $filters = $request->only(['search','status','from_date','to_date']);
            $perPage = (int)$request->get('per_page',15);
            $paginator = $this->service->list($type, $filters, $perPage);
            return response()->json([
                'success'=>true,
                'message'=>"Daftar {$type}",
                'data'=>$paginator->items(),
                'meta'=>[
                    'current_page'=>$paginator->currentPage(),
                    'last_page'=>$paginator->lastPage(),
                    'per_page'=>$paginator->perPage(),
                    'total'=>$paginator->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(string $type, int $id)
    {
        try {
            $data = $this->service->find($type, $id);
            return $this->success($data, "Detail {$type}");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 404);
        }
    }

    public function store(StoreMasterDataRequest $request, string $type)
    {
        try {
            $data = $this->service->create($type, $request->validated());
            return $this->success($data, "{$type} berhasil dibuat", 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(UpdateMasterDataRequest $request, string $type, int $id)
    {
        try {
            $data = $this->service->update($type, $id, $request->validated());
            return $this->success($data, "{$type} berhasil diperbarui");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function destroy(string $type, int $id)
    {
        try {
            $this->service->delete($type, $id);
            return $this->success(null, "{$type} berhasil dihapus");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function bulkAction(Request $request, string $type)
    {
        $request->validate([
            'action'=>'required|in:activate,deactivate,delete',
            'selected'=>'required|array',
            'selected.*'=>'required|integer'
        ]);
        try {
            $count = $this->service->bulkAction($type, $request->action, $request->selected);
            return $this->success(['affected'=>$count], "Bulk {$request->action} berhasil untuk {$count} data");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    // Public lookup for user (no admin) - read only
    public function lookup(Request $request, string $type)
    {
        try {
            $filters = $request->only(['search','status']);
            $filters['status'] = $filters['status'] ?? 1; // only active
            $perPage = (int)$request->get('per_page', 50);
            $paginator = $this->service->list($type, $filters, $perPage);
            return response()->json([
                'success'=>true,
                'message'=>"Lookup {$type}",
                'data'=>$paginator->items(),
                'meta'=>[
                    'current_page'=>$paginator->currentPage(),
                    'last_page'=>$paginator->lastPage(),
                    'per_page'=>$paginator->perPage(),
                    'total'=>$paginator->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
