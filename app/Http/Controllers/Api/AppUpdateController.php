<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Http\Request;

class AppUpdateController extends Controller
{
    public function check(Request $request)
    {
        $currentVersionCode = $request->query('version_code');

        $latestUpdate = AppUpdate::where('is_active', true)
            ->orderByDesc('version_code')
            ->first();

        if ($latestUpdate && $latestUpdate->version_code > $currentVersionCode) {
            return response()->json([
                'update_available' => true,
                'version_name' => $latestUpdate->version_name,
                'version_code' => $latestUpdate->version_code,
                'apk_url' => $latestUpdate->apk_url,
                'release_notes' => $latestUpdate->release_notes,
                'is_mandatory' => (bool)$latestUpdate->is_mandatory,
            ]);
        }

        return response()->json([
            'update_available' => false,
        ]);
    }
}
