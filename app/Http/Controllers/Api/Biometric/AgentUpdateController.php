<?php

namespace App\Http\Controllers\Api\Biometric;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentUpdateController extends Controller
{
    public function checkUpdate(Request $request)
    {
        // Currently hardcoding the latest version details.
        // In the future, this can be pulled from a database table or config.
        $latestVersion = '1.0.0';
        $downloadUrl = url('/downloads/BiometricAgentSetup.exe');

        return response()->json([
            'latest_version' => $latestVersion,
            'download_url' => $downloadUrl,
            'release_notes' => 'Initial release with new local enrollment features.',
            'mandatory' => false
        ]);
    }
}
