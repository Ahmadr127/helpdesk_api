<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            // Manajemen & Administrasi
            [
                'name' => 'Direktur Utama',
                'code' => 'DIR_UT',
                'status' => true,
            ],
            [
                'name' => 'Direktur Medis',
                'code' => 'DIR_MED',
                'status' => true,
            ],
            [
                'name' => 'Kepala HRD',
                'code' => 'KA_HRD',
                'status' => true,
            ],
            [
                'name' => 'Staff HRD',
                'code' => 'STF_HRD',
                'status' => true,
            ],
            [
                'name' => 'Kepala Keuangan',
                'code' => 'KA_KEU',
                'status' => true,
            ],
            [
                'name' => 'Staff Keuangan',
                'code' => 'STF_KEU',
                'status' => true,
            ],
            [
                'name' => 'Kepala IT',
                'code' => 'KA_IT',
                'status' => true,
            ],
            [
                'name' => 'Staff IT',
                'code' => 'STF_IT',
                'status' => true,
            ],

            // Medis
            [
                'name' => 'Dokter Spesialis',
                'code' => 'DR_SP',
                'status' => true,
            ],
            [
                'name' => 'Dokter Umum',
                'code' => 'DR_UM',
                'status' => true,
            ],
            [
                'name' => 'Kepala Perawat',
                'code' => 'KA_PRW',
                'status' => true,
            ],
            [
                'name' => 'Perawat IGD',
                'code' => 'PRW_IGD',
                'status' => true,
            ],
            [
                'name' => 'Perawat Rawat Inap',
                'code' => 'PRW_INAP',
                'status' => true,
            ],
            [
                'name' => 'Perawat Rawat Jalan',
                'code' => 'PRW_JALAN',
                'status' => true,
            ],
            [
                'name' => 'Perawat OK/Bedah',
                'code' => 'PRW_OK',
                'status' => true,
            ],
            [
                'name' => 'Bidan',
                'code' => 'BIDAN',
                'status' => true,
            ],

            // Penunjang Medis
            [
                'name' => 'Kepala Laboratorium',
                'code' => 'KA_LAB',
                'status' => true,
            ],
            [
                'name' => 'Staff Laboratorium',
                'code' => 'STF_LAB',
                'status' => true,
            ],
            [
                'name' => 'Kepala Radiologi',
                'code' => 'KA_RAD',
                'status' => true,
            ],
            [
                'name' => 'Staff Radiologi',
                'code' => 'STF_RAD',
                'status' => true,
            ],
            [
                'name' => 'Kepala Farmasi',
                'code' => 'KA_FARM',
                'status' => true,
            ],
            [
                'name' => 'Apoteker',
                'code' => 'APT',
                'status' => true,
            ],
            [
                'name' => 'Staff Farmasi',
                'code' => 'STF_FARM',
                'status' => true,
            ],
            [
                'name' => 'Kepala Gizi',
                'code' => 'KA_GIZI',
                'status' => true,
            ],
            [
                'name' => 'Ahli Gizi',
                'code' => 'GIZI',
                'status' => true,
            ],
            [
                'name' => 'Fisioterapis',
                'code' => 'FISIO',
                'status' => true,
            ],

            // Unit Khusus
            [
                'name' => 'Staff Hemodialisa',
                'code' => 'STF_HD',
                'status' => true,
            ],
            [
                'name' => 'Staff Bank Darah',
                'code' => 'STF_BD',
                'status' => true,
            ],
            [
                'name' => 'Staff CSSD',
                'code' => 'STF_CSSD',
                'status' => true,
            ],

            // Administrasi Pasien
            [
                'name' => 'Kepala Rekam Medis',
                'code' => 'KA_RM',
                'status' => true,
            ],
            [
                'name' => 'Staff Rekam Medis',
                'code' => 'STF_RM',
                'status' => true,
            ],
            [
                'name' => 'Kepala Pendaftaran',
                'code' => 'KA_DAFTAR',
                'status' => true,
            ],
            [
                'name' => 'Staff Pendaftaran',
                'code' => 'STF_DAFTAR',
                'status' => true,
            ],
            [
                'name' => 'Staff Asuransi',
                'code' => 'STF_ASR',
                'status' => true,
            ],

            // Penunjang Umum
            [
                'name' => 'Kepala Logistik',
                'code' => 'KA_LOG',
                'status' => true,
            ],
            [
                'name' => 'Staff Logistik',
                'code' => 'STF_LOG',
                'status' => true,
            ],
            [
                'name' => 'Staff Gudang',
                'code' => 'STF_GDG',
                'status' => true,
            ],
            [
                'name' => 'Staff Pembelian',
                'code' => 'STF_BELI',
                'status' => true,
            ],
            [
                'name' => 'Staff Keamanan',
                'code' => 'SECURITY',
                'status' => true,
            ],
            [
                'name' => 'Staff Kebersihan',
                'code' => 'CLEANING',
                'status' => true,
            ],
            [
                'name' => 'Staff Maintenance',
                'code' => 'MAINTENANCE',
                'status' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
} 