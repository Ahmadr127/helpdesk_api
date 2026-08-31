@extends('user.layouts.app')

@section('title', 'Manual Book')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-2 text-gray-800">Manual Book</h1>
        <p class="text-gray-600 mb-8">Panduan penggunaan sistem helpdesk dan pembuatan tiket/orderan</p>

        <!-- Department Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
            <!-- SIRS Section -->
            <div class="bg-gradient-to-r from-green-600 to-green-500 p-6 rounded-lg text-white">
                <h2 class="text-xl font-bold mb-3">SIRS (Sistem Informasi Rumah Sakit)</h2>
                <p class="mb-4">Layanan untuk kebutuhan teknologi informasi dan sistem rumah sakit:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Penanganan masalah software dan aplikasi</li>
                    <li>Dukungan sistem informasi</li>
                    <li>Pengelolaan jaringan dan hardware</li>
                    <li>Pemeliharaan database</li>
                </ul>
            </div>

            <!-- IPSRS Section -->
            <div class="bg-gradient-to-r from-blue-300 to-blue-200 p-6 rounded-lg text-gray-800">
                <h2 class="text-xl font-bold mb-3">IPSRS (Instalasi Pemeliharaan Sarana Rumah Sakit)</h2>
                <p class="mb-4">Layanan untuk pemeliharaan dan perbaikan sarana fisik:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Perbaikan fasilitas dan infrastruktur</li>
                    <li>Pemeliharaan peralatan non-medis</li>
                    <li>Pengelolaan sarana fisik</li>
                    <li>Perawatan gedung dan lingkungan</li>
                </ul>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
            <a href="#create-ticket"
                class="bg-green-50 hover:bg-green-100 p-4 rounded-lg border border-green-200 flex items-center transition duration-200">
                <div class="rounded-full bg-green-600 p-3 mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-green-800">Panduan Tiket SIRS</h3>
                    <p class="text-sm text-green-600">Cara membuat tiket layanan IT dan sistem informasi</p>
                </div>
            </a>
            <a href="#create-order"
                class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg border border-blue-200 flex items-center transition duration-200">
                <div class="rounded-full bg-blue-300 p-3 mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-blue-800">Panduan Order IPSRS</h3>
                    <p class="text-sm text-blue-600">Cara membuat orderan perbaikan sarana fisik</p>
                </div>
            </a>
        </div>

        <!-- Ticket and Order Guide Sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- SIRS Ticket Guide Section -->
            <div id="create-ticket" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-full">
                <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-white">Panduan Tiket SIRS</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Step 1 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <span
                                    class="flex items-center justify-center bg-green-600 text-white rounded-full w-6 h-6 mr-2 text-sm">1</span>
                                Akses Halaman SIRS
                            </h3>
                            <p class="text-gray-600 mb-2">Untuk membuat tiket IT, pilih kategori "SIRS" pada form
                                pembuatan tiket.</p>
                            <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                                <p class="text-sm text-green-800">
                                    <span class="font-semibold">Tip:</span> Pastikan memilih kategori yang sesuai dengan
                                    masalah IT yang Anda alami.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <span
                                    class="flex items-center justify-center bg-green-600 text-white rounded-full w-6 h-6 mr-2 text-sm">2</span>
                                Isi Detail Masalah IT
                            </h3>
                            <p class="text-gray-600 mb-3">Lengkapi informasi terkait masalah IT:</p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-600">
                                <li><span class="font-medium text-gray-700">Jenis Masalah:</span> Software, hardware,
                                    jaringan, atau sistem</li>
                                <li><span class="font-medium text-gray-700">Deskripsi:</span> Jelaskan detail masalah
                                    yang dialami</li>
                                <li><span class="font-medium text-gray-700">Screenshot:</span> Lampirkan screenshot
                                    error jika ada</li>
                                <li><span class="font-medium text-gray-700">Prioritas:</span> Tentukan urgensi
                                    penanganan</li>
                            </ul>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <span
                                    class="flex items-center justify-center bg-green-600 text-white rounded-full w-6 h-6 mr-2 text-sm">3</span>
                                Tindak Lanjut
                            </h3>
                            <p class="text-gray-600 mb-2">Tim IT akan merespon dan menangani masalah Anda sesuai
                                prioritas.</p>
                            <p class="text-gray-600">Pantau status tiket Anda melalui dashboard SIRS.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IPSRS Order Guide Section -->
            <div id="create-order" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-full">
                <div class="bg-gradient-to-r from-blue-300 to-blue-200 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Panduan Order IPSRS</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Step 1 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <span
                                    class="flex items-center justify-center bg-blue-300 text-white rounded-full w-6 h-6 mr-2 text-sm">1</span>
                                Akses Halaman IPSRS
                            </h3>
                            <p class="text-gray-600 mb-2">Untuk membuat order perbaikan sarana, pilih menu "Administrasi
                                Umum" pada sidebar.</p>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <span
                                    class="flex items-center justify-center bg-blue-300 text-white rounded-full w-6 h-6 mr-2 text-sm">2</span>
                                Detail Perbaikan
                            </h3>
                            <p class="text-gray-600 mb-3">Lengkapi informasi perbaikan yang dibutuhkan:</p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-600">
                                <li><span class="font-medium text-gray-700">Jenis Perbaikan:</span> Fasilitas,
                                    peralatan, atau infrastruktur</li>
                                <li><span class="font-medium text-gray-700">Lokasi:</span> Tentukan lokasi
                                    barang/fasilitas</li>
                                <li><span class="font-medium text-gray-700">Foto:</span> Lampirkan foto kerusakan</li>
                                <li><span class="font-medium text-gray-700">Deskripsi:</span> Jelaskan detail kerusakan
                                </li>
                            </ul>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <span
                                    class="flex items-center justify-center bg-blue-300 text-white rounded-full w-6 h-6 mr-2 text-sm">3</span>
                                Proses Perbaikan
                            </h3>
                            <p class="text-gray-600 mb-2">Tim IPSRS akan menindaklanjuti dan melakukan perbaikan sesuai
                                urgensi.</p>
                            <p class="text-gray-600">Pantau status perbaikan melalui dashboard IPSRS.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Pertanyaan Umum</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-1">Apa perbedaan antara tiket SIRS dan order IPSRS?</h3>
                        <p class="text-gray-600">Tiket SIRS digunakan untuk masalah terkait IT dan sistem informasi,
                            sedangkan order IPSRS untuk perbaikan sarana fisik dan infrastruktur.</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-1">Bagaimana menentukan urgensi tiket/order?</h3>
                        <p class="text-gray-600">Pertimbangkan dampak terhadap operasional dan jumlah pengguna yang
                            terdampak. Semakin besar dampaknya, semakin tinggi urgensinya.</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-1">Berapa lama proses penanganan?</h3>
                        <p class="text-gray-600">Untuk SIRS: 1-24 jam tergantung urgensi. Untuk IPSRS: 1-3 hari kerja
                            tergantung kompleksitas perbaikan.</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-1">Bagaimana jika masalah belum terselesaikan?</h3>
                        <p class="text-gray-600">Anda dapat memilih opsi "Belum Selesai" saat konfirmasi dan memberikan
                            feedback tambahan untuk penanganan lebih lanjut.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection