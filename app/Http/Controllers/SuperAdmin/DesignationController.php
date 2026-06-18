<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'designations' => Designation::orderBy('id', 'asc')->get()
            ]);
        }
        return view('superadmin.designations.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
        ]);

        $designation = Designation::create($validated);

        return response()->json([
            'message' => 'পদবী সফলভাবে যুক্ত করা হয়েছে',
            'designation' => $designation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
        ]);

        $designation->update($validated);

        return response()->json([
            'message' => 'পদবী সফলভাবে আপডেট করা হয়েছে',
            'designation' => $designation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Designation $designation)
    {
        $designation->delete();

        return response()->json([
            'message' => 'পদবী সফলভাবে ডিলিট করা হয়েছে'
        ]);
    }
}
