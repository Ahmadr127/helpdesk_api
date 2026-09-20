@extends('user.layouts.app')

@section('title', 'Buat Order Perbaikan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="max-w-7xl mx-auto py-6">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header - GREEN theme for Maintenance -->
        <div class="bg-green-600 p-5 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center">
                    Buat Order Perbaikan Baru
                </h1>
            </div>
            <a href="{{ route('user.administrasi-umum.order-barang') }}"
               class="inline-flex items-center px-4 py-2 bg-white text-green-600 font-medium rounded-lg hover:bg-green-50 transition-colors shadow">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali
            </a>
        </div>

        @if(session('error'))
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form - separate page (not modal) -->
        <form action="{{ route('user.administrasi-umum.order-perbaikan.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="space-y-6">
                <input type="hidden" name="tanggal" value="{{ $tanggal ?? now()->format('Y-m-d H:i:s') }}">
                <input type="hidden" name="department_id" value="{{ $departmentId ?? '' }}">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Pengaju <span class="text-gray-400">(otomatis)</span></label>
                        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-900">{{ $unitPengajuName ?? '-' }} ({{ $unitPengajuCode ?? '-' }})</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Department ID: {{ $departmentId ?? '-' }} • {{ $user->department ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Proses <span class="text-red-500">*</span></label>
                        <x-searchable-select name="unit_proses_code" :options="$unitProses->mapWithKeys(fn($u)=>[$u->code=>$u->code.' - '.$u->name])->toArray()" :selected="old('unit_proses_code')" placeholder="Cari unit proses..." :required="true" id="unit_proses_code" />
                        @error('unit_proses_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Inventaris</label>
                        <input type="text" name="kode_inventaris" value="{{ old('kode_inventaris') }}" placeholder="Kode inventaris (opsional)"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @error('kode_inventaris') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas <span class="text-red-500">*</span></label>
                        <select name="prioritas" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <option value="">Pilih Prioritas</option>
                            <option value="RENDAH" {{ old('prioritas')=='RENDAH' ? 'selected':'' }}>RENDAH</option>
                            <option value="SEDANG" {{ old('prioritas')=='SEDANG' ? 'selected':'' }}>SEDANG</option>
                            <option value="TINGGI/URGENT" {{ old('prioritas')=='TINGGI/URGENT' ? 'selected':'' }}>TINGGI/URGENT</option>
                        </select>
                        @error('prioritas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_barang" value="{{ old('nama_barang') }}" required placeholder="Nama barang / peralatan"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @error('nama_barang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Order</label>
                        <select name="kategori_order" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <option value="">Pilih Kategori</option>
                            @foreach($kategoriOrders as $kat)
                                <option value="{{ $kat->name }}" {{ old('kategori_order')==$kat->name ? 'selected':'' }}>{{ $kat->name }}</option>
                            @endforeach
                        </select>
                        @error('kategori_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi <span class="text-red-500">*</span></label>
                        @if(isset($departmentLocation) && $departmentLocation)
                        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">
                                    {{ $departmentLocation->name }}
                                </p>
                                <p class="text-xs text-gray-500">Otomatis dari departemen Anda</p>
                            </div>
                        </div>
                        <input type="hidden" name="lokasi" value="{{ $departmentLocation->id }}">
                        @else
                        <div class="relative">
                            <input type="text" id="lokasi_search"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Ketik untuk cari lokasi..." autocomplete="off"
                                   value="{{ old('lokasi') ? ($locations->firstWhere('id', old('lokasi'))->name ?? '') : '' }}">
                            <div id="lokasi_results" class="absolute z-20 w-full mt-1 bg-white shadow-lg rounded-lg border border-gray-200 hidden max-h-60 overflow-y-auto"></div>
                            <select name="lokasi" id="lokasi_select" required class="hidden">
                                <option value="">Pilih Lokasi</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('lokasi')==$loc->id ? 'selected':'' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @error('lokasi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900" id="gedung_display">
                                    {{ $departmentBuilding?->name ?? '-' }}
                                </p>
                                @if(isset($departmentBuilding) && $departmentBuilding)
                                <p class="text-xs text-gray-500">Otomatis dari departemen</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keluhan / Deskripsi Kerusakan <span class="text-red-500">*</span></label>
                        <textarea name="keluhan" rows="4" required placeholder="Jelaskan keluhan / kerusakan..."
                                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('keluhan') }}</textarea>
                        @error('keluhan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional, max 10MB)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-green-400 transition-colors" id="dropZone">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="foto" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500">
                                        <span>Upload foto</span>
                                        <input id="foto" name="foto" type="file" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="pl-1">atau drag & drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                <p id="file-name" class="text-sm text-green-600 font-medium hidden"></p>
                                <div id="preview-container" class="hidden mt-3">
                                    <img id="preview-image" src="#" alt="Preview" class="w-24 h-24 object-cover rounded-lg mx-auto border">
                                </div>
                            </div>
                        </div>
                        @error('foto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t">
                <a href="{{ route('user.administrasi-umum.order-barang') }}"
                   class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-8 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200 transition-colors shadow">
                    Simpan Order
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Lokasi autocomplete
    const lokasiSearch = document.getElementById('lokasi_search');
    const lokasiResults = document.getElementById('lokasi_results');
    const lokasiSelect = document.getElementById('lokasi_select');
    if(lokasiSearch && lokasiResults && lokasiSelect){
        const locations = [];
        for(const opt of lokasiSelect.options){
            if(opt.value) locations.push({id: opt.value, name: opt.text});
        }
        // set initial if old value
        if(lokasiSelect.value){
            const sel = locations.find(l=>l.id==lokasiSelect.value);
            if(sel){
                lokasiSearch.value = sel.name;
            }
        }
        function display(results){
            lokasiResults.innerHTML='';
            if(results.length>0){
                results.forEach(loc=>{
                    const div=document.createElement('div');
                    div.className='px-4 py-2 cursor-pointer hover:bg-green-50 text-sm';
                    div.textContent=loc.name;
                    div.addEventListener('click',()=>{
                        lokasiSearch.value=loc.name;
                        lokasiSelect.value=loc.id;
                        lokasiResults.classList.add('hidden');
                    });
                    lokasiResults.appendChild(div);
                });
                lokasiResults.classList.remove('hidden');
            } else {
                lokasiResults.classList.add('hidden');
            }
        }
        lokasiSearch.addEventListener('input', function(){
            const term=this.value.toLowerCase();
            const filtered=locations.filter(l=>l.name.toLowerCase().includes(term));
            display(filtered);
        });
        lokasiSearch.addEventListener('focus', ()=>display(locations));
        document.addEventListener('click', e=>{
            if(e.target!==lokasiSearch && e.target!==lokasiResults){
                lokasiResults.classList.add('hidden');
            }
        });
    }

    // Foto preview
    const fotoInput=document.getElementById('foto');
    const previewContainer=document.getElementById('preview-container');
    const previewImage=document.getElementById('preview-image');
    const fileName=document.getElementById('file-name');
    if(fotoInput){
        fotoInput.addEventListener('change', function(){
            if(this.files && this.files[0]){
                const file=this.files[0];
                fileName.textContent=file.name+' ('+(file.size/1024/1024).toFixed(2)+' MB)';
                fileName.classList.remove('hidden');
                const reader=new FileReader();
                reader.onload=e=>{
                    previewImage.src=e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
        const dropZone=document.getElementById('dropZone');
        if(dropZone){
            ['dragenter','dragover'].forEach(ev=>dropZone.addEventListener(ev, e=>{e.preventDefault(); dropZone.classList.add('border-green-500','bg-green-50');}));
            ['dragleave','drop'].forEach(ev=>dropZone.addEventListener(ev, e=>{e.preventDefault(); dropZone.classList.remove('border-green-500','bg-green-50');}));
            dropZone.addEventListener('drop', e=>{
                const file=e.dataTransfer.files[0];
                if(file && file.type.startsWith('image/')){
                    fotoInput.files=e.dataTransfer.files;
                    fotoInput.dispatchEvent(new Event('change'));
                }
            });
        }
    }
});
</script>
@endpush
@endsection
