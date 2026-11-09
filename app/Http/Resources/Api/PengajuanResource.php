<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PengajuanResource extends JsonResource
{
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
                    'notes' => $h->notes,
                    'keterangan' => $h->notes,
                    'follow_up' => $h->notes,
                    'actor_id' => $h->actor_id,
                    'actor_type' => $h->actor_type,
                    'actor' => $h->relationLoaded('actor') && $h->actor ? ['id' => $h->actor->id, 'name' => $h->actor->name] : null,
                    'created_at' => $h->created_at,
                ];
            })->values();
        });

        $pesans = $this->whenLoaded('pesans', function () {
            return $this->pesans->map(function ($p) {
                return [
                    'id' => $p->id,
                    'body' => $p->body,
                    'sender_type' => $p->sender_type ?? null,
                    'sender' => $p->relationLoaded('sender') && $p->sender ? ['id' => $p->sender->id, 'name' => $p->sender->name] : null,
                    'created_at' => $p->created_at,
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
                $by = $h->relationLoaded('actor') && $h->actor
                    ? $h->actor->name
                    : ($isAdmin ? 'Admin' : ($this->pemohon_nama ?? 'User'));
                return [
                    'at' => $h->created_at,
                    'by' => $by,
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
