<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | Helpdesk RS Azra</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">404 — Halaman Tidak Ditemukan</h1>
        <p class="text-sm text-gray-500 mt-2">
            {{ $exception->getMessage() ?: 'Halaman yang Anda cari tidak ditemukan.' }}
        </p>
        <div class="mt-6 flex gap-3 justify-center">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Ke Dashboard</a>
            <a href="{{ url()->previous() }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">Kembali</a>
        </div>
    </div>
</body>
</html>
