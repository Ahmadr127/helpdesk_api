{{-- Modal detail tiket milik sendiri. Isi dimuat via AJAX (?modal=1) dari detail-body. --}}
<div id="myticket-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-start justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeMyticketDetail()"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full">
            <div class="sticky top-0 bg-white rounded-t-lg px-6 py-4 border-b border-gray-100 flex justify-between items-center z-10">
                <h3 class="text-lg font-semibold text-gray-800">Detail Tiket Saya</h3>
                <button type="button" onclick="closeMyticketDetail()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div id="myticket-detail-body" class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <p class="text-sm text-gray-500 text-center py-8">Memuat detail...</p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                <a id="myticket-detail-full" href="#" class="text-sm text-blue-600 hover:text-blue-800">Buka halaman penuh</a>
                <button type="button" onclick="closeMyticketDetail()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openMyticketDetail(button) {
    const url = button.getAttribute('data-url');
    const modal = document.getElementById('myticket-detail-modal');
    const body = document.getElementById('myticket-detail-body');
    document.getElementById('myticket-detail-full').href = url;
    body.innerHTML = '<p class="text-sm text-gray-500 text-center py-8">Memuat detail...</p>';
    modal.classList.remove('hidden');
    const sep = url.indexOf('?') === -1 ? '?' : '&';
    fetch(url + sep + 'modal=1', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function (html) { body.innerHTML = html; })
        .catch(function () {
            body.innerHTML = '<p class="text-sm text-red-600 text-center py-8">Gagal memuat detail.</p>';
        });
}
function closeMyticketDetail() {
    document.getElementById('myticket-detail-modal').classList.add('hidden');
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMyticketDetail();
});
</script>
