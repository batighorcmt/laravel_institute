<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settingsRows = DB::table('school_frontend_settings')->get(['id', 'school_id', 'homepage_content', 'updated_at']);

        foreach ($settingsRows as $row) {
            $content = json_decode($row->homepage_content ?? '', true);
            $gallery = is_array($content) ? ($content['gallery'] ?? []) : [];

            if (empty($gallery) || ! is_array($gallery)) {
                continue;
            }

            $now = $row->updated_at ?? now();
            foreach ($gallery as $path) {
                if (! is_string($path) || $path === '') {
                    continue;
                }

                DB::table('gallery_images')->insert([
                    'school_id' => $row->school_id,
                    'gallery_album_id' => null,
                    'path' => $path,
                    'caption' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data import; nothing to roll back.
    }
};
