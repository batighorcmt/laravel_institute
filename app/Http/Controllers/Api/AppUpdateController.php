<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class AppUpdateController extends Controller
{
    public function check(Request $request)
    {
        $currentVersionCode = (int)$request->query('version_code');

        $latestUpdate = AppUpdate::where('is_active', true)
            ->orderByDesc('version_code')
            ->first();

        // The mobile app calls this same public, unauthenticated,
        // always-hit-on-cold-start endpoint to resolve which API server it
        // should actually talk to (see DioClient.init() /
        // BootstrapService). Piggy-backing on this endpoint means a super
        // admin can redirect every installed app to a new backend, in an
        // emergency, without an app-store update — just by editing this
        // setting.
        $apiBaseUrl = SystemSetting::get('mobile_api_base_url');

        if ($latestUpdate && (int)$latestUpdate->version_code > $currentVersionCode) {
            return response()->json([
                'update_available' => true,
                'version_name' => $latestUpdate->version_name,
                'version_code' => (int)$latestUpdate->version_code,
                'apk_url' => $latestUpdate->apk_url,
                'release_notes' => $latestUpdate->release_notes,
                'is_mandatory' => (bool)$latestUpdate->is_mandatory,
                'api_base_url' => $apiBaseUrl,
            ]);
        }

        return response()->json([
            'update_available' => false,
            'api_base_url' => $apiBaseUrl,
        ]);
    }
}
