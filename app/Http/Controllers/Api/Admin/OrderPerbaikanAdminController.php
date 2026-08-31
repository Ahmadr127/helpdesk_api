<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\OrderPerbaikan\UpdateStatusRequest;
use App\Http\Resources\Api\OrderPerbaikanResource;
use App\Models\OrderPerbaikan;
use App\Services\Api\OrderPerbaikanService;
use Illuminate\Http\Request;

class OrderPerbaikanAdminController extends BaseApiController
{
    public function __construct(protected OrderPerbaikanService $service){}

    public function index(Request $request)
    {
        $filters = $request->only(['search','date_from','date_to','status','prioritas','location_id']);
        // support alternative date_from naming
        if ($request->filled('start_date')) $filters['date_from'] = $request->start_date;
        if ($request->filled('end_date')) $filters['date_to'] = $request->end_date;
        $paginator = $this->service->listForAdmin($filters, (int)$request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'Daftar order perbaikan (administrasi)',
            'data'=>OrderPerbaikanResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ],
            'statistics'=>$this->service->statistics()
        ]);
    }

    public function show(OrderPerbaikan $orderPerbaikan)
    {
        $orderPerbaikan->load(['creator','history.creator','location']);
        return $this->success(new OrderPerbaikanResource($orderPerbaikan), 'Detail order');
    }

    public function updateStatus(UpdateStatusRequest $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            $updated = $this->service->updateStatus($request->user(), $orderPerbaikan, $request->validated());
            return $this->success(new OrderPerbaikanResource($updated), 'Status berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function confirm(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            $updated = $this->service->confirm($request->user(), $orderPerbaikan);
            return $this->success(new OrderPerbaikanResource($updated), 'Order dikonfirmasi');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function reject(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            $updated = $this->service->reject($request->user(), $orderPerbaikan);
            return $this->success(new OrderPerbaikanResource($updated), 'Order ditolak');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function start(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            $updated = $this->service->start($request->user(), $orderPerbaikan);
            return $this->success(new OrderPerbaikanResource($updated), 'Order dimulai');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function inProgress(Request $request)
    {
        $filters = array_merge($request->only(['search','prioritas','location_id']), ['status'=>'in_progress']);
        $paginator = $this->service->listForAdmin($filters, (int)$request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'In progress orders',
            'data'=>OrderPerbaikanResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function confirmed(Request $request)
    {
        $filters = array_merge($request->only(['search','date_from','date_to']), ['status'=>'confirmed']);
        $paginator = $this->service->listForAdmin($filters, (int)$request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'Confirmed orders',
            'data'=>OrderPerbaikanResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function rejected(Request $request)
    {
        $filters = array_merge($request->only(['search','date_from','date_to']), ['status'=>'rejected']);
        $paginator = $this->service->listForAdmin($filters, (int)$request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'Rejected orders',
            'data'=>OrderPerbaikanResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }

    public function statistics()
    {
        return $this->success($this->service->statistics(), 'Statistics');
    }
}
