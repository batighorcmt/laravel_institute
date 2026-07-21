<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use App\Services\ApkManifestParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            // extensions: rely on the client-reported filename extension
            // rather than the `mimes:` rule's MIME-sniffed extension
            // guessing, which is unreliable for .apk (an APK is a ZIP
            // container, so MIME-sniffing alone can't tell it apart from a
            // plain .zip — this previously let a misnamed file through).
            'apk_file' => 'required|file|extensions:apk|max:204800', // max 200MB
            'release_notes' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // version_code/version_name used to be typed by hand, completely
        // disconnected from the actual uploaded APK — an admin could release
        // an APK whose real versionCode didn't match what they typed, which
        // left the update-check endpoint permanently advertising a version
        // no installable build could ever satisfy (endless "update available"
        // even right after installing the "latest" build). Read them straight
        // out of the APK's own compiled manifest instead, so they can never
        // drift from what's actually being distributed.
        $manifest = $this->readManifestOrFail($request->file('apk_file'));
        if ($manifest instanceof \Illuminate\Http\RedirectResponse) {
            return $manifest;
        }

        if (AppUpdate::where('version_code', $manifest['version_code'])->exists()) {
            return back()->withInput()->withErrors([
                'apk_file' => "এই APK-এর ভার্সন কোড ({$manifest['version_code']}) ইতিমধ্যে আরেকটি রিলিজে ব্যবহৃত হয়েছে। mobile_app/pubspec.yaml-এর বিল্ড নাম্বার (+N) বাড়িয়ে আবার বিল্ড করে আপলোড করুন।",
            ]);
        }

        $path = $request->file('apk_file')->store('apk_updates', 'public');
        $data['apk_url'] = asset('storage/' . $path);
        $data['version_code'] = $manifest['version_code'];
        $data['version_name'] = $manifest['version_name'];
        $data['is_mandatory'] = $request->has('is_mandatory');
        $data['is_active'] = $request->has('is_active');

        AppUpdate::create($data);

        return redirect()->route('superadmin.app-updates.index')
            ->with('success', "Update released successfully — detected from APK: v{$manifest['version_name']} (code {$manifest['version_code']}).");
    }

    public function edit(AppUpdate $appUpdate)
    {
        return view('superadmin.app_updates.edit', compact('appUpdate'));
    }

    public function update(Request $request, AppUpdate $appUpdate)
    {
        $data = $request->validate([
            'apk_file' => 'nullable|file|extensions:apk|max:204800',
            'release_notes' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data['is_mandatory'] = $request->has('is_mandatory');
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('apk_file')) {
            $manifest = $this->readManifestOrFail($request->file('apk_file'));
            if ($manifest instanceof \Illuminate\Http\RedirectResponse) {
                return $manifest;
            }

            if (AppUpdate::where('version_code', $manifest['version_code'])->where('id', '!=', $appUpdate->id)->exists()) {
                return back()->withInput()->withErrors([
                    'apk_file' => "এই APK-এর ভার্সন কোড ({$manifest['version_code']}) ইতিমধ্যে আরেকটি রিলিজে ব্যবহৃত হয়েছে।",
                ]);
            }

            // Delete old file if exists
            $oldPath = str_replace(asset('storage/'), '', $appUpdate->apk_url);
            Storage::disk('public')->delete($oldPath);

            $path = $request->file('apk_file')->store('apk_updates', 'public');
            $data['apk_url'] = asset('storage/' . $path);
            $data['version_code'] = $manifest['version_code'];
            $data['version_name'] = $manifest['version_name'];
        }

        $appUpdate->update($data);

        return redirect()->route('superadmin.app-updates.index')->with('success', 'Update updated successfully.');
    }

    /**
     * @return array{version_code:int, version_name:string}|\Illuminate\Http\RedirectResponse
     */
    private function readManifestOrFail(\Illuminate\Http\UploadedFile $file)
    {
        try {
            $manifest = ApkManifestParser::parse($file->getRealPath());
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['apk_file' => 'APK ফাইল থেকে ভার্সন তথ্য পড়া যায়নি: '.$e->getMessage()]);
        }

        if (! $manifest['version_code'] || ! $manifest['version_name']) {
            return back()->withInput()->withErrors(['apk_file' => 'এই APK ফাইলে versionCode/versionName পাওয়া যায়নি। এটি সঠিক release APK কিনা যাচাই করুন।']);
        }

        return $manifest;
    }

    public function destroy(AppUpdate $appUpdate)
    {
        $oldPath = str_replace(asset('storage/'), '', $appUpdate->apk_url);
        Storage::disk('public')->delete($oldPath);
        $appUpdate->delete();

        return redirect()->route('superadmin.app-updates.index')->with('success', 'Update deleted successfully.');
    }
}
