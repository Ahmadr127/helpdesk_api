@extends('admin.layouts.app')

@section('title', 'Monitoring FCM & Jobs')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Monitoring Manual</h2>
        <p class="text-sm text-gray-500">Logs • Jobs • FCM • Notifikasi — endpoint <code>/fcm</code> • API <code>/api/monitoring/*</code></p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('fcm.index', ['tab'=>'overview','format'=>'json']) }}" target="_blank" class="px-3 py-2 text-xs bg-gray-800 text-white rounded hover:bg-black">JSON API</a>
        <a href="{{ route('api.monitoring.overview') }}" target="_blank" class="px-3 py-2 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">/api/monitoring/overview</a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ session('error') }}</div>
@endif

{{-- Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
        @php $tabs = ['overview'=>'Overview','logs'=>'Logs','jobs'=>'Jobs','fcm'=>'FCM','notifications'=>'Notifikasi']; @endphp
        @foreach($tabs as $key=>$label)
            <a href="{{ route('fcm.index', ['tab'=>$key]) }}"
               class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 {{ $tab===$key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>

@if($tab==='overview')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-sm">Queue</h3></div>
            <div class="p-4">
                <div class="text-2xl font-bold">{{ $jobs['pending'] ?? 0 }} <span class="text-sm font-normal text-gray-500">pending</span></div>
                <div class="text-xs text-gray-500">Connection: <code>{{ $queue_connection }}</code> • Env: {{ $env }}</div>
                <div class="mt-2 text-xs">By queue: @foreach(($jobs['pending_by_queue']??[]) as $q=>$c) <span class="px-2 py-1 bg-gray-100 rounded mr-1">{{ $q }}:{{ $c }}</span> @endforeach</div>
                <div class="mt-2 text-sm">Failed: <span class="font-bold text-red-600">{{ $jobs['failed'] ?? 0 }}</span> • Batches: {{ $jobs['batches'] ?? 0 }}</div>
                <div class="mt-2 text-xs">Notif queue pending: {{ $fcm['notif_pending'] ?? 0 }} • failed: {{ $fcm['notif_failed'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-sm">FCM Credential</h3></div>
            <div class="p-4 text-sm">
                <div>Status: @if($fcm['credential_exists']) <span class="text-green-600 font-bold">ADA</span> @else <span class="text-red-600 font-bold">TIDAK ADA</span> @endif</div>
                <div class="text-xs text-gray-500 break-all">Path: {{ $fcm['credential_path'] ?? '-' }}</div>
                <div>Project: <code>{{ $fcm['project_id'] ?? '-' }}</code></div>
                <div>Client: <code class="text-xs">{{ $fcm['client_email'] ?? '-' }}</code></div>
                <div class="mt-2 text-xs">Total tokens: {{ $fcm['total_tokens'] ?? 0 }} • Valid: <span class="text-green-600">{{ $fcm['valid_tokens'] ?? 0 }}</span> • Invalid: <span class="text-red-600">{{ $fcm['invalid_tokens'] ?? 0 }}</span> • Legacy users: {{ $fcm['legacy_users_with_token'] ?? 0 }}</div>
                <div class="text-xs">By platform: @foreach(($fcm['by_platform']??[]) as $p=>$c) <span class="px-2 py-1 bg-blue-50 rounded mr-1">{{ $p }}:{{ $c }}</span> @endforeach</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-sm">Notifikasi DB</h3></div>
            <div class="p-4">
                <div class="text-2xl font-bold">{{ $notifications['total'] ?? 0 }} <span class="text-sm font-normal text-gray-500">total</span></div>
                <div class="text-sm">Unread: <span class="font-bold text-orange-600">{{ $notifications['unread'] ?? 0 }}</span> • Read: {{ $notifications['read'] ?? 0 }}</div>
                <div class="mt-2 text-xs">By type: @foreach(($notifications['by_type']??[]) as $t=>$c) <span class="px-2 py-1 bg-purple-50 rounded mr-1">{{ $t }}:{{ $c }}</span> @endforeach</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-sm">Logs</h3></div>
            <div class="p-4 text-xs">
                <div>Laravel: {{ $logs['laravel']['exists'] ? number_format($logs['laravel']['size']/1024,1).' KB' : 'tidak ada' }} • {{ count($logs['laravel']['lines'] ?? []) }} lines preview</div>
                <div>Firebase: {{ $logs['firebase']['exists'] ? number_format($logs['firebase']['size']/1024,1).' KB' : 'tidak ada' }} • {{ count($logs['firebase']['lines'] ?? []) }} lines</div>
                <div class="mt-2">Daily firebase files: {{ $fcm['daily_log_files'] ?? 0 }}</div>
                <div class="mt-2"><a href="{{ route('fcm.index',['tab'=>'logs','channel'=>'firebase','lines'=>200]) }}" class="text-blue-600 underline">Lihat logs →</a></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card">
            <div class="card-header flex justify-between items-center"><h3 class="font-semibold text-sm">Recent Jobs (5)</h3><a href="{{ route('fcm.index',['tab'=>'jobs']) }}" class="text-xs text-blue-600">Detail</a></div>
            <div class="p-4">
                @forelse(($jobs['recent_jobs'] ?? []) as $j)
                    <div class="py-2 border-b border-gray-100 flex justify-between text-xs">
                        <div><div class="font-mono">{{ $j['displayName'] }}</div><div class="text-gray-500">queue: {{ $j['queue'] }} • attempts: {{ $j['attempts'] }}</div></div>
                        <div class="text-gray-400">#{{ $j['id'] }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Tidak ada pending jobs</div>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header flex justify-between items-center"><h3 class="font-semibold text-sm">Recent Notifications (10)</h3><a href="{{ route('fcm.index',['tab'=>'notifications']) }}" class="text-xs text-blue-600">Detail</a></div>
            <div class="p-4">
                @forelse(($notifications['recent'] ?? []) as $n)
                    <div class="py-2 border-b border-gray-100 text-xs">
                        <div class="font-medium">{{ $n['title'] }} <span class="text-gray-400">({{ $n['type'] }})</span></div>
                        <div class="text-gray-500">To #{{ $n['notifiable_id'] }} • {{ $n['created_at'] }} • @if($n['read_at']) <span class="text-green-600">read</span> @else <span class="text-orange-600">unread</span> @endif</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Belum ada notifikasi</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 card p-4">
        <h3 class="font-semibold text-sm mb-2">Queue Worker Command (manual, tanpa Horizon)</h3>
        <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto">php artisan queue:work --queue=notifications --tries=3 --timeout=120 --sleep=3

# cek
php artisan queue:failed
php artisan queue:retry all
tail -f storage/logs/firebase.log
tail -f storage/logs/laravel.log</pre>
        <div class="mt-3">
            <form method="POST" action="{{ route('fcm.test') }}" class="flex flex-col md:flex-row gap-2 items-end">
                @csrf
                <div class="flex-1">
                    <label class="text-xs text-gray-600">Test FCM ke token (kosong = pakai token user login)</label>
                    <input type="text" name="token" placeholder="FCM token manual (optional)" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div class="flex-1">
                    <label class="text-xs text-gray-600">Title</label>
                    <input type="text" name="title" value="Test FCM Manual" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div class="flex-1">
                    <label class="text-xs text-gray-600">Body</label>
                    <input type="text" name="body" value="Hello dari /fcm monitoring" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Kirim Test</button>
            </form>
        </div>
    </div>
@endif

@if($tab==='logs')
    <div class="card mb-4">
        <div class="card-header flex flex-col md:flex-row md:items-center justify-between gap-3">
            <h3 class="font-semibold text-sm">Logs Viewer</h3>
            <form method="GET" action="{{ route('fcm.logs') }}" class="flex gap-2 items-center">
                <select name="channel" class="border rounded px-2 py-1 text-sm">
                    <option value="laravel" {{ ($logChannel ?? 'laravel')==='laravel' ? 'selected' : '' }}>laravel.log</option>
                    <option value="firebase" {{ ($logChannel ?? '')==='firebase' ? 'selected' : '' }}>firebase.log</option>
                    <option value="firebase-daily" {{ ($logChannel ?? '')==='firebase-daily' ? 'selected' : '' }}>firebase daily</option>
                </select>
                <select name="lines" class="border rounded px-2 py-1 text-sm">
                    <option value="50" {{ ($logLines ?? 200)==50 ? 'selected' : '' }}>50</option>
                    <option value="200" {{ ($logLines ?? 200)==200 ? 'selected' : '' }}>200</option>
                    <option value="500" {{ ($logLines ?? 200)==500 ? 'selected' : '' }}>500</option>
                    <option value="1000" {{ ($logLines ?? 200)==1000 ? 'selected' : '' }}>1000</option>
                </select>
                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Lihat</button>
            </form>
            <form method="POST" action="{{ route('fcm.logs.clear') }}" onsubmit="return confirm('Clear log {{ $logChannel ?? 'laravel' }}?')">
                @csrf
                <input type="hidden" name="channel" value="{{ $logChannel ?? 'laravel' }}">
                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-sm">Clear</button>
            </form>
        </div>
        <div class="p-4">
            @if(isset($logDetail))
                <div class="text-xs text-gray-500 mb-2">Path: <code class="break-all">{{ $logDetail['path'] }}</code> • Size: {{ number_format($logDetail['size']/1024,1) }} KB • Exists: {{ $logDetail['exists'] ? 'ya' : 'tidak' }}</div>
                <pre class="bg-gray-900 text-green-300 p-3 rounded text-xs overflow-auto max-h-[60vh] whitespace-pre-wrap break-words">{{ implode("\n", $logDetail['lines']) }}</pre>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-xs mb-2">laravel.log (50)</h4>
                        <pre class="bg-gray-900 text-white p-3 rounded text-xs overflow-auto max-h-[40vh] whitespace-pre-wrap">{{ implode("\n", array_slice($logs['laravel']['lines'] ?? [], -30)) }}</pre>
                    </div>
                    <div>
                        <h4 class="font-semibold text-xs mb-2">firebase.log (50)</h4>
                        <pre class="bg-gray-900 text-green-300 p-3 rounded text-xs overflow-auto max-h-[40vh] whitespace-pre-wrap">{{ implode("\n", array_slice($logs['firebase']['lines'] ?? [], -30)) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="text-xs text-gray-500">Tips: gunakan <code>tail -f storage/logs/laravel.log</code> di server untuk realtime tanpa Horizon.</div>
@endif

@if($tab==='jobs')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-4 text-center"><div class="text-2xl font-bold">{{ $jobs['pending'] ?? 0 }}</div><div class="text-xs text-gray-500">Pending Jobs</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-red-600">{{ $jobs['failed'] ?? 0 }}</div><div class="text-xs text-gray-500">Failed Jobs</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold">{{ $jobs['batches'] ?? 0 }}</div><div class="text-xs text-gray-500">Batches</div></div>
    </div>

    <div class="card mb-6">
        <div class="card-header flex justify-between items-center">
            <h3 class="font-semibold text-sm">Pending Jobs (queue: notifications + default)</h3>
            <form method="GET" class="text-xs"><button type="submit" class="px-2 py-1 bg-gray-100 rounded">Refresh</button></form>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">ID</th><th>Queue</th><th>Job</th><th>Attempts</th><th>Available</th><th>Created</th></tr></thead>
                <tbody>
                @forelse(($jobs['recent_jobs'] ?? []) as $j)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 font-mono">{{ $j['id'] }}</td>
                        <td><span class="px-2 py-1 bg-blue-50 rounded">{{ $j['queue'] }}</span></td>
                        <td class="max-w-[300px] truncate">{{ $j['displayName'] }}</td>
                        <td>{{ $j['attempts'] }}</td>
                        <td>{{ date('H:i:s', $j['available_at']) }}</td>
                        <td>{{ date('d/m H:i', $j['created_at']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-gray-500">Tidak ada pending jobs</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-gray-50 text-xs">
            Jalankan worker: <code class="bg-gray-900 text-green-400 px-2 py-1 rounded">php artisan queue:work --queue=notifications --tries=3 --timeout=120</code>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex justify-between items-center">
            <h3 class="font-semibold text-sm">Failed Jobs (10 terbaru)</h3>
            <form method="POST" action="{{ route('fcm.jobs.flushFailed') }}" onsubmit="return confirm('Flush semua failed?')">
                @csrf
                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-xs">Flush All</button>
            </form>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">UUID</th><th>Queue</th><th>Payload</th><th>Exception</th><th>Failed At</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse(($jobs['recent_failed'] ?? []) as $f)
                    @php $payload = json_decode($f->payload, true); $display = $payload['displayName'] ?? $payload['job'] ?? '-'; @endphp
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 font-mono text-[11px]">{{ substr($f->uuid,0,8) }}...</td>
                        <td>{{ $f->queue }}</td>
                        <td class="max-w-[200px] truncate">{{ $display }}</td>
                        <td class="max-w-[300px] truncate text-red-600">{{ Str::limit($f->exception, 80) }}</td>
                        <td>{{ $f->failed_at }}</td>
                        <td class="flex gap-1">
                            <form method="POST" action="{{ route('fcm.jobs.retryFailed', $f->uuid) }}">@csrf<button type="submit" class="px-2 py-1 bg-yellow-500 text-white rounded">Retry</button></form>
                            <form method="POST" action="{{ route('fcm.jobs.forgetFailed', $f->uuid) }}">@csrf<button type="submit" class="px-2 py-1 bg-gray-600 text-white rounded">Forget</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-gray-500">Tidak ada failed jobs</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-gray-50 text-xs">
            Manual: <code>php artisan queue:failed</code> • <code>queue:retry all</code> • <code>queue:forget ID</code> • <code>queue:flush</code>
        </div>
    </div>
@endif

@if($tab==='fcm')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-4">
            <h3 class="font-semibold text-sm mb-2">Credential</h3>
            <div class="text-sm">File: @if($fcm['credential_exists']) <span class="text-green-600">ADA</span> @else <span class="text-red-600">TIDAK ADA</span> @endif</div>
            <div class="text-xs break-all">{{ $fcm['credential_path'] }}</div>
            <div class="text-xs">Project: <code>{{ $fcm['project_id'] }}</code></div>
            <div class="text-xs">Client: <code>{{ $fcm['client_email'] }}</code></div>
            <div class="text-xs mt-2">Queue: {{ $fcm['queue_connection'] }}</div>
        </div>
        <div class="card p-4">
            <h3 class="font-semibold text-sm mb-2">Tokens</h3>
            <div class="text-sm">Total: {{ $fcm['total_tokens'] }} • Valid: <span class="text-green-600">{{ $fcm['valid_tokens'] }}</span> • Invalid: <span class="text-red-600">{{ $fcm['invalid_tokens'] }}</span></div>
            <div class="text-xs mt-2">By platform: @foreach($fcm['by_platform'] as $p=>$c) <span class="px-2 py-1 bg-gray-100 rounded mr-1">{{ $p }}:{{ $c }}</span> @endforeach</div>
            <div class="text-xs mt-2">Legacy users fcm_token: {{ $fcm['legacy_users_with_token'] }}</div>
            <div class="text-xs mt-2">Notif pending: {{ $fcm['notif_pending'] }} • failed: {{ $fcm['notif_failed'] }}</div>
        </div>
        <div class="card p-4">
            <h3 class="font-semibold text-sm mb-2">Test Kirim</h3>
            <form method="POST" action="{{ route('fcm.test') }}">@csrf
                <input type="text" name="title" value="Test FCM Manual" class="w-full border rounded px-2 py-1 text-sm mb-2" placeholder="Title">
                <input type="text" name="body" value="Hello dari /fcm" class="w-full border rounded px-2 py-1 text-sm mb-2" placeholder="Body">
                <input type="text" name="token" placeholder="Kosong = pakai token sendiri" class="w-full border rounded px-2 py-1 text-sm mb-2">
                <button type="submit" class="w-full px-3 py-2 bg-blue-600 text-white rounded text-sm">Kirim Test (sync)</button>
            </form>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-semibold text-sm">Recent Device Tokens (10)</h3></div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">User</th><th>Token</th><th>Platform</th><th>Valid</th><th>Last Used</th></tr></thead>
                <tbody>
                @forelse($fcm['recent_tokens'] as $t)
                    <tr class="border-b">
                        <td class="py-2">#{{ $t->user_id }}</td>
                        <td class="font-mono">{{ substr($t->token,0,12) }}***{{ substr($t->token,-4) }}</td>
                        <td>{{ $t->platform ?? '-' }}</td>
                        <td>@if($t->is_valid) <span class="text-green-600">ya</span> @else <span class="text-red-600">tidak</span> @endif</td>
                        <td>{{ $t->last_used_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-gray-500">Belum ada token</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-sm">Firebase Log (20 terbaru)</h3></div>
        <div class="p-4">
            <pre class="bg-gray-900 text-green-300 p-3 rounded text-xs overflow-auto max-h-[40vh] whitespace-pre-wrap">{{ implode("\n", $fcm['firebase_log_recent']['lines'] ?? []) }}</pre>
        </div>
    </div>
@endif

@if($tab==='notifications')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-4 text-center"><div class="text-2xl font-bold">{{ $notifications['total'] }}</div><div class="text-xs">Total</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-orange-600">{{ $notifications['unread'] }}</div><div class="text-xs">Unread</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-green-600">{{ $notifications['read'] }}</div><div class="text-xs">Read</div></div>
    </div>
    <div class="card mb-6">
        <div class="card-header"><h3 class="font-semibold text-sm">By Type</h3></div>
        <div class="p-4 flex flex-wrap gap-2">
            @foreach($notifications['by_type'] as $t=>$c) <span class="px-3 py-1 bg-purple-50 rounded text-xs">{{ $t }}: {{ $c }}</span> @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-header flex justify-between items-center"><h3 class="font-semibold text-sm">Recent Notifications (10)</h3><a href="{{ route('api.notifications.index') }}" target="_blank" class="text-xs text-blue-600">API JSON →</a></div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">ID</th><th>Type</th><th>Title</th><th>To</th><th>Read</th><th>Created</th></tr></thead>
                <tbody>
                @forelse($notifications['recent'] as $n)
                    <tr class="border-b">
                        <td class="font-mono text-[11px]">{{ substr($n['id'],0,8) }}...</td>
                        <td>{{ $n['type'] }}</td>
                        <td class="max-w-[250px] truncate">{{ $n['title'] }}</td>
                        <td>#{{ $n['notifiable_id'] }}</td>
                        <td>@if($n['read_at']) <span class="text-green-600">ya</span> @else <span class="text-orange-600">belum</span> @endif</td>
                        <td>{{ $n['created_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-gray-500">Belum ada</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card mt-4 p-4">
        <h3 class="font-semibold text-sm mb-2">Top Users by Notif Count</h3>
        @forelse($notifications['top_users'] as $u)
            <div class="text-xs py-1 flex justify-between"><span>User #{{ $u->notifiable_id }}</span><span class="font-bold">{{ $u->cnt }}</span></div>
        @empty
            <div class="text-xs text-gray-500">-</div>
        @endforelse
    </div>
@endif

<div class="mt-6 text-xs text-gray-500">
    <div>API Monitoring JSON: <code>GET /api/monitoring/overview</code> • <code>/api/monitoring/logs?channel=firebase&lines=100</code> • <code>/api/monitoring/jobs</code> • <code>/api/monitoring/fcm</code> • <code>/api/monitoring/notifications</code> (Bearer admin)</div>
    <div>Web Monitoring: <code>/fcm?tab=overview|logs|jobs|fcm|notifications</code> + <code>/fcm/logs?channel=laravel&lines=200</code></div>
</div>
@endsection
