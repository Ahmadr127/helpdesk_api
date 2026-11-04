@if(auth()->user()->hasPermission('order'))
<!-- Edit Pengajuan Modal -->
<div id="orderSubmissionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Edit Pengajuan Order</h3>
            <button type="button" onclick="closeOrderSubmissionModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <form action="{{ route('admin.order-perbaikan.update-submission', $orderPerbaikan) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keluhan / Deskripsi Kerusakan</label>
                <textarea name="keluhan" rows="4" disabled class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-100">{{ old('keluhan', $orderPerbaikan->keluhan) }}</textarea>
                @error('keluhan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Order</label>
                    <select name="kategori_order" disabled class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-100">
                        <option value="">- Pilih kategori -</option>
                        @foreach(($kategoriOrders ?? collect()) as $kategori)
                            <option value="{{ $kategori->name }}" {{ old('kategori_order', $orderPerbaikan->kategori_order) === $kategori->name ? 'selected' : '' }}>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <select name="lokasi" disabled class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-100">
                        <option value="">- Pilih lokasi -</option>
                        @foreach(($locations ?? collect()) as $lokasi)
                            <option value="{{ $lokasi->id }}" {{ (string) old('lokasi', $orderPerbaikan->lokasi) === (string) $lokasi->id ? 'selected' : '' }}>
                                {{ $lokasi->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('lokasi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas (Urgensi)</label>
                    <select name="prioritas" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <option value="RENDAH" {{ old('prioritas', $orderPerbaikan->prioritas) == 'RENDAH' ? 'selected' : '' }}>RENDAH</option>
                        <option value="SEDANG" {{ old('prioritas', $orderPerbaikan->prioritas) == 'SEDANG' ? 'selected' : '' }}>SEDANG</option>
                        <option value="TINGGI/URGENT" {{ old('prioritas', $orderPerbaikan->prioritas) == 'TINGGI/URGENT' ? 'selected' : '' }}>TINGGI/URGENT</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional)</label>
                    <x-image-upload name="fotos[]" accent="green" :max="3" :max-size="2"
                        accept="image/jpeg,image/png,image/jpg,image/gif"
                        :existing="$orderPerbaikan->foto_urls"
                        existing-label="Foto order saat ini" />
                    @error('foto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('fotos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('fotos.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Petugas Penanggung Jawab (bisa lebih dari satu)</label>
                <input type="text" id="orderPjSearch" disabled placeholder="Cari nama petugas..." class="w-full mb-2 px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-100">
                <select id="orderPjSelect" name="penanggung_jawab_ids[]" multiple size="6" disabled class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-100">
                    @foreach(($users ?? collect()) as $u)
                        <option value="{{ $u->id }}" {{ in_array($u->id, old('penanggung_jawab_ids', $selectedPjIds ?? [auth()->id()])) ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Tahan Ctrl/Cmd untuk memilih lebih dari satu. Default: 1.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeOrderSubmissionModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Batal</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
<script>
function openOrderSubmissionModal() {
    document.getElementById('orderSubmissionModal').classList.remove('hidden');
}
function closeOrderSubmissionModal() {
    document.getElementById('orderSubmissionModal').classList.add('hidden');
}
function filterPjOptions(inputId, selectId) {
    var q = (document.getElementById(inputId).value || '').toLowerCase();
    var opts = document.getElementById(selectId).options;
    for (var i = 0; i < opts.length; i++) {
        var txt = (opts[i].text || '').toLowerCase();
        opts[i].style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
    }
}
</script>
@endif