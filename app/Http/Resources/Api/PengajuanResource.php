<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PengajuanResource extends JsonResource
{
    /**
     * Status pengajuan pada saat pesan dibuat.
     *
     * `pengajuan_pesan` tidak punya kolom status, jadi status diturunkan dari
     * histori terakhir yang terjadi sebelum/tepat pada waktu pesan dibuat.
     * Pesan yang lebih tua dari histori pertama memakai status histori pertama
     * (perilaku "status saat itu" tetap masuk akal). Null bila tidak ada
     * histori sama sekali -> Flutter menyembunyikan chip.
     */
    protected function statusOfMessage($pesan, $historis): ?string
    {
        if ($historis->isEmpty()) {
            return null;
        }

        $at = $pesan->created_at;
        if (! $at) {
            return null;
        }

        $before = null;
        foreach ($historis as $h) {
            if (! $h->created_at) {
                continue;
            }
            if ($h->created_at->lessThanOrEqualTo($at)) {
                $before = $h;
            } else {
                break;
            }
        }

        // Pesan lebih tua dari histori pertama -> pakai histori pertama agar
        // bubble paling awal tetap punya konteks status.
        $chosen = $before ?? $historis->first();

        return $chosen->status ? (string) $chosen->status : null;
    }

    public function toArray($request): array
    {
        $user = $request->user();
        $ownerId = $this->created_by ?? $this->pemohon_id;
        $canEdit = $user ? ((int) $ownerId === (int) $user->id && $this->status === 'open') : false;

        $priority = $this->prioritas ?? $this->priority ?? 'medium';
        $priority = in_array($priority, ['low', 'medium', 'high'], true) ? $priority : match (strtolower((string) $priority)) {
            'rendah' => 'low',
            'sedang' => 'medium',
            'tinggi', 'tinggi/urgent', 'urgent' => 'high',
            default => 'medium',
        };

        $kategori = $this->whenLoaded('kategori', function () {
            $k = $this->kategori;
            if (! $k) {
                return null;
            }
            return [
                'id' => $k->id,
                'name' => $k->name,
                'code' => $k->code ?? null,
                'jenis' => $k->relationLoaded('jenis') && $k->jenis ? [
                    'id' => $k->jenis->id,
                    'code' => $k->jenis->code,
                    'name' => $k->jenis->name,
                    'prefix' => $k->jenis->prefix ?? null,
                ] : null,
            ];
        });

        $pemohon = $this->whenLoaded('pemohon', function () {
            $u = $this->pemohon;
            if (! $u) {
                return null;
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'department' => $u->department ?? null,
                'position' => $u->position ?? null,
            ];
        });

        $department = $this->whenLoaded('department', function () {
            $d = $this->department;
            if (! $d) {
                return null;
            }
            return ['id' => $d->id, 'code' => $d->code ?? null, 'name' => $d->name];
        });

        $lokasi = $this->whenLoaded('lokasi', function () {
            $l = $this->lokasi;
            if (! $l) {
                return null;
            }
            return ['id' => $l->id, 'name' => $l->name];
        });

        $building = $this->whenLoaded('building', function () {
            $b = $this->building;
            if (! $b) {
                return null;
            }
            return ['id' => $b->id, 'name' => $b->name, 'code' => $b->code ?? null];
        });

        $lampirans = $this->whenLoaded('lampirans', function () {
            return $this->lampirans->map(function ($l) {
                $path = $l->path ?? null;
                $url = $path ? Storage::disk('public')->url($path) : null;
                return [
                    'id' => $l->id,
                    'path' => $path,
                    'url' => $url,
                    'kind' => $l->kind ?? null,
                    'original_name' => $l->original_name ?? null,
                ];
            })->values();
        });

        $historis = $this->whenLoaded('historis', function () {
            return $this->historis->map(function ($h) {
                return [
                    'id' => $h->id,
                    'status' => $h->status,
                    'action' => $h->action,
                    'notes' => $h->notes ?? $h->keterangan ?? $h->follow_up ?? null,
                    'keterangan' => $h->keterangan ?? $h->notes ?? $h->follow_up ?? null,
                    'follow_up' => $h->follow_up ?? $h->notes ?? $h->keterangan ?? null,
                    'actor_id' => $h->actor_id,
                    'actor_type' => $h->actor_type,
                    'actor' => $h->relationLoaded('actor') && $h->actor ? ['id' => $h->actor->id, 'name' => $h->actor->name] : null,
                    'created_at' => $h->created_at,
                    'lampiran' => $h->lampiran,
                    'lampiran_url' => $h->lampiran ? \Illuminate\Support\Facades\Storage::disk('public')->url($h->lampiran) : null,
                    'lampirans' => $h->relationLoaded('lampirans')
                        ? $h->lampirans->map(fn ($l) => [
                            'id' => $l->id,
                            'path' => $l->path,
                            'url' => $l->path ? \Illuminate\Support\Facades\Storage::disk('public')->url($l->path) : null,
                        ])->values()->all()
                        : [],
                ];
            })->values();
        });

        $pesans = $this->whenLoaded('pesans', function () {
            // `pengajuan_pesan` tidak punya kolom status, jadi status per
            // pesan diturunkan dari `pengajuan_histori` (punya kolom status)
            // memakai relasi yang sudah eager-loaded — tanpa query tambahan.
            $historis = $this->relationLoaded('historis')
                ? $this->historis
                    ->sortBy(fn ($h) => sprintf('%s|%010d', $h->created_at?->format('Y-m-d H:i:s') ?? '', $h->id))
                    ->values()
                : collect();

            return $this->pesans->map(function ($p) use ($historis) {
                $lampiran = $p->relationLoaded('lampirans')
                    ? $p->lampirans->map(function ($l) {
                        $path = $l->path ?? null;

                        return [
                            'id' => $l->id,
                            'path' => $path,
                            'url' => $path ? Storage::disk('public')->url($path) : null,
                            'kind' => $l->kind ?? null,
                            'original_name' => $l->original_name ?? null,
                        ];
                    })->values()
                    : collect();

                $urls = $lampiran->pluck('url')->filter()->values()->all();
                $status = $this->statusOfMessage($p, $historis);

                return [
                    'id' => $p->id,
                    'body' => $p->body,
                    'sender_type' => $p->sender_type ?? null,
                    'sender' => $p->relationLoaded('sender') && $p->sender ? ['id' => $p->sender->id, 'name' => $p->sender->name] : null,
                    'created_at' => $p->created_at,
                    // Status pengajuan saat balasan ini dibuat — dipakai
                    // Flutter untuk chip status di setiap bubble replies.
                    'status' => $status,
                    'ticket_status' => $status,
                    // Lampiran pesan (mis. foto tanggapan admin) — dipakai Flutter.
                    'lampirans' => $lampiran,
                    'photos' => $lampiran,
                    'photo_urls' => $urls,
                    'photo_url' => $urls[0] ?? null,
                ];
            })->values();
        });

        $aktif = $this->relationLoaded('penugasan')
            ? $this->penugasan->filter(fn ($a) => $a->released_at === null)->values()
            : collect();

        $penugasan = $this->whenLoaded('penugasan', function () use ($aktif) {
            return $aktif->map(function ($a) {
                $u = $a->relationLoaded('user') ? $a->user : null;

                return [
                    'id' => $a->user_id,
                    'user_id' => $a->user_id,
                    'name' => $u?->name,
                    'role_id' => $a->role_id,
                    'assigned_by' => $a->assigned_by,
                    'assigned_at' => $a->assigned_at?->toIso8601String(),
                ];
            })->values();
        });
        $pjNames = $aktif
            ->map(fn ($a) => $a->relationLoaded('user') && $a->user ? $a->user->name : null)
            ->filter()
            ->values()
            ->all();
        $pjName = ! empty($pjNames) ? implode(', ', $pjNames) : null;
        $pjIds = $aktif->pluck('user_id')->filter()->values()->all();

        $timeline = $this->whenLoaded('historis', function () {
            return $this->historis->map(function ($h) {
                $isAdmin = ($h->actor_type ?? '') === 'admin';
                $actorLoaded = $h->relationLoaded('actor') && $h->actor;
                $by = $actorLoaded
                    ? $h->actor->name
                    : ($isAdmin ? 'Admin' : ($this->pemohon_nama ?? 'User'));

                return [
                    'at' => $h->created_at,
                    'by' => $by,
                    'actor_id' => $h->actor_id,
                    'actor_name' => $actorLoaded ? $h->actor->name : null,
                    'action' => $h->action ?? $h->status ?? 'updated',
                    'notes' => $h->notes,
                    'is_admin' => $isAdmin,
                    'status' => $h->status,
                ];
            })->values();
        });

        $deskripsi = $this->deskripsi ?? $this->description ?? null;
        $nomor = $this->nomor ?? $this->ticket_number ?? null;

        return [
            'id' => $this->id,
            'nomor' => $nomor,
            'ticket_number' => $nomor,
            'order_number' => $nomor,
            'kategori_id' => $this->kategori_id,
            'kategori' => $kategori,
            'pemohon_id' => $this->pemohon_id,
            'pemohon_nama' => $this->pemohon_nama ?? ($this->relationLoaded('pemohon') && $this->pemohon ? $this->pemohon->name : null),
            'pemohon' => $pemohon,
            'department_id' => $this->department_id,
            'department' => $department,
            'lokasi_id' => $this->lokasi_id,
            'building_id' => $this->building_id,
            'lokasi' => $lokasi,
            'building' => $building,
            'deskripsi' => $deskripsi,
            'keluhan' => $deskripsi,
            'description' => $deskripsi,
            'prioritas' => $priority,
            'priority' => $priority,
            'status' => $this->status,
            'follow_up' => $this->follow_up,
            'nama_penanggung_jawab' => $pjName,
            'penanggung_jawab' => $penugasan,
            'penanggung_jawab_ids' => $pjIds,
            'can_assign' => $user
                ? app(\App\Services\Pengajuan\PengajuanScopeService::class)->canAct($user, $this->resource)
                : false,
            'tanggal' => $this->tanggal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'started_at' => $this->started_at ?? null,
            'closed_at' => $this->closed_at ?? null,
            'confirmed_at' => $this->confirmed_at ?? null,
            'user_confirmation' => (bool) ($this->user_confirmation ?? false),
            'user_confirmed_at' => $this->user_confirmed_at ?? null,
            'rejection_count' => (int) ($this->rejection_count ?? 0),
            'historis' => $historis,
            'history' => $historis,
            'pesans' => $pesans,
            'replies' => $pesans,
            'timeline' => $timeline,
            'lampirans' => $lampirans,
            'photos' => $lampirans,
            'can_edit' => $canEdit,
            'can_delete' => $canEdit,
        ];
    }
}
