<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Http\Resources\DeviceTokenResource;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required','string'],
            'platform' => ['nullable','string','in:android,ios,web']
        ]);
        $existing = DeviceToken::where('token',$validated['token'])->first();
        if ($existing) {
            $existing->update([
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'] ?? $existing->platform,
                'last_used_at' => now(),
            ]);
            return (new DeviceTokenResource($existing))->additional(['message' => 'ডিভাইস টোকেন আপডেট']);
        }
        $device = DeviceToken::create([
            'user_id' => $request->user()->id,
            'token' => $validated['token'],
            'platform' => $validated['platform'] ?? null,
            'last_used_at' => now(),
        ]);
        return (new DeviceTokenResource($device))->additional(['message' => 'ডিভাইস টোকেন সংরক্ষণ']);
    }

    public function destroy(Request $request, DeviceToken $deviceToken)
    {
        if ($deviceToken->user_id !== $request->user()->id) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }
        $deviceToken->delete();
        return response()->json(['message' => 'ডিভাইস টোকেন মুছে ফেলা হয়েছে']);
    }
}
