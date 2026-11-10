<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;


class PengajuanController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
    ) {}

    /** Jenis pengajuan yang dititipkan route (null = semua jenis). */
    protected function jenis(Request $request): ?string
    {
        $jenis = $request->route('jenis');

        return $jenis ? strtoupper((string) $jenis) : null;
    }

    /** Query dasar: selalu milik user yang login, opsional difilter jenis. */
    protected function baseQuery(Request $request): Builder
    {
        $query = Pengajuan::query()->where('pemohon_id', $request->user()->id);

        if ($jenis = $this->jenis($request)) {
            $query->whereHas('kategori.jenis', fn (Builder $q) => $q->where('code', $jenis));
        }

        return $query;
    }

    /** Tolak akses bila pengajuan bukan jenis milik prefix route. */
    protected function guardJenis(Request $request, Pengajuan $pengajuan): bool
    {
        $jenis = $this->jenis($request);

        if ($jenis === null) {
            return true;
        }

        $pengajuan->loadMissing('kategori.jenis');

        return strtoupper((string) $pengajuan->kategori?->jenis?->code) === $jenis;
    }

    /** Tolak akses bila pengajuan bukan milik user. */
    protected function guardOwner(Request $request, Pengajuan $pengajuan): bool
    {
        return (int) $pengajuan->pemohon_id === (int) $request->user()->id;
    }

    protected function relations(): array
    {
        return [
            'kategori.jenis', 'pemohon', 'department', 'lokasi', 'building',
            'penugasan.user', 'historis.actor', 'pesans.sender', 'pesans.lampirans', 'lampirans',
        ];
    }

    public function index(Request $request)
    {
        $query = $this->baseQuery($request)->with([
            'kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user',
        ]);

        // Status bisa datang dari path (filter/{status}) atau query string.
        $status = $request->route('status') ?? $request->input('status');
        if ($status && $status !== 'all') {
            $query->where('status', $this->normalizeStatus($status));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        if ($request->filled('priority')) {
            $query->where('prioritas', $request->priority);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $paginator = $query->latest()->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan',
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'status' => $status ?: 'all',
                'jenis' => $this->jenis($request) ?? 'all',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'description' => 'required_without:deskripsi|nullable|string',
            'deskripsi' => 'required_without:description|nullable|string',
            'keluhan' => 'nullable|string',
            'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
            'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'department_id' => 'nullable|exists:departments,id',
            'photo' => 'nullable|image|max:5120',
        ]);

        try {
            $pengajuan = $this->service->create($request->user(), [
                'kategori_id' => $validated['kategori_id'],
                'deskripsi' => $validated['description'] ?? $validated['deskripsi'] ?? $validated['keluhan'],
                'prioritas' => $validated['priority'] ?? $validated['prioritas'],
                'department_id' => $validated['department_id'] ?? $request->user()->department_id,
                'lokasi_id' => $validated['location_id'] ?? null,
                'building_id' => $validated['building_id'] ?? null,
            ], $request->file('photo') ? [$request->file('photo')] : []);

            $pengajuan->loadMissing($this->relations());

            return $this->success(new PengajuanResource($pengajuan), 'Pengajuan berhasil dibuat', 201);
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function show(Request $request, Pengajuan $pengajuan)
    {
        if (! $this->guardOwner($request, $pengajuan) || ! $this->guardJenis($request, $pengajuan)) {
            return $this->error('Unauthorized', 403);
        }

        $pengajuan->load($this->relations());

        return $this->success(new PengajuanResource($pengajuan), 'Detail pengajuan');
    }

    public function update(Request $request, Pengajuan $pengajuan)
    {
        if (! $this->guardOwner($request, $pengajuan) || ! $this->guardJenis($request, $pengajuan)) {
            return $this->error('Unauthorized', 403);
        }

        try {
            $validated = $request->validate([
                'description' => 'required_without:deskripsi|nullable|string',
                'deskripsi' => 'required_without:description|nullable|string',
                'keluhan' => 'nullable|string',
                'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
                'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
                'location_id' => 'nullable|exists:locations,id',
                'building_id' => 'nullable|exists:buildings,id',
                'kategori_id' => 'nullable|exists:kategori_pengajuan,id',
                'department_id' => 'nullable|exists:departments,id',
                'photo' => 'nullable|image|max:5120',
            ]);

            $data = [
                'deskripsi' => $validated['description'] ?? $validated['deskripsi'] ?? $validated['keluhan'] ?? null,
                'prioritas' => $validated['priority'] ?? $validated['prioritas'] ?? null,
            ];
            if (array_key_exists('location_id', $validated)) {
                $data['lokasi_id'] = $validated['location_id'];
            }
            if (array_key_exists('building_id', $validated)) {
                $data['building_id'] = $validated['building_id'];
            }
            if (array_key_exists('department_id', $validated)) {
                $data['department_id'] = $validated['department_id'];
            }
            if (! empty($validated['kategori_id'])) {
                $data['kategori_id'] = $validated['kategori_id'];
            }

            $updated = $this->service->updateOwn(
                $request->user(),
                $pengajuan,
                $data,
                $request->file('photo') ? [$request->file('photo')] : []
            );
            $updated->loadMissing($this->relations());

            return $this->success(new PengajuanResource($updated), 'Pengajuan berhasil diperbarui');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function destroy(Request $request, Pengajuan $pengajuan)
    {
        if (! $this->guardOwner($request, $pengajuan) || ! $this->guardJenis($request, $pengajuan)) {
            return $this->error('Unauthorized', 403);
        }

        try {
            $this->service->deleteOwn($request->user(), $pengajuan);

            return $this->success(null, 'Pengajuan berhasil dihapus');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function reply(Request $request, Pengajuan $pengajuan)
    {
        if (! $this->guardOwner($request, $pengajuan) || ! $this->guardJenis($request, $pengajuan)) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'message' => 'required_without:body|nullable|string',
            'body' => 'required_without:message|nullable|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        try {
            $pesan = $this->service->addMessage(
                $request->user(),
                $pengajuan,
                $validated['message'] ?? $validated['body'],
                $request->file('photo') ? [$request->file('photo')] : []
            );
            $pesan->loadMissing(['sender', 'lampirans']);

            return $this->success($pesan, 'Reply berhasil dikirim');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function confirm(Request $request, Pengajuan $pengajuan)
    {
        if (! $this->guardOwner($request, $pengajuan) || ! $this->guardJenis($request, $pengajuan)) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'confirmation_notes' => 'nullable|string',
            'catatan' => 'nullable|string',
            'action' => 'nullable|in:confirm,reject,selesai,belum',
            'konfirmasi' => 'nullable|in:confirm,reject,selesai,belum',
        ]);

        $action = $validated['action'] ?? $validated['konfirmasi'] ?? 'confirm';
        $selesai = in_array($action, ['confirm', 'selesai'], true);
        $notes = $validated['confirmation_notes'] ?? $validated['catatan'] ?? null;

        try {
            $this->workflow->userConfirm($request->user(), $pengajuan, $notes, $selesai, []);
            $pengajuan->refresh()->load($this->relations());

            return $this->success(
                new PengajuanResource($pengajuan),
                $selesai ? 'Pengajuan dikonfirmasi selesai' : 'Pengajuan dikembalikan ke in_progress'
            );
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    /** Alias endpoint lama: /api/tickets/filter/{status}. */
    public function filterByStatus(Request $request, string $status)
    {
        return $this->index($request);
    }

    protected function normalizeStatus(string $status): string
    {
        return match ($status) {
            'pending', 'pending_confirmation', 'closed', 'tutup' => 'ditutup',
            default => $status,
        };
    }
}
