{{-- Modal konfirmasi hapus order milik sendiri. Dipicu via openMyticketDelete(button) dengan data-delete-url. --}}
<div id="myticket-delete-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeMyticketDelete()"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-800">Hapus order?</h3>
            <p class="text-sm text-gray-500 mt-2">Hanya order dengan status open yang bisa dihapus. Tindakan ini tidak bisa dibatalkan.</p>
            <form id="myticket-delete-form" method="POST" class="mt-6 flex justify-end space-x-3">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeMyticketDelete()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors text-sm">
                    Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openMyticketDelete(button) {
    document.getElementById('myticket-delete-form').action = button.getAttribute('data-delete-url');
    document.getElementById('myticket-delete-modal').classList.remove('hidden');
}
function closeMyticketDelete() {
    document.getElementById('myticket-delete-modal').classList.add('hidden');
}
</script>
