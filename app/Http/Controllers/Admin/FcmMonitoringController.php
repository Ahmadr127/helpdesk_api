<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FcmMonitoringController extends Controller
{
    public function __construct(protected MonitoringService $monitoring) {}

    /**
     * GET /fcm and /monitoring/fcm - Dashboard
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'overview');
        $allowed = ['overview','logs','jobs','fcm','notifications'];
        if (!in_array($tab, $allowed, true)) $tab = 'overview';

        $data = $this->collectData($request);

        // If wants JSON
        if ($request->wantsJson() || $request->query('format')==='json') {
            return response()->json(['success'=>true, 'tab'=>$tab, 'data'=>$data]);
        }

        return view('admin.fcm.index', array_merge($data, ['tab'=>$tab]));
    }

    /**
     * GET /fcm/logs
     */
    public function logs(Request $request)
    {
        $channel = $request->query('channel','laravel');
        $lines = (int) $request->query('lines', 200);
        $lines = max(10, min($lines, 1000));

        $log = $this->monitoring->getLogLines($channel, $lines);

        if ($request->wantsJson()) {
            return response()->json(['success'=>true, 'data'=>$log]);
        }

        $data = $this->collectData($request);
        $data['logDetail'] = $log;
        $data['logChannel'] = $channel;
        $data['logLines'] = $lines;

        return view('admin.fcm.index', array_merge($data, ['tab'=>'logs']));
    }

    /**
     * POST /fcm/logs/clear
     */
    public function clearLog(Request $request)
    {
        $channel = $request->input('channel','laravel');
        $path = match($channel){
            'firebase' => storage_path('logs/firebase.log'),
            'laravel' => storage_path('logs/laravel.log'),
            default => storage_path("logs/{$channel}.log"),
        };
        if (File::exists($path)) {
            File::put($path, "[Cleared at ".now()." by ".auth()->user()->email."]".PHP_EOL);
        }
        return redirect()->route('fcm.index', ['tab'=>'logs','channel'=>$channel])->with('success', "Log {$channel} cleared");
    }

    /**
     * POST /fcm/jobs/retry/{id}
     */
    public function retryFailed(Request $request, string $id)
    {
        try {
            \Artisan::call('queue:retry', ['id'=>$id]);
            $output = \Artisan::output();
            return redirect()->route('fcm.index', ['tab'=>'jobs'])->with('success', "Retry {$id}: {$output}");
        } catch (\Throwable $e) {
            return redirect()->route('fcm.index', ['tab'=>'jobs'])->with('error', $e->getMessage());
        }
    }

    public function forgetFailed(Request $request, string $id)
    {
        try {
            \Artisan::call('queue:forget', ['id'=>$id]);
            return redirect()->route('fcm.index', ['tab'=>'jobs'])->with('success', "Forgot {$id}");
        } catch (\Throwable $e) {
            return redirect()->route('fcm.index', ['tab'=>'jobs'])->with('error', $e->getMessage());
        }
    }

    public function flushFailed(Request $request)
    {
        try {
            \Artisan::call('queue:flush');
            return redirect()->route('fcm.index', ['tab'=>'jobs'])->with('success', "Flushed all failed jobs");
        } catch (\Throwable $e) {
            return redirect()->route('fcm.index', ['tab'=>'jobs'])->with('error', $e->getMessage());
        }
    }

    /**
     * POST /fcm/test - send test FCM to self
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'token' => ['nullable','string','min:10'],
            'title' => ['nullable','string','max:200'],
            'body' => ['nullable','string','max:1000'],
        ]);

        $user = $request->user();
        $token = $request->input('token') ?: $user->fcm_token;

        if (!$token) {
            // try device_tokens
            $token = $user->deviceTokens()->valid()->value('token');
        }

        if (!$token) {
            return redirect()->route('fcm.index', ['tab'=>'fcm'])->with('error', 'Tidak ada FCM token untuk user ini. Register via Flutter dulu atau isi token manual.');
        }

        try {
            $title = $request->input('title', 'Test FCM '.now()->format('H:i:s'));
            $body = $request->input('body', 'Hello dari monitoring /fcm');
            $dto = \App\DTO\Notifications\FirebaseNotificationData::make(title:$title, body:$body, data:['type'=>'test','source'=>'fcm_monitoring','time'=>now()->toIso8601String()]);
            // Use queue for async, but for monitoring we send sync to show result immediately
            $service = app(\App\Contracts\Notifications\FirebaseNotificationInterface::class);
            $result = $service->sendToToken($token, $dto);
            return redirect()->route('fcm.index', ['tab'=>'fcm'])->with('success', "Test terkirim! Message ID: ".($result['message_id'] ?? 'ok')." ke ".substr($token,0,10).'***');
        } catch (\Throwable $e) {
            return redirect()->route('fcm.index', ['tab'=>'fcm'])->with('error', 'Gagal kirim: '.$e->getMessage());
        }
    }

    private function collectData(Request $request): array
    {
        return [
            'jobs' => $this->monitoring->getJobsStats(),
            'fcm' => $this->monitoring->getFcmStats(),
            'notifications' => $this->monitoring->getNotificationsStats(),
            'logs' => [
                'laravel' => $this->monitoring->getLogLines('laravel', 50),
                'firebase' => $this->monitoring->getLogLines('firebase', 50),
            ],
            'queue_connection' => config('queue.default'),
            'env' => app()->environment(),
        ];
    }
}
