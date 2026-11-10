<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanHistori extends Model
{
    protected $table = 'pengajuan_histori';

    protected $fillable = [
        'pengajuan_id',
        'status',
        'action',
        'notes',
        'actor_id',
        'actor_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Tentukan jenis pelaku berdasarkan role user, bukan hardcode 'user'.
     * Dipakai saat menulis histori agar timeline menampilkan admin dengan benar.
     */
    public static function actorTypeFor(?User $actor): string
    {
        if ($actor === null) {
            return 'system';
        }

        if ($actor->isAdminIT() || $actor->isAdminUmum()) {
            return 'admin';
        }

        // Fallback: siapa pun yang punya permission pengelola dianggap admin,
        // selaras dengan penentuan sender_type pada PengajuanService.
        if (
            $actor->hasPermission('permission')
            || $actor->hasPermission('dashboard.it')
            || $actor->hasPermission('dashboard.ipsrs')
        ) {
            return 'admin';
        }

        return 'user';
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(PengajuanLampiran::class, 'histori_id');
    }

    // Backward-compat accessors for legacy order views/components
    public function getCreatorAttribute()
    {
        return $this->actor;
    }

    public function getCreatedByAttribute(): ?int
    {
        return $this->actor_id;
    }

    public function getKeteranganAttribute(): ?string
    {
        return $this->notes;
    }

    public function getFollowUpAttribute(): ?string
    {
        return $this->notes;
    }

    public function getLampiranAttribute(): ?string
    {
        $lampiran = $this->lampirans->first();

        return $lampiran?->path;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'ditutup', 'tutup', 'closed', 'pending' => 'Ditutup',
            'confirmed' => 'Confirmed',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'open' => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'ditutup', 'tutup', 'closed', 'pending' => 'bg-violet-100 text-violet-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
