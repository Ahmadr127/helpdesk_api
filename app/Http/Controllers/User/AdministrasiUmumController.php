<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderPerbaikan\UserConfirmOrderRequest;
use App\Models\Building;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\UnitProses;
use App\Services\Api\OrderPerbaikanService;
use App\Services\DepartmentService;
use Illuminate\Http\Request;

class AdministrasiUmumController extends Controller
{
    public function orderBarang(Request $request, OrderPerbaikanService $service)
    {
        $filters = $request->only(['search', 'kategori_order', 'start_date', 'end_date']);

        // Kartu order in progress (maks 6)
        $inProgressOrders = $service->listForUser(
            auth()->user(),
            array_merge($filters, ['status' => OrderPerbaikan::STATUS_IN_PROGRESS]),
            6
        );

        // Tabel order open
        $orders = $service->listForUser(
            auth()->user(),
            array_merge($filters, ['status' => OrderPerbaikan::STATUS_OPEN]),
            10
        );

        $locations = Location::orderBy('name', 'asc')->get();
        $unitProses = UnitProses::where('status', 1)
            ->where('code', '!=', 'SIRS')
            ->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
                'inProgressHtml' => view('user.administrasi-umum.order-perbaikan._in_progress_cards', compact('inProgressOrders'))->render(),
            ]);
        }

        return view('user.administrasi-umum.order-barang', compact('orders', 'inProgressOrders', 'locations', 'unitProses', 'kategoriOrders'));
    }

    public function orderBarangKonfirmasi(Request $request, OrderPerbaikanService $service)
    {
        $filters = array_merge(
            $request->only(['start_date', 'end_date']),
            ['status' => OrderPerbaikan::STATUS_CONFIRMED]
        );

        $orders = $service->listForUser(auth()->user(), $filters, 10);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
            ]);
        }

        return view('user.administrasi-umum.order-barang-konfirmasi', compact('orders'));
    }

    public function orderBarangTutup(Request $request, OrderPerbaikanService $service)
    {
        $filters = array_merge(
            $request->only(['start_date', 'end_date']),
            ['status' => OrderPerbaikan::STATUS_TUTUP]
        );

        $orders = $service->listForUser(auth()->user(), $filters, 10);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
            ]);
        }

        return view('user.administrasi-umum.order-barang-tutup', compact('orders'));
    }

    public function orderBarangReject(Request $request, OrderPerbaikanService $service)
    {
        $filters = array_merge(
            $request->only(['start_date', 'end_date']),
            ['status' => OrderPerbaikan::STATUS_REJECTED]
        );

        $orders = $service->listForUser(auth()->user(), $filters, 10);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
            ]);
        }

        return view('user.administrasi-umum.order-barang-reject', compact('orders'));
    }

    public function indexOrderPerbaikan(Request $request, OrderPerbaikanService $service)
    {
        $status = $request->status ?? 'all';

        $filters = $request->only(['search', 'kategori_order', 'start_date', 'end_date']);
        $filters['status'] = $status;

        $orders = $service->listForUser(auth()->user(), $filters, 10);
        $stats = $service->userStatistics(auth()->user());

        $locations = Location::all();
        $unitProses = UnitProses::where('status', 1)
            ->where('code', '!=', 'SIRS')
            ->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
            ]);
        }

        return view('user.administrasi-umum.order-perbaikan.index', compact('orders', 'locations', 'unitProses', 'status', 'stats', 'kategoriOrders'));
    }

    public function createOrderPerbaikan(OrderPerbaikanService $service, DepartmentService $departments)
    {
        try {
            $currentDate = now();
            $nomor = $service->nextNumber();

            $locations = Location::orderBy('name', 'asc')->get();
            $buildings = Building::where('status', 1)->orderBy('name', 'asc')->get();
            $unitProses = UnitProses::where('status', 1)
                ->where('code', '!=', 'SIRS')
                ->get();
            $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();

            // Get user data
            $user = auth()->user();

            // Resolve departemen user
            $department = $departments->resolveForUser($user);
            $departmentId = $department?->id;
            $departmentLocation = $department?->location;
            $departmentBuilding = $department?->building;

            // Unit pengaju otomatis dari departemen user
            $unitPengajuCode = $department?->code ?? $user->department ?? 'GENERAL';
            $unitPengajuName = $department?->name ?? Department::where('code', $unitPengajuCode)->value('name') ?? $unitPengajuCode;

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'nomor' => $nomor,
                        'tanggal' => $currentDate->format('Y-m-d H:i:s'),
                        'locations' => $locations,
                        'unitPengajuCode' => $unitPengajuCode,
                        'unitPengajuName' => $unitPengajuName,
                        'departmentId' => $departmentId,
                        'defaultLocation' => $departmentLocation,
                        'defaultBuilding' => $departmentBuilding,
                        'kategoriOrders' => $kategoriOrders,
                        'user' => [
                            'nip' => $user->nip,
                            'name' => $user->name,
                        ],
                    ],
                ]);
            }

            return view('user.administrasi-umum.order-perbaikan.create',
                compact('nomor', 'locations', 'buildings', 'unitProses', 'user', 'unitPengajuCode', 'unitPengajuName', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }

    public function storeOrderPerbaikan(\App\Http\Requests\Api\OrderPerbaikan\StoreOrderPerbaikanRequest $request, OrderPerbaikanService $service)
    {
        $validated = $request->validated();

        try {
            $orderPerbaikan = $service->create(auth()->user(), $validated, $request->file('foto'), 'Web');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order perbaikan berhasil dibuat dengan nomor: '.$orderPerbaikan->nomor,
                    'data' => $orderPerbaikan,
                ]);
            }

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Order perbaikan berhasil dibuat dengan nomor: '.$orderPerbaikan->nomor);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat membuat order: '.$e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat membuat order: '.$e->getMessage())
                ->withInput();
        }
    }

    public function showOrderPerbaikan(OrderPerbaikan $orderPerbaikan)
    {
        // Load the relationships we need
        $order = $orderPerbaikan->load(['history.creator', 'location', 'creator']);

        // Check if the current user is authorized to view this order
        $canViewAll = auth()->user()->isAdminUmum() || auth()->user()->isAdminIT();
        if (! $canViewAll && $order->created_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('user.administrasi-umum.order-perbaikan.show', compact('order'));
    }

    public function editOrderPerbaikan(OrderPerbaikan $orderPerbaikan)
    {
        // Check if the current user is authorized to edit this order
        if ($orderPerbaikan->created_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if the order is still editable (open status)
        if ($orderPerbaikan->status !== 'open') {
            return redirect()->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('error', 'Hanya order dengan status open yang dapat diedit.');
        }

        $locations = Location::orderBy('name', 'asc')->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();

        $department = $this->resolveDepartment(auth()->user());
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;

        return view('user.administrasi-umum.order-perbaikan.edit', compact('orderPerbaikan', 'locations', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    protected function resolveDepartment($user): ?Department
    {
        $department = null;
        if (! empty($user->department_id)) {
            $department = Department::with(['location', 'building'])->find($user->department_id);
        }
        if (! $department && $user->department) {
            $deptValue = $user->department;
            $department = Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        return $department;
    }

    public function updateOrderPerbaikan(\App\Http\Requests\Api\OrderPerbaikan\UpdateOrderPerbaikanRequest $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        $validated = $request->validated();

        try {
            $order = $service->update(auth()->user(), $orderPerbaikan, $validated, $request->file('foto'), 'Web');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order perbaikan berhasil diperbarui',
                    'data' => $order,
                ]);
            }

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $order)
                ->with('success', 'Order perbaikan berhasil diperbarui');

        } catch (\Exception $e) {
            $code = $e->getCode() === 403 ? 403 : ($e->getCode() === 422 ? 422 : 500);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $code);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function deleteOrderPerbaikan(OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        try {
            $service->delete(auth()->user(), $orderPerbaikan);

            return response()->json([
                'success' => true,
                'message' => 'Order perbaikan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() === 403 ? 403 : ($e->getCode() === 422 ? 422 : 500);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    public function confirmOrderPerbaikan(Request $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        $validated = $request->validate([
            'konfirmasi' => 'required|in:selesai,belum',
            'catatan' => 'nullable|string|max:1000',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');
                $filename = 'lampiran_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $lampiranPath = $file->storeAs('order-lampiran', $filename, 'public');
            }

            $service->userConfirm(
                auth()->user(),
                $orderPerbaikan,
                $validated['catatan'] ?? null,
                $validated['konfirmasi'] === 'selesai',
                $lampiranPath
            );

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', $validated['konfirmasi'] === 'selesai'
                    ? 'Terima kasih, order telah dikonfirmasi selesai.'
                    : 'Order dibuka kembali karena belum selesai.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
