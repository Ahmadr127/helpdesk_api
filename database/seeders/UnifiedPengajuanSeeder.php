<?php

namespace Database\Seeders;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UnifiedPengajuanSeeder extends Seeder
{
    public function run(): void
    {
        $jenisIt = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        // Pastikan label sinkron dengan nama flavour baru (SIRS / UMUM).
        // Code dipertahankan agar data lama tidak perlu migrasi.
        JenisPengajuan::where('code', 'IT')->update(['name' => 'SIRS']);

        $jenisIpsrs = JenisPengajuan::firstOrCreate(
            ['code' => 'IPSRS'],
            ['name' => 'UMUM', 'prefix' => 'OP/RTG/MTC', 'is_active' => true]
        );

        JenisPengajuan::where('code', 'IPSRS')->update(['name' => 'UMUM']);

        $itKategoriNames = ['Printer', 'CCTV', 'Software', 'Hardware'];
        $itKategoris = [];
        foreach ($itKategoriNames as $name) {
            $kategori = KategoriPengajuan::withTrashed()->where('name', $name)->first();

            if (! $kategori) {
                $kategori = KategoriPengajuan::create([
                    'name' => $name,
                    'code' => null,
                    'description' => null,
                    'status' => 1,
                ]);
                $kategori->syncJenis([$jenisIt->id]);
            } else {
                $kategori->syncJenis(array_unique(array_merge(
                    $kategori->jenisList()->pluck('jenis_pengajuan.id')->all(),
                    [$jenisIt->id]
                )));
            }

            $itKategoris[] = $kategori->id;
        }

        $ipsrsKategoriNames = [
            ['name' => 'Perbaikan', 'code' => 'PRB'],
            ['name' => 'Pengadaan', 'code' => 'PENG'],
            ['name' => 'Kalibrasi', 'code' => 'KAL'],
            ['name' => 'Penggantian', 'code' => 'GNT'],
            ['name' => 'Lainnya', 'code' => 'LL'],
            ['name' => 'Termedic', 'code' => null],
        ];
        $ipsrsKategoris = [];
        foreach ($ipsrsKategoriNames as $data) {
            $kategori = KategoriPengajuan::withTrashed()->where('name', $data['name'])->first();

            if (! $kategori) {
                $kategori = KategoriPengajuan::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'description' => null,
                    'status' => 1,
                ]);
                $kategori->syncJenis([$jenisIpsrs->id]);
            } else {
                $kategori->syncJenis(array_unique(array_merge(
                    $kategori->jenisList()->pluck('jenis_pengajuan.id')->all(),
                    [$jenisIpsrs->id]
                )));
            }

            $ipsrsKategoris[] = $kategori->id;
        }

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && $itKategoris !== []) {
            $adminRole->kategoris()->sync($itKategoris);
        }

        $ipsrsRole = Role::where('slug', 'ipsrs')->first();
        if ($ipsrsRole && $ipsrsKategoris !== []) {
            $ipsrsRole->kategoris()->sync($ipsrsKategoris);
        }
    }
}
