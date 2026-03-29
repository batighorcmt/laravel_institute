<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppUpdateController extends Controller
{
    public function index()
    {
        $updates = AppUpdate::orderByDesc('version_code')->paginate(10);
        return view('superadmin.app_updates.index', compact('updates'));
    }

    public function create()
    {
        return view('superadmin.app_updates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'version_code' => 'required|integer|unique:app_updates,version_code',
            'version_name' => 'required|string|max:20',
            'apk_file' => 'required|file|mimes:apk,bin,zip|max:51200', // max 50MB
            'release_notes' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('apk_file')) {
            $path = $request->file('apk_file')->store('apk_updates', 'public');
            $data['apk_url'] = asset('storage/' . $path);
        }

        AppUpdate::create($data);

        return redirect()->route('superadmin.app-updates.index')->with('success', 'Update released successfully.');
    }

    public function edit(AppUpdate $appUpdate)
    {
        return view('superadmin.app_updates.edit', compact('appUpdate'));
    }

    public function update(Request $request, AppUpdate $appUpdate)
    {
        $data = $request->validate([
            'version_code' => ['required', 'integer', Rule::unique('app_updates', 'version_code')->ignore($appUpdate->id)],
            'version_name' => 'required|string|max:20',
            'apk_file' => 'nullable|file|mimes:apk,bin,zip|max:51200',
            'release_notes' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('apk_file')) {
            // Delete old file if exists
            $oldPath = str_replace(asset('storage/'), '', $appUpdate->apk_url);
            Storage::disk('public')->delete($oldPath);
            
            $path = $request->file('apk_file')->store('apk_updates', 'public');
            $data['apk_url'] = asset('storage/' . $path);
        }

        $appUpdate->update($data);

        return redirect()->route('superadmin.app-updates.index')->with('success', 'Update updated successfully.');
    }

    public function destroy(AppUpdate $appUpdate)
    {
        $oldPath = str_replace(asset('storage/'), '', $appUpdate->apk_url);
        Storage::disk('public')->delete($oldPath);
        $appUpdate->delete();

        return redirect()->route('superadmin.app-updates.index')->with('success', 'Update deleted successfully.');
    }
}
