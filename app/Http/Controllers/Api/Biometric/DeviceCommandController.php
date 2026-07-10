<?php

namespace App\Http\Controllers\Api\Biometric;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceCommandController extends Controller
{
    /**
     * Send commands to the local agent (e.g., sync users, restart device).
     * This acts as a polling endpoint for the agent to fetch pending commands.
     */
    public function getPendingCommands(Request $request)
    {
        // For phase 1, returning empty. 
        // In the future, this will return commands like 'sync_user', 'clear_logs'
        return response()->json([
            'commands' => []
        ]);
    }
}
