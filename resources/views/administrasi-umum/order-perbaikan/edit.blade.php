@extends('administrasi-umum.layouts.app')

@section('title', 'Edit Order Perbaikan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- Header with back button -->
        <div
            class="bg-gradient-to-r from-blue-200 to-white -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200 rounded-t-lg">
            <div class="flex items-center">
                <a href="{{ route('administrasi-umum.order-perbaikan.index') }}"
                    class="mr-4 text-gray-700 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h2 class="text-2xl font-bold text-gray-700">Edit Order Perbaikan</h2>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Edit Order Perbaikan</h1>

            <form action="{{ route('administrasi-umum.order-perbaikan.update', $orderPerbaikan) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="nomor">
                                Nomor Order
                            </label>
                            <input type="text" name="nomor" id="nomor"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('nomor') border-red-500 @enderror"
                                value="{{ old('nomor', $orderPerbaikan->nomor) }}" readonly>
                            @error('nomor')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="tanggal">
                                Tanggal
                            </label>
                            <input type="date" name="tanggal" id="tanggal"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('tanggal') border-red-500 @enderror"
                                value="{{ old('tanggal', $orderPerbaikan->tanggal->format('Y-m-d')) }}" required>
                            @error('tanggal')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="unit_proses">
                                Unit Proses
                            </label>
                            <select name="unit_proses" id="unit_proses"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('unit_proses') border-red-500 @enderror"
                                required>
                                <option value="">Pilih Unit Proses</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ old('unit_proses', $orderPerbaikan->unit_proses) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('unit_proses')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="unit_penerima">
                                Unit Penerima
                            </label>
                            <select name="unit_penerima" id="unit_penerima"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('unit_penerima') border-red-500 @enderror"
                                required>
                                <option value="">Pilih Unit Penerima</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ old('unit_penerima', $orderPerbaikan->unit_penerima) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('unit_penerima')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="no_penerima">
                                No. Penerima
                            </label>
                            <input type="text" name="no_penerima" id="no_penerima"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('no_penerima') border-red-500 @enderror"
                                value="{{ old('no_penerima', $orderPerbaikan->no_penerima) }}" required>
                            @error('no_penerima')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="jenis_barang">
                                Jenis Barang
                            </label>
                            <select name="jenis_barang" id="jenis_barang"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('jenis_barang') border-red-500 @enderror"
                                required>
                                <option value="">Pilih Jenis Barang</option>
                                <option value="Inventaris"
                                    {{ old('jenis_barang', $orderPerbaikan->jenis_barang) == 'Inventaris' ? 'selected' : '' }}>
                                    Inventaris</option>
                                <option value="Non-Inventaris"
                                    {{ old('jenis_barang', $orderPerbaikan->jenis_barang) == 'Non-Inventaris' ? 'selected' : '' }}>
                                    Non-Inventaris</option>
                            </select>
                            @error('jenis_barang')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="kode_inventaris">
                                Kode Inventaris
                            </label>
                            <input type="text" name="kode_inventaris" id="kode_inventaris"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('kode_inventaris') border-red-500 @enderror"
                                value="{{ old('kode_inventaris', $orderPerbaikan->kode_inventaris) }}" required>
                            @error('kode_inventaris')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_barang">
                                Nama Barang
                            </label>
                            <input type="text" name="nama_barang" id="nama_barang"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('nama_barang') border-red-500 @enderror"
                                value="{{ old('nama_barang', $orderPerbaikan->nama_barang) }}" required>
                            @error('nama_barang')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="lokasi">
                                Lokasi
                            </label>
                            <input type="text" name="lokasi" id="lokasi"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('lokasi') border-red-500 @enderror"
                                value="{{ old('lokasi', $orderPerbaikan->lokasi) }}" required>
                            @error('lokasi')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="prioritas">
                                Prioritas
                            </label>
                            <select name="prioritas" id="prioritas"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('prioritas') border-red-500 @enderror"
                                required>
                                <option value="RENDAH"
                                    {{ old('prioritas', $orderPerbaikan->prioritas) == 'RENDAH' ? 'selected' : '' }}>
                                    RENDAH</option>
                                <option value="SEDANG"
                                    {{ old('prioritas', $orderPerbaikan->prioritas) == 'SEDANG' ? 'selected' : '' }}>
                                    SEDANG</option>
                                <option value="TINGGI/URGENT"
                                    {{ old('prioritas', $orderPerbaikan->prioritas) == 'TINGGI/URGENT' ? 'selected' : '' }}>
                                    TINGGI/URGENT</option>
                            </select>
                            @error('prioritas')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="keluhan">
                        Keluhan
                    </label>
                    <textarea name="keluhan" id="keluhan" rows="4"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('keluhan') border-red-500 @enderror"
                        required>{{ old('keluhan', $orderPerbaikan->keluhan) }}</textarea>
                    @error('keluhan')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end mt-6">
                    <button type="button" onclick="window.history.back()"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2">
                        Batal
                    </button>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection