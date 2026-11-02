@if(auth()->user()->hasPermission('ticket'))
<!-- Edit Pengajuan Modal -->
<div id="ticketSubmissionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Edit Pengajuan Tiket</h3>
            <button type="button" onclick="closeTicketSubmissionModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <form action="{{ route('admin.tickets.update-submission', $ticket) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Masalah</label>
                <textarea name="description" rows="4" disabled class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-gray-600">{{ old('description', $ticket->description) }}</textarea>
                <input type="hidden" name="description" value="{{ $ticket->description }}">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select disabled class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-gray-600">
                        @foreach(($categories ?? collect()) as $cat)
                            @if(old('category_id', $ticket->category_id) == $cat->id)
                                <option selected>{{ $cat->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="category_id" value="{{ $ticket->category_id }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                    <input type="hidden" name="department_id" value="{{ $ticket->department_id }}">
                    <div class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-800">{{ $ticket->department }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <select disabled class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-gray-600">
                        @foreach(($locations ?? collect()) as $loc)
                            @if(old('location_id', $ticket->location_id) == $loc->id)
                                <option selected>{{ $loc->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="location_id" value="{{ $ticket->location_id }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                    <select disabled class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-100 text-gray-600">
                        @foreach(($buildings ?? collect()) as $bld)
                            @if(old('building_id', $ticket->building_id) == $bld->id)
                                <option selected>{{ $bld->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="building_id" value="{{ $ticket->building_id }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas (Urgensi) - dapat diubah</label>
                    <select name="priority" required class="w-full px-4 py-2 rounded-lg border border-gray-300">
                        <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Rendah</option>
                        <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Sedang</option>
                        <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>Tinggi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional)</label>
                    <input type="file" disabled class="w-full text-sm text-gray-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Petugas Penanggung Jawab (bisa lebih dari satu)</label>
                <input type="text" id="ticketPjSearch" placeholder="Cari nama petugas..." class="w-full mb-2 px-4 py-2 rounded-lg border border-gray-300 text-sm" oninput="filterPjOptions('ticketPjSearch','ticketPjSelect')">
                <select id="ticketPjSelect" name="penanggung_jawab_ids[]" multiple size="6" class="w-full px-4 py-2 rounded-lg border border-gray-300">
                    @foreach(($users ?? collect()) as $u)
                        <option value="{{ $u->id }}" {{ in_array($u->id, old('penanggung_jawab_ids', $selectedPjIds ?? [auth()->id()])) ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Tahan Ctrl/Cmd untuk memilih lebih dari satu. Default: 1.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeTicketSubmissionModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Batal</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
<script>
function openTicketSubmissionModal() {
    document.getElementById('ticketSubmissionModal').classList.remove('hidden');
}
function closeTicketSubmissionModal() {
    document.getElementById('ticketSubmissionModal').classList.add('hidden');
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