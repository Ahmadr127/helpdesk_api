@props([
    'name' => 'photos[]',
    'id' => null,
    'max' => 3,
    'maxSize' => 5,
    'accept' => 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
    'accent' => 'blue',
    'existing' => [],
    'existingLabel' => 'Foto saat ini',
    'hint' => null,
    'required' => false,
    'compress' => true,
    'compressThreshold' => 1.5,
    'maxDim' => 1600,
    'quality' => 0.82,
])

@php
    $uid = $id ?? 'img-upload-' . uniqid();
    $accent = in_array($accent, ['blue', 'green'], true) ? $accent : 'blue';
    $accentText = $accent === 'green' ? 'text-green-600' : 'text-blue-600';
    $accentBorder = $accent === 'green' ? 'hover:border-green-400' : 'hover:border-blue-400';
    $accentRing = $accent === 'green' ? 'focus:ring-green-500' : 'focus:ring-blue-500';

    $existing = is_array($existing) ? array_values(array_filter($existing)) : ($existing ? (array) $existing : []);

    $hintText = $hint ?? ('JPG, PNG, atau GIF · maks ' . $max . ' foto · maks ' . $maxSize . 'MB/foto' . ($compress ? ' · otomatis dikompresi' : ''));
@endphp

<div class="image-upload"
    data-image-upload
    data-accent="{{ $accent }}"
    data-max="{{ $max }}"
    data-max-size="{{ $maxSize }}"
    data-compress="{{ $compress ? '1' : '0' }}"
    data-compress-threshold="{{ $compressThreshold }}"
    data-max-dim="{{ $maxDim }}"
    data-quality="{{ $quality }}"
    data-accept="{{ $accept }}">

    <input type="file" name="{{ $name }}" id="{{ $uid }}" accept="{{ $accept }}" multiple
        class="hidden" data-image-upload-input @if($required) required @endif>

    <div data-image-upload-dropzone tabindex="0" role="button" aria-label="Pilih foto"
        class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-6 text-center transition-colors hover:bg-white {{ $accentBorder }} focus:outline-none focus:ring-2 {{ $accentRing }}">
        <div class="mb-2 flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-sm">
            <svg class="h-5 w-5 {{ $accentText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        <p class="text-sm text-gray-700">
            <span class="font-semibold {{ $accentText }}">Pilih foto</span>
            <span class="text-gray-400">atau seret &amp; lepas ke sini</span>
        </p>
        <p class="mt-1 text-xs text-gray-500">{{ $hintText }}</p>
        <div data-image-upload-busy
            class="absolute inset-0 flex hidden items-center justify-center rounded-xl bg-white text-sm font-semibold {{ $accentText }}">
            Memproses foto&hellip;
        </div>
    </div>

    <p data-image-upload-summary class="mt-2 text-xs text-gray-500">Belum ada foto dipilih (maks {{ $max }}).</p>
    <p data-image-upload-error class="mt-1 hidden text-xs font-medium text-red-600"></p>

    <div data-image-upload-previews class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5"></div>

    @if(!empty($existing))
        <div class="mt-4">
            <p class="mb-2 text-xs font-medium text-gray-500">{{ $existingLabel }}</p>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
                @foreach($existing as $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener"
                        class="block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <img src="{{ $url }}" alt="" class="h-24 w-full object-cover">
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    if (window.__imageUploadInit) return;
    window.__imageUploadInit = true;

    function fmtSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
        return bytes + ' B';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function compressImage(file, maxDim, quality) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function (event) {
                var img = new Image();
                img.onload = function () {
                    try {
                        var width = img.naturalWidth || img.width;
                        var height = img.naturalHeight || img.height;
                        if (!width || !height) { reject(new Error('dimensi')); return; }
                        var scale = Math.min(1, maxDim / Math.max(width, height));
                        width = Math.max(1, Math.round(width * scale));
                        height = Math.max(1, Math.round(height * scale));
                        var canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        var ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, width, height);
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob(function (blob) {
                            blob ? resolve(blob) : reject(new Error('toBlob'));
                        }, 'image/jpeg', quality);
                    } catch (err) { reject(err); }
                };
                img.onerror = function () { reject(new Error('load')); };
                img.src = event.target.result;
            };
            reader.onerror = function () { reject(new Error('read')); };
            reader.readAsDataURL(file);
        });
    }

    function initImageUpload(root) {
        if (root.__imageUpload) return;

        var input = root.querySelector('[data-image-upload-input]');
        var dropzone = root.querySelector('[data-image-upload-dropzone]');
        var previewsEl = root.querySelector('[data-image-upload-previews]');
        var summaryEl = root.querySelector('[data-image-upload-summary]');
        var errorEl = root.querySelector('[data-image-upload-error]');
        var busyEl = root.querySelector('[data-image-upload-busy]');
        if (!input || !dropzone || !previewsEl) return;

        var accent = root.getAttribute('data-accent') === 'green' ? 'green' : 'blue';
        var dragClasses = accent === 'green'
            ? ['border-green-500', 'bg-green-50']
            : ['border-blue-500', 'bg-blue-50'];

        var opts = {
            max: parseInt(root.getAttribute('data-max') || '3', 10),
            maxSize: parseFloat(root.getAttribute('data-max-size') || '5'),
            compress: root.getAttribute('data-compress') !== '0',
            threshold: parseFloat(root.getAttribute('data-compress-threshold') || '1.5') * 1048576,
            maxDim: parseInt(root.getAttribute('data-max-dim') || '1600', 10),
            quality: parseFloat(root.getAttribute('data-quality') || '0.82'),
            accept: (root.getAttribute('data-accept') || 'image/*').split(',').map(function (s) { return s.trim(); })
        };

        var state = { files: [], busy: false };
        root.__imageUpload = { root: root, input: input, opts: opts, state: state, sync: sync };

        function allowedType(file) {
            if (!file.type || file.type.indexOf('image/') !== 0) return false;
            if (opts.accept.indexOf('image/*') !== -1) return true;
            if (opts.accept.indexOf(file.type) !== -1) return true;
            if (file.type === 'image/jpeg' && opts.accept.indexOf('image/jpg') !== -1) return true;
            return false;
        }

        function showError(message) {
            if (!errorEl) return;
            errorEl.textContent = message || '';
            errorEl.classList.toggle('hidden', !message);
        }

        function setBusy(flag) {
            state.busy = flag;
            if (busyEl) {
                busyEl.classList.toggle('hidden', !flag);
                busyEl.classList.toggle('flex', flag);
            }
        }

        function sync() {
            try {
                var dt = new DataTransfer();
                state.files.forEach(function (file) { dt.items.add(file); });
                input.files = dt.files;
            } catch (err) {
                // Browser lama tanpa DataTransfer: berkas tetap terkirim dari input asli.
            }
        }

        function summarize() {
            if (!summaryEl) return;
            var count = state.files.length;
            if (!count) {
                summaryEl.textContent = 'Belum ada foto dipilih (maks ' + opts.max + ').';
                return;
            }
            var total = state.files.reduce(function (sum, file) { return sum + file.size; }, 0);
            summaryEl.textContent = count + ' / ' + opts.max + ' foto dipilih · ' + fmtSize(total);
        }

        function render() {
            previewsEl.innerHTML = '';
            state.files.forEach(function (file, index) {
                var url = URL.createObjectURL(file);
                var card = document.createElement('div');
                card.className = 'relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm';
                card.innerHTML =
                    '<img src="' + url + '" alt="" class="h-24 w-full object-cover">' +
                    '<button type="button" title="Hapus foto" class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white font-semibold text-gray-700 shadow hover:bg-red-50 hover:text-red-600">&times;</button>' +
                    '<div class="truncate px-2 pt-1 text-xs text-gray-600" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</div>' +
                    '<div class="px-2 pb-1 text-xs text-gray-400">' + fmtSize(file.size) + '</div>';
                var img = card.querySelector('img');
                img.addEventListener('load', function () { URL.revokeObjectURL(url); });
                card.querySelector('button').addEventListener('click', function () {
                    state.files.splice(index, 1);
                    sync();
                    render();
                });
                previewsEl.appendChild(card);
            });
            summarize();
        }

        function addFiles(list) {
            var incoming = Array.prototype.slice.call(list || []).filter(function (file) {
                return file && file.size > 0;
            });
            if (!incoming.length) return;

            var remaining = opts.max - state.files.length;
            if (remaining <= 0) {
                showError('Maksimal ' + opts.max + ' foto. Hapus salah satu untuk menambah yang baru.');
                return;
            }

            var limited = false;
            if (incoming.length > remaining) {
                incoming = incoming.slice(0, remaining);
                limited = true;
            }

            var accepted = [];
            var typeError = null;
            incoming.forEach(function (file) {
                if (!allowedType(file)) {
                    if (!typeError) typeError = 'Format tidak didukung: ' + (file.name || 'file') + '. Gunakan JPG, PNG, atau GIF.';
                    return;
                }
                if (!opts.compress && file.size > opts.maxSize * 1048576) {
                    if (!typeError) typeError = 'Ukuran melebihi ' + opts.maxSize + 'MB: ' + (file.name || 'file');
                    return;
                }
                accepted.push(file);
            });

            if (limited) {
                showError('Maksimal ' + opts.max + ' foto. Hanya ' + remaining + ' foto pertama yang dipakai.');
            } else {
                showError(typeError);
            }
            if (!accepted.length) return;

            var needsCompression = accepted.some(function (file) {
                return opts.compress && file.size > opts.threshold;
            });

            if (!needsCompression) {
                accepted.forEach(function (file) { state.files.push(file); });
                sync();
                render();
                return;
            }

            setBusy(true);
            Promise.all(accepted.map(function (file) {
                if (opts.compress && file.size > opts.threshold) {
                    return compressImage(file, opts.maxDim, opts.quality).then(function (blob) {
                        var base = (file.name || 'foto').replace(/\.[^.]+$/, '') || 'foto';
                        return new File([blob], base + '.jpg', { type: 'image/jpeg' });
                    }).catch(function () { return file; });
                }
                return Promise.resolve(file);
            })).then(function (files) {
                files.forEach(function (file) { state.files.push(file); });
                setBusy(false);
                sync();
                render();
            });
        }

        input.setAttribute('multiple', 'multiple');

        dropzone.addEventListener('click', function () {
            if (!state.busy) input.click();
        });
        dropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (!state.busy) input.click();
            }
        });

        input.addEventListener('change', function () {
            var selected = Array.prototype.slice.call(input.files || []);
            input.value = '';
            addFiles(selected);
        });

        ['dragenter', 'dragover'].forEach(function (ev) {
            dropzone.addEventListener(ev, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dragClasses.forEach(function (cls) { dropzone.classList.add(cls); });
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            dropzone.addEventListener(ev, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dragClasses.forEach(function (cls) { dropzone.classList.remove(cls); });
            });
        });
        dropzone.addEventListener('drop', function (event) {
            addFiles(event.dataTransfer ? event.dataTransfer.files : null);
        });

        var form = root.closest('form');
        if (form) {
            form.addEventListener('reset', function () {
                state.files = [];
                showError('');
                sync();
                render();
            });
        }

        summarize();
    }

    // Cegah submit saat foto masih diproses (kompresi berjalan).
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.querySelectorAll) return;
        var busy = false;
        Array.prototype.forEach.call(form.querySelectorAll('[data-image-upload]'), function (el) {
            if (el.__imageUpload && el.__imageUpload.state.busy) busy = true;
        });
        if (busy) {
            event.preventDefault();
            event.stopPropagation();
            alert('Mohon tunggu sebentar, foto sedang diproses.');
        }
    }, true);

    function initAll() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-image-upload]'), initImageUpload);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
</script>
@endpush
@endonce
