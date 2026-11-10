<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;

/**
 * Detail pengajuan admin (Tiket IT / SIRS & Order Perbaikan / IPSRS).
 * Dipakai oleh halaman report (klik baris) dan halaman admin lain.
 */
class PengajuanController extends Controller
{
    public function show(Pengajuan $pengajuan)
    {
        $pengajuan->load([
            'kategori.jenis',
            'pemohon',
            'department',
            'lokasi',
            'building',
            'historis.actor',
            'pesans.sender',
            'lampirans',
            'penugasan.user',
        ]);

        return view('admin.pengajuan.show', [
            'pengajuan' => $pengajuan,
        ]);
    }
}
