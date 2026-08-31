<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\OrderPerbaikan\StoreOrderPerbaikanRequest;
use App\Http\Requests\Api\OrderPerbaikan\UpdateOrderPerbaikanRequest;
use App\Http\Resources\Api\OrderPerbaikanResource;
use App\Models\OrderPerbaikan;
use App\Services\Api\OrderPerbaikanService;
use Illuminate\Http\Request;

class OrderPerbaikanController extends BaseApiController
{
    public function __construct(protected OrderPerbaikanService $orderService){}

    public function index(Request $request)
    {
        $filters = $request->only(['status','search','start_date','end_date','prioritas']);
        // support 'all' default
        if (!isset($filters['status'])) $filters['status'] = $request->get('status','all');
        $perPage = (int)$request->get('per_page',15);
        $paginator = $this->orderService->listForUser($request->user(), $filters, $perPage);
        return response()->json([
            'success'=>true,
            'message'=>'Daftar order perbaikan',
            'data'=> OrderPerbaikanResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ],
            'stats'=> $this->orderService->userStatistics($request->user())
        ]);
    }

    public function store(StoreOrderPerbaikanRequest $request)
    {
        try {
            $order = $this->orderService->create($request->user(), $request->validated(), $request->file('foto'));
            return $this->success(new OrderPerbaikanResource($order), 'Order perbaikan berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        if ($orderPerbaikan->created_by !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        $orderPerbaikan->load(['creator','history.creator','location']);
        return $this->success(new OrderPerbaikanResource($orderPerbaikan), 'Detail order');
    }

    public function update(UpdateOrderPerbaikanRequest $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            $updated = $this->orderService->update($request->user(), $orderPerbaikan, $request->validated(), $request->file('foto'));
            return $this->success(new OrderPerbaikanResource($updated), 'Order berhasil diperbarui');
        } catch (\Exception $e) {
            $code = $e->getCode() >=400 && $e->getCode()<600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function destroy(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            $this->orderService->delete($request->user(), $orderPerbaikan);
            return $this->success(null, 'Order berhasil dihapus');
        } catch (\Exception $e) {
            $code = $e->getCode() >=400 && $e->getCode()<600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function konfirmasi(Request $request)
    {
        $filters = ['status'=>'confirmed'];
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date'] = $request->end_date;
        }
        $paginator = $this->orderService->listForUser($request->user(), $filters, (int)$request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'Order confirmed',
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
        $filters = ['status'=>'rejected'];
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date'] = $request->end_date;
        }
        $paginator = $this->orderService->listForUser($request->user(), $filters, (int)$request->get('per_page',15));
        return response()->json([
            'success'=>true,
            'message'=>'Order rejected',
            'data'=>OrderPerbaikanResource::collection($paginator->items()),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
            ]
        ]);
    }
}
