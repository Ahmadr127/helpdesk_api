<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFcmTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(StoreFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->input('token');
        $platform = $request->input('platform');

        // Upsert device_tokens
        DeviceToken::upsertToken($user->id, $token, $platform);

        // Update legacy column for easy access
        $user->update([
            'fcm_token' => $token,
            'fcm_token_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token disimpan',
            'data' => ['token' => $token],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->input('token');

        if ($token) {
            DeviceToken::where('token', $token)->where('user_id', $user->id)->delete();
            // If deleted token was legacy fcm_token, clear it
            if ($user->fcm_token === $token) {
                $user->update(['fcm_token' => null, 'fcm_token_updated_at' => null]);
            }
        } else {
            // Delete all tokens for user (logout all devices)
            DeviceToken::where('user_id', $user->id)->delete();
            $user->update(['fcm_token' => null, 'fcm_token_updated_at' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM token dihapus',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokens = DeviceToken::where('user_id', $user->id)->valid()->pluck('token');

        return response()->json([
            'success' => true,
            'data' => $tokens,
            'legacy_token' => $user->fcm_token,
        ]);
    }
}
