<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\PengajuanResource;
use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\User;
use App\Services\Pengajuan\PengajuanNotificationService;
use App\Services\Pengajuan\PengajuanPenugasanService;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Satu controller admin untuk semua pengajuan (tiket IT & order IPSRS).
 *
 * Scope data memakai PengajuanScopeService (role_kategori) — sama seperti web,
 * bukan sekadar whereHas('kategori.jenisList'). Jenis pengajuan dititipkan route
 * lewat ->defaults('jenis', 'IT'|'IPSRS') agar prefix lama tetap kompatibel.
 */
class AdminPengajuanController extends BaseApiController
{
    /** Sentinel route `jenis` untuk flavour umum (semua jenis selain SIRS). */
    protected const JENIS_UMUM = 'UMUM';

    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
        protected PengajuanPenugasanService $penugasan,
        protected PengajuanNotificationService $notificationService,
    ) {}

    protected function jenis(Request $request): ?string
    {
        $jenis = $request->route('jenis');

        return $jenis ? strtoupper((string) $jenis) : null;
    }

    /** Terapkan filter flavour: `UMUM` = bukan SIRS, selain itu kode persis. */
    protected function applyJenis(Builder $query, string $jenis): Builder
    {
        if ($jenis === self::JENIS_UMUM) {
            return $query->umum();
        }

        return $query->whereHas(
            'kategori.jenisList',
            fn (Builder $q) => $q->where('code', $jenis)
        );
    }

    /**
     * Query dasar admin: scope role-kategori (sumber kebenaran tunggal) + filter jenis.
     */
    protected function baseQuery(Request $request): Builder
    {
        $query = $this->scope->visibleTo($request->user());

        if ($jenis = $this->jenis($request)) {
            $this->applyJenis($query, $jenis);
        }

        return $query;
    }

    protected function relations(): array
    {
        return [
            'kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building',
            'penugasan.user', 'penugasan.role', 'historis.actor', 'pesans.sender', 'pesans.lampirans', 'lampirans',
        ];
    }

    /** Pastikan pengajuan terlihat oleh user (scope) dan sesuai jenis prefix. */
    protected function ensureVisible(Request $request, Pengajuan $pengajuan): ?\Illuminate\Http\JsonResponse
    {
        if (! $this->scope->canView($request->user(), $pengajuan)) {
            return $this->error('Forbidden - pengajuan di luar scope role Anda', 403);
        }

        $jenis = $this->jenis($request);
        if ($jenis !== null) {
            $pengajuan->loadMissing('kategori.jenisList');
            $codes = collect($pengajuan->kategori?->jenisCodes ?? []);

            // Sentinel UMUM = flavour umum (punya minimal satu jenis non-SIRS).
            if ($jenis === self::JENIS_UMUM) {
                if (! $codes->contains(fn ($code) => strtoupper((string) $code) !== 'IT')) {
                    return $this->error('Pengajuan bukan jenis umum', 403);
                }
            } elseif (! $codes->contains($jenis)) {
                return $this->error('Pengajuan bukan jenis '.$jenis, 403);
            }
        }

        return null;
    }

    protected function ensureCanAct(Request $request, Pengajuan $pengajuan): ?\Illuminate\Http\JsonResponse
    {
        if (! $this->scope->canAct($request->user(), $pengajuan)) {
            return $this->error('Anda tidak berhak mengubah pengajuan ini.', 403);
        }

        return null;
    }

    /**
     * Kumpulkan file lampiran dari request (mendukung `photo` tunggal dan
     * `photos[]` / `lampirans[]` array). Dipakai bersama oleh endpoint admin
     * agar lampiran dari mobile tidak lagi dibuang diam-diam.
     *
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    protected function uploadedFiles(Request $request): array
    {
        $files = [];

        foreach (['photos', 'lampirans'] as $key) {
            $raw = $request->file($key);
            if (is_array($raw)) {
                foreach ($raw as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $files[] = $file;
                    }
                }
            }
        }

        $single = $request->file('photo');
        if ($single instanceof \Illuminate\Http\UploadedFile) {
            $files[] = $single;
        }

        return array_values(array_filter($files));
    }

    /** Aturan validasi lampiran yang dipakai semua endpoint tulis. */
    protected function attachmentRules(): array
    {
        return [
            'photo' => 'nullable|image|max:5120',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|max:5120',
            'lampirans' => 'nullable|array|max:3',
            'lampirans.*' => 'image|max:5120',
        ];
    }

    protected function normalizeStatus(string $status): string
    {
        return match ($status) {
            'pending', 'pending_confirmation', 'closed', 'tutup' => 'ditutup',
            default => $status,
        };
    }

    /**
     * Transisi status lewat workflow agar started_at/closed_at/histori/notifikasi
     * konsisten dengan web.
     *
     * @return bool true bila workflow sudah mengirim notifikasi sendiri
     *              (close/start), sehingga pemanggil tidak perlu mengirim lagi.
     */
    protected function applyStatus(User $actor, Pengajuan $pengajuan, string $status, ?string $notes = null): bool
    {
        $status = $this->normalizeStatus($status);
        $current = $pengajuan->status;

        if ($status === $current) {
            return false;
        }

        if ($status === 'ditutup') {
            if (in_array($current, ['open', 'in_progress'], true)) {
                // close() sudah memanggil notifyStatusChanged('closed').
                $this->workflow->close($actor, $pengajuan->fresh(), $notes);

                return true;
            }
            $pengajuan->update(['status' => 'ditutup', 'closed_at' => $pengajuan->closed_at ?: now()]);
            $this->logTransition($actor, $pengajuan, 'ditutup', $notes);

            return false;
        }

        if ($status === 'in_progress' && $current === 'open') {
            // start() sudah memanggil notifyStatusChanged('started').
            $this->workflow->start($actor, $pengajuan->fresh());

            return true;
        }

        $extra = match ($status) {
            'confirmed' => ['confirmed_at' => $pengajuan->confirmed_at ?: now()],
            'rejected' => ['rejected_at' => $pengajuan->rejected_at ?: now()],
            default => [],
        };

        $pengajuan->update(['status' => $status, ...$extra]);
        $this->logTransition($actor, $pengajuan, $status, $notes);

        return false;
    }

    protected function logTransition(User $actor, Pengajuan $pengajuan, string $status, ?string $notes = null): void
    {
        $pengajuan->historis()->create([
            'status' => $status,
            'action' => 'status_updated',
            'notes' => $notes ?: 'Status pengajuan diperbarui menjadi '.$status.'.',
            'actor_id' => $actor->id,
            'actor_type' => PengajuanHistori::actorTypeFor($actor),
        ]);
    }

    public function index(Request $request)
    {
        $query = $this->baseQuery($request)->with([
            'kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user',
        ]);

        // Status bisa datang dari query string atau dari default route (open/in-progress/...).
        $status = $request->input('status') ?? $request->route('status');
        if ($status && $status !== 'all') {
            $normalized = $this->normalizeStatus($status);
            $query->where('status', $normalized);

            if ($normalized === 'ditutup') {
                $query->where('user_confirmation', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('pemohon_nama', 'like', "%{$search}%")
                    ->orWhereHas('pemohon', fn ($p) => $p->where('name', 'like', "%{$search}%"));
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

        // Statistik WAJIB dari baseQuery yang sama agar angka konsisten dengan daftar.
        $base = $this->baseQuery($request);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan (admin)',
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'jenis' => $this->jenis($request) ?? 'all',
            ],
            'stats' => [
                'total' => (clone $base)->count(),
                'open' => (clone $base)->where('status', 'open')->count(),
                'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
                'ditutup' => (clone $base)->where('status', 'ditutup')->count(),
                'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function show(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        $pengajuan->load($this->relations());

        return $this->success(new PengajuanResource($pengajuan), 'Detail pengajuan');
    }

    public function respond(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }
        if ($deny = $this->ensureCanAct($request, $pengajuan)) {
            return $deny;
        }

        $validated = $request->validate([
            'notes' => 'required|string',
            'status' => 'required|in:in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
            ...$this->attachmentRules(),
        ]);

        try {
            $this->service->addMessage(
                $request->user(),
                $pengajuan,
                $validated['notes'],
                $this->uploadedFiles($request)
            );
            $workflowNotified = $this->applyStatus($request->user(), $pengajuan, $validated['status'], $validated['notes']);

            $newlyAssigned = [];
            if (array_key_exists('penanggung_jawab_ids', $validated)) {
                $newlyAssigned = $this->service->assignStrict(
                    $request->user(),
                    $pengajuan->fresh(),
                    $validated['penanggung_jawab_ids'] ?? []
                );
            }

            $pengajuan->refresh()->load($this->relations());
            if (! $workflowNotified) {
                $this->notificationService->notifyStatusChanged($pengajuan, $request->user(), 'responded');
            }
            if (! empty($newlyAssigned)) {
                $this->notificationService->notifyAssigned($pengajuan, $newlyAssigned, $request->user());
            }

            return $this->success(new PengajuanResource($pengajuan), 'Response berhasil ditambahkan');
        } catch (ValidationException $e) {
            return $this->error('Penugasan ditolak', 422, $e->errors());
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function update(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }
        if ($deny = $this->ensureCanAct($request, $pengajuan)) {
            return $deny;
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:open,in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
            ...$this->attachmentRules(),
        ]);

        try {
            if (isset($validated['notes'])) {
                $this->service->addMessage(
                    $request->user(),
                    $pengajuan,
                    $validated['notes'],
                    $this->uploadedFiles($request)
                );
            }

            $statusChanged = false;
            $workflowNotified = false;
            if (isset($validated['status'])) {
                $workflowNotified = $this->applyStatus($request->user(), $pengajuan, $validated['status'], $validated['notes'] ?? null);
                $statusChanged = true;
            }

            $newlyAssigned = [];
            if (array_key_exists('penanggung_jawab_ids', $validated)) {
                $newlyAssigned = $this->service->assignStrict(
                    $request->user(),
                    $pengajuan->fresh(),
                    $validated['penanggung_jawab_ids'] ?? []
                );
            }

            $pengajuan->refresh()->load($this->relations());
            if ($statusChanged && ! $workflowNotified) {
                $this->notificationService->notifyStatusChanged($pengajuan, $request->user(), 'status_updated');
            }
            if (! empty($newlyAssigned)) {
                $this->notificationService->notifyAssigned($pengajuan, $newlyAssigned, $request->user());
            }

            return $this->success(new PengajuanResource($pengajuan), 'Pengajuan diperbarui');
        } catch (ValidationException $e) {
            return $this->error('Penugasan ditolak', 422, $e->errors());
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    /** Alias status-only untuk prefix administrasi-umum (kontrak lama). */
    public function updateStatus(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }
        if ($deny = $this->ensureCanAct($request, $pengajuan)) {
            return $deny;
        }

        $validated = $request->validate([
            'status' => 'required|in:in_progress,pending_confirmation,rejected,ditutup,tutup,confirmed',
            'follow_up' => 'nullable|string',
            'notes' => 'nullable|string',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
            ...$this->attachmentRules(),
        ]);

        try {
            if (! empty($validated['follow_up'])) {
                $pengajuan->update(['follow_up' => $validated['follow_up']]);
            }

            // Lampiran pada update status disimpan sebagai pesan admin agar
            // tidak hilang (sebelumnya file diabaikan sepenuhnya).
            $files = $this->uploadedFiles($request);
            $statusNote = $validated['notes'] ?? $validated['follow_up'] ?? null;
            if (! empty($files) && ($statusNote === null || trim((string) $statusNote) === '')) {
                $statusNote = 'Status diperbarui menjadi '.$validated['status'].'.';
            }
            if (! empty($files)) {
                $this->service->addMessage($request->user(), $pengajuan, (string) $statusNote, $files);
            }

            $workflowNotified = $this->applyStatus($request->user(), $pengajuan, $validated['status'], $validated['notes'] ?? $validated['follow_up'] ?? null);

            $newlyAssigned = [];
            if (array_key_exists('penanggung_jawab_ids', $validated)) {
                $newlyAssigned = $this->service->assignStrict(
                    $request->user(),
                    $pengajuan->fresh(),
                    $validated['penanggung_jawab_ids'] ?? []
                );
            }

            $pengajuan->refresh()->load($this->relations());
            if (! $workflowNotified) {
                $this->notificationService->notifyStatusChanged($pengajuan, $request->user(), 'status_updated');
            }
            if (! empty($newlyAssigned)) {
                $this->notificationService->notifyAssigned($pengajuan, $newlyAssigned, $request->user());
            }

            return $this->success(new PengajuanResource($pengajuan), 'Status berhasil diperbarui');
        } catch (ValidationException $e) {
            return $this->error('Penugasan ditolak', 422, $e->errors());
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function updatePriority(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        $validated = $request->validate([
            'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
            'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
        ]);

        $pengajuan->update(['prioritas' => $validated['prioritas'] ?? $validated['priority']]);
        $pengajuan->load($this->relations());

        return $this->success(new PengajuanResource($pengajuan), 'Prioritas berhasil diperbarui');
    }

    public function updateSubmission(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }
        if ($deny = $this->ensureCanAct($request, $pengajuan)) {
            return $deny;
        }

        $validated = $request->validate([
            'description' => 'sometimes|required|string',
            'deskripsi' => 'sometimes|required|string',
            'keluhan' => 'sometimes|required|string',
            'category_id' => 'nullable|integer',
            'kategori_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'lokasi' => 'nullable|integer',
            'building_id' => 'nullable|integer',
            'priority' => 'nullable|in:low,medium,high',
            'prioritas' => 'nullable|in:low,medium,high',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
            ...$this->attachmentRules(),
        ]);

        try {
            $payload = [];
            $desc = $validated['description'] ?? $validated['deskripsi'] ?? $validated['keluhan'] ?? null;
            if ($desc !== null) {
                $payload['deskripsi'] = $desc;
            }
            $prio = $validated['priority'] ?? $validated['prioritas'] ?? null;
            if ($prio !== null) {
                $payload['prioritas'] = $prio;
            }
            $cat = $validated['category_id'] ?? $validated['kategori_id'] ?? null;
            if ($cat) {
                $payload['kategori_id'] = $cat;
            }
            if (array_key_exists('department_id', $validated)) {
                $payload['department_id'] = $validated['department_id'];
            }
            $lokasi = $validated['location_id'] ?? $validated['lokasi'] ?? null;
            if ($lokasi !== null) {
                $payload['lokasi_id'] = $lokasi;
            }
            if (array_key_exists('building_id', $validated)) {
                $payload['building_id'] = $validated['building_id'];
            }
            if ($payload !== []) {
                $pengajuan->update($payload);
            }

            // Lampiran pengganti (kind 'initial') bila admin mengunggah foto baru.
            $files = $this->uploadedFiles($request);
            if (! empty($files)) {
                $this->service->replaceInitialAttachments($request->user(), $pengajuan, $files);
            }

            $newlyAssigned = [];
            if (array_key_exists('penanggung_jawab_ids', $validated)) {
                $newlyAssigned = $this->service->assignStrict(
                    $request->user(),
                    $pengajuan->fresh(),
                    $validated['penanggung_jawab_ids'] ?? []
                );
            }

            $pengajuan->refresh()->load($this->relations());
            if (! empty($newlyAssigned)) {
                $this->notificationService->notifyAssigned($pengajuan, $newlyAssigned, $request->user());
            }

            return $this->success(new PengajuanResource($pengajuan), 'Pengajuan diperbarui');
        } catch (ValidationException $e) {
            return $this->error('Penugasan ditolak', 422, $e->errors());
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function confirm(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        try {
            $this->workflow->adminConfirm($request->user(), $pengajuan, $request->input('notes'));
            $pengajuan->refresh()->load($this->relations());

            return $this->success(new PengajuanResource($pengajuan), 'Pengajuan dikonfirmasi');
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function reject(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        try {
            $this->workflow->adminReject($request->user(), $pengajuan, $request->input('notes'));
            $pengajuan->refresh()->load($this->relations());

            return $this->success(new PengajuanResource($pengajuan), 'Pengajuan ditolak');
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function start(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        try {
            $this->workflow->start($request->user(), $pengajuan);
            $pengajuan->refresh()->load($this->relations());

            return $this->success(new PengajuanResource($pengajuan), 'Pengajuan dimulai');
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422], true) ? $e->getCode() : 500;

            return $this->error($e->getMessage(), $code);
        }
    }

    public function history(Request $request)
    {
        $query = $this->baseQuery($request)
            ->with(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user'])
            ->where('status', 'confirmed')
            ->where('user_confirmation', true);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $paginator = $query->orderByDesc('user_confirmed_at')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'History pengajuan confirmed',
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function historyShow(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        if ($pengajuan->status !== 'confirmed') {
            return $this->error('Pengajuan belum dikonfirmasi', 422);
        }

        $pengajuan->load($this->relations());

        return $this->success(new PengajuanResource($pengajuan), 'Detail history');
    }

    /**
     * GET /api/admin/pengajuan/{pengajuan}/penugasan
     * Kandidat PJ bersumber dari role_kategori (sama dengan web).
     */
    public function penugasan(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }

        $pengajuan->loadMissing(['kategori.jenisList', 'penugasan.user', 'penugasan.role']);

        return $this->success($this->penugasanPayload($request, $pengajuan), 'Data penugasan pengajuan');
    }

    /**
     * PUT /api/admin/pengajuan/{pengajuan}/penugasan  body: { "user_ids": [1,2] }
     */
    public function syncPenugasan(Request $request, Pengajuan $pengajuan)
    {
        if ($deny = $this->ensureVisible($request, $pengajuan)) {
            return $deny;
        }
        if ($deny = $this->ensureCanAct($request, $pengajuan)) {
            return $deny;
        }

        $validated = $request->validate([
            'user_ids' => 'present|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $newlyAssigned = $this->service->assignStrict(
                $request->user(),
                $pengajuan,
                $validated['user_ids']
            );
        } catch (ValidationException $e) {
            return $this->error('Penugasan ditolak', 422, $e->errors());
        }

        $pengajuan->refresh()->load($this->relations());

        if (! empty($newlyAssigned)) {
            $this->notificationService->notifyAssigned($pengajuan, $newlyAssigned, $request->user());
        }

        return $this->success($this->penugasanPayload($request, $pengajuan), 'Penanggung jawab berhasil diperbarui');
    }

    protected function penugasanPayload(Request $request, Pengajuan $pengajuan): array
    {
        return [
            'pengajuan_id' => $pengajuan->id,
            'nomor' => $pengajuan->nomor,
            'status' => $pengajuan->status,
            'kategori_id' => $pengajuan->kategori_id,
            'kategori' => $pengajuan->kategori?->name,
            'jenis' => $pengajuan->kategori?->jenis?->code,
            'active' => $this->penugasan->activePayload($pengajuan),
            'candidates' => $this->penugasan->candidatePayload($pengajuan),
            'can_assign' => $this->scope->canAct($request->user(), $pengajuan),
        ];
    }
}
