<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(protected MonitoringService $monitoring) {}

    public function overview(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'queue' => $this->monitoring->getJobsStats(),
                'fcm' => $this->monitoring->getFcmStats(),
                'notifications' => $this->monitoring->getNotificationsStats(),
                'logs' => [
                    'laravel' => $this->monitoring->getLogLines('laravel', 20),
                    'firebase' => $this->monitoring->getLogLines('firebase', 20),
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $channel = $request->query('channel','laravel');
        $lines = (int) $request->query('lines', 100);
        $lines = max(10, min($lines, 1000));
        $data = $this->monitoring->getLogLines($channel, $lines);
        return response()->json(['success'=>true, 'channel'=>$channel, 'data'=>$data]);
    }

    public function jobs(Request $request): JsonResponse
    {
        return response()->json(['success'=>true, 'data'=>$this->monitoring->getJobsStats()]);
    }

    public function fcm(Request $request): JsonResponse
    {
        return response()->json(['success'=>true, 'data'=>$this->monitoring->getFcmStats()]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json(['success'=>true, 'data'=>$this->monitoring->getNotificationsStats()]);
    }
}
