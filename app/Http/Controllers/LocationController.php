<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Thana;
use App\Models\Union;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get districts by division_id
     */
    public function districts(Request $request)
    {
        $districts = District::where('division_id', $request->division_id)
            ->orderBy('bn_name')
            ->get(['id', 'name', 'bn_name']);
        return response()->json($districts);
    }

    /**
     * Get thanas by district_id
     */
    public function thanas(Request $request)
    {
        $thanas = Thana::where('district_id', $request->district_id)
            ->orderBy('bn_name')
            ->get(['id', 'name', 'bn_name']);
        return response()->json($thanas);
    }

    /**
     * Get unions by thana_id
     */
    public function unions(Request $request)
    {
        $unions = Union::where('thana_id', $request->thana_id)
            ->orderBy('bn_name')
            ->get(['id', 'name', 'bn_name']);
        return response()->json($unions);
    }
}
