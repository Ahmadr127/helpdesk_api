<?php

namespace App\Services\Pengajuan;

use App\Models\LegacyMap;
use App\Models\Pengajuan;

class LegacyPengajuanResolver
{
    public function resolve(string $source, int|string $legacyId): ?Pengajuan
    {
        $map = LegacyMap::where('legacy_source', $source)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($map && $map->pengajuan_id) {
            return Pengajuan::find($map->pengajuan_id);
        }

        return Pengajuan::where('legacy_source', $source)
            ->where('legacy_id', $legacyId)
            ->first();
    }

    public function pengajuanId(string $source, int|string $legacyId): ?int
    {
        return $this->resolve($source, $legacyId)?->id;
    }

    public function legacyId(Pengajuan $pengajuan): ?int
    {
        return $pengajuan->legacy_id ? (int) $pengajuan->legacy_id : null;
    }
}