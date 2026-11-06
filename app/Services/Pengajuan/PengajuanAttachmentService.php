<?php

namespace App\Services\Pengajuan;

use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\PengajuanLampiran;
use App\Models\PengajuanPesan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PengajuanAttachmentService
{
    public function store(
        Pengajuan $pengajuan,
        array $files,
        string $kind = 'initial',
        ?User $uploader = null,
        ?PengajuanHistori $histori = null,
        ?PengajuanPesan $pesan = null
    ): array {
        $files = array_values(array_filter($files));

        if (count($files) > 3) {
            throw new \InvalidArgumentException('Maksimal 3 lampiran.');
        }

        $jenisCode = $pengajuan->kategori?->jenis?->code ?? 'IT';
        $paths = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $filename = 'pengajuan_' . uniqid() . '_' . time() . '.' . $extension;
            $directory = 'pengajuan/' . $jenisCode . '/' . now()->format('Y/m');

            $path = $file->storeAs($directory, $filename, 'public');

            PengajuanLampiran::create([
                'pengajuan_id' => $pengajuan->id,
                'histori_id' => $histori?->id,
                'pesan_id' => $pesan?->id,
                'kind' => $kind,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $uploader?->id,
            ]);

            $paths[] = $path;
        }

        return $paths;
    }

    public function deletePaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
