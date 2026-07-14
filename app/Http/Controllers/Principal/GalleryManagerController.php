<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryManagerController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.gallery', compact('school'));
    }

    public function data(School $school)
    {
        $images = GalleryImage::where('school_id', $school->id)
            ->whereNull('gallery_album_id')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GalleryImage $img) => $this->presentImage($img));

        $albums = GalleryAlbum::where('school_id', $school->id)
            ->withCount('images')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GalleryAlbum $album) => $this->presentAlbum($album));

        return response()->json([
            'images' => $images,
            'albums' => $albums,
        ]);
    }

    public function albumImages(School $school, GalleryAlbum $album)
    {
        $images = $album->images()
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GalleryImage $img) => $this->presentImage($img));

        return response()->json([
            'album' => $this->presentAlbum($album),
            'images' => $images,
        ]);
    }

    public function upload(Request $request, School $school)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'gallery_album_id' => 'nullable|exists:gallery_albums,id',
        ]);

        $albumId = $request->input('gallery_album_id');
        if ($albumId) {
            GalleryAlbum::where('school_id', $school->id)->findOrFail($albumId);
        }

        $created = [];
        foreach ($request->file('images') as $file) {
            $path = $file->store('frontend/'.$school->id.'/gallery', 'public');
            $image = GalleryImage::create([
                'school_id' => $school->id,
                'gallery_album_id' => $albumId,
                'path' => $path,
            ]);
            $created[] = $this->presentImage($image);
        }

        return response()->json([
            'message' => count($created).' টি ছবি যোগ করা হয়েছে।',
            'images' => $created,
        ]);
    }

    public function destroyImage(School $school, GalleryImage $image)
    {
        abort_unless($image->school_id === $school->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['message' => 'ছবি মুছে ফেলা হয়েছে।']);
    }

    public function storeAlbum(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $album = GalleryAlbum::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'এলবাম তৈরি হয়েছে।',
            'album' => $this->presentAlbum($album->loadCount('images')),
        ]);
    }

    public function updateAlbum(Request $request, School $school, GalleryAlbum $album)
    {
        abort_unless($album->school_id === $school->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $album->update($validated);

        return response()->json([
            'message' => 'এলবাম আপডেট হয়েছে।',
            'album' => $this->presentAlbum($album->loadCount('images')),
        ]);
    }

    public function destroyAlbum(School $school, GalleryAlbum $album)
    {
        abort_unless($album->school_id === $school->id, 404);

        $album->delete();

        return response()->json(['message' => 'এলবাম মুছে ফেলা হয়েছে। ছবিগুলো সাধারণ গ্যালারিতে থেকে গেছে।']);
    }

    protected function presentImage(GalleryImage $image): array
    {
        return [
            'id' => $image->id,
            'path' => $image->path,
            'url' => storage_asset($image->path),
            'gallery_album_id' => $image->gallery_album_id,
            'uploaded_at' => $image->created_at?->format('d M Y, h:i A'),
            'uploaded_at_iso' => $image->created_at?->toIso8601String(),
        ];
    }

    protected function presentAlbum(GalleryAlbum $album): array
    {
        $thumbs = $album->images()
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn (GalleryImage $img) => storage_asset($img->path));

        return [
            'id' => $album->id,
            'name' => $album->name,
            'description' => $album->description,
            'images_count' => $album->images_count ?? $album->images()->count(),
            'thumbnails' => $thumbs,
            'created_at' => $album->created_at?->format('d M Y'),
        ];
    }
}
