<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MonitoringService
{
    /**
     * Get recent log lines from a file.
     */
    public function getLogLines(string $channel = 'laravel', int $lines = 200): array
    {
        $path = match ($channel) {
            'firebase' => storage_path('logs/firebase.log'),
            'firebase-daily' => $this->findLatestFirebaseLog(),
            'laravel' => storage_path('logs/laravel.log'),
            default => storage_path("logs/{$channel}.log"),
        };

        if (!File::exists($path)) {
            // try daily variant
            $daily = storage_path("logs/{$channel}-".now()->format('Y-m-d').".log");
            if (File::exists($daily)) {
                $path = $daily;
            } else {
                return ['exists' => false, 'path' => $path, 'lines' => [], 'size' => 0];
            }
        }

        $size = File::size($path);
        // Read last N lines efficiently
        $content = $this->tailFile($path, $lines);
        return ['exists' => true, 'path' => $path, 'lines' => $content, 'size' => $size];
    }

    private function findLatestFirebaseLog(): string
    {
        $pattern = storage_path('logs/firebase*.log');
        $files = glob($pattern);
        if (!$files) return storage_path('logs/firebase.log');
        usort($files, fn($a,$b) => filemtime($b) <=> filemtime($a));
        return $files[0];
    }

    private function tailFile(string $path, int $lines): array
    {
        // Simple: read all, slice last N (good for < few MB)
        try {
            $all = File::lines($path);
            $arr = iterator_to_array($all);
            $slice = array_slice($arr, -$lines);
            // Reverse to show newest first? Keep chronological (oldest of slice first)
            return array_values($slice);
        } catch (\Throwable $e) {
            $content = @file_get_contents($path);
            if ($content === false) return ['Error reading log: '.$e->getMessage()];
            $exploded = explode("\n", $content);
            return array_slice($exploded, -$lines);
        }
    }

    public function getJobsStats(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $pendingByQueue = DB::table('jobs')->select('queue', DB::raw('count(*) as cnt'))->groupBy('queue')->pluck('cnt','queue')->toArray();
            $failed = DB::table('failed_jobs')->count();
            $recentFailed = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(10)->get();
            $batches = DB::table('job_batches')->count();
            // Recent jobs payload preview (first 5)
            $recentJobs = DB::table('jobs')->orderByDesc('created_at')->limit(5)->get()->map(function($j){
                $payload = json_decode($j->payload, true);
                return [
                    'id' => $j->id,
                    'queue' => $j->queue,
                    'attempts' => $j->attempts,
                    'available_at' => $j->available_at,
                    'created_at' => $j->created_at,
                    'displayName' => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                    'uuid' => $payload['uuid'] ?? null,
                ];
            });

            return [
                'pending' => $pending,
                'pending_by_queue' => $pendingByQueue,
                'failed' => $failed,
                'recent_failed' => $recentFailed,
                'batches' => $batches,
                'recent_jobs' => $recentJobs,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'pending'=>0,'failed'=>0];
        }
    }

    public function getFcmStats(): array
    {
        $credPath = config('firebase.credentials', storage_path('app/firebase-auth.json'));
        $exists = File::exists($credPath);
        $projectId = null;
        $clientEmail = null;
        if ($exists) {
            $json = @json_decode(@file_get_contents($credPath), true);
            $projectId = $json['project_id'] ?? null;
            $clientEmail = $json['client_email'] ?? null;
        }

        try {
            $totalTokens = DB::table('device_tokens')->count();
            $validTokens = DB::table('device_tokens')->where('is_valid', true)->count();
            $invalidTokens = DB::table('device_tokens')->where('is_valid', false)->count();
            $byPlatform = DB::table('device_tokens')->select('platform', DB::raw('count(*) as cnt'))->groupBy('platform')->pluck('cnt','platform')->toArray();
            $legacyUsersWithToken = DB::table('users')->whereNotNull('fcm_token')->count();
            $recentTokens = DB::table('device_tokens')->orderByDesc('last_used_at')->limit(10)->get();
        } catch (\Throwable $e) {
            $totalTokens = $validTokens = $invalidTokens = 0;
            $byPlatform = [];
            $legacyUsersWithToken = 0;
            $recentTokens = collect();
        }

        // Queue stats for notifications queue specifically
        try {
            $notifPending = DB::table('jobs')->where('queue', 'notifications')->count();
            $notifFailed = DB::table('failed_jobs')->where('queue', 'notifications')->count();
        } catch (\Throwable $e) {
            $notifPending = $notifFailed = 0;
        }

        // Firebase channel log recent
        $firebaseLog = $this->getLogLines('firebase', 20);
        // Also check daily logs count
        $dailyFiles = glob(storage_path('logs/firebase*.log'));
        $dailyCount = $dailyFiles ? count($dailyFiles) : 0;

        return [
            'credential_exists' => $exists,
            'credential_path' => $credPath,
            'project_id' => $projectId,
            'client_email' => $clientEmail ? $this->maskEmail($clientEmail) : null,
            'queue_connection' => config('queue.default'),
            'total_tokens' => $totalTokens,
            'valid_tokens' => $validTokens,
            'invalid_tokens' => $invalidTokens,
            'by_platform' => $byPlatform,
            'legacy_users_with_token' => $legacyUsersWithToken,
            'recent_tokens' => $recentTokens,
            'notif_pending' => $notifPending,
            'notif_failed' => $notifFailed,
            'firebase_log_recent' => $firebaseLog,
            'daily_log_files' => $dailyCount,
        ];
    }

    public function getNotificationsStats(): array
    {
        try {
            $total = DB::table('notifications')->count();
            $unread = DB::table('notifications')->whereNull('read_at')->count();
            $byType = DB::table('notifications')->select('type', DB::raw('count(*) as cnt'))->groupBy('type')->pluck('cnt','type')->toArray();
            // Simplify type display
            $byTypeShort = [];
            foreach ($byType as $k=>$v) {
                $short = class_basename($k);
                $byTypeShort[$short] = $v;
            }
            $recent = DB::table('notifications')->orderByDesc('created_at')->limit(10)->get()->map(function($n){
                $data = json_decode($n->data, true) ?? [];
                return [
                    'id' => $n->id,
                    'type' => class_basename($n->type),
                    'notifiable_type' => class_basename($n->notifiable_type),
                    'notifiable_id' => $n->notifiable_id,
                    'title' => $data['title'] ?? $data['message'] ?? '-',
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            });
            $perUser = DB::table('notifications')->select('notifiable_id', DB::raw('count(*) as cnt'))->groupBy('notifiable_id')->orderByDesc('cnt')->limit(5)->get();
            return [
                'total' => $total,
                'unread' => $unread,
                'read' => $total - $unread,
                'by_type' => $byTypeShort,
                'recent' => $recent,
                'top_users' => $perUser,
            ];
        } catch (\Throwable $e) {
            return ['error'=>$e->getMessage(),'total'=>0,'unread'=>0];
        }
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts)!==2) return '***';
        $name = $parts[0];
        $domain = $parts[1];
        $masked = substr($name,0,2).str_repeat('*', max(0, strlen($name)-4)).substr($name,-2);
        return $masked.'@'.$domain;
    }
}
