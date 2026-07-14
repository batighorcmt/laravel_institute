<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('designation_id')->nullable()->after('designation')->constrained('designations')->nullOnDelete();
        });

        $this->backfillDesignations();
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('designation_id');
        });
    }

    /**
     * One-time data migration: match each teacher's existing free-text
     * `designation` against the super-admin managed `designations` list
     * (case-insensitive, trimmed, against either name_en or name_bn).
     * Matched rows get `designation_id` set and `designation` normalized
     * to the canonical Bangla name. Unmatched rows are cleared entirely
     * (both designation_id and designation set to null) so the Select2
     * field starts empty rather than showing a stale, unmapped label.
     */
    protected function backfillDesignations(): void
    {
        $designations = DB::table('designations')->get(['id', 'name_en', 'name_bn']);

        $lookup = [];
        foreach ($designations as $d) {
            if ($d->name_en) {
                $lookup[mb_strtolower(trim($d->name_en))] = $d;
            }
            if ($d->name_bn) {
                $lookup[mb_strtolower(trim($d->name_bn))] = $d;
            }
        }

        $teachers = DB::table('teachers')->whereNotNull('designation')->where('designation', '!=', '')->get(['id', 'designation']);

        foreach ($teachers as $teacher) {
            $key = mb_strtolower(trim($teacher->designation));
            $match = $lookup[$key] ?? null;

            if ($match) {
                DB::table('teachers')->where('id', $teacher->id)->update([
                    'designation_id' => $match->id,
                    'designation' => $match->name_bn ?: $match->name_en,
                ]);
            } else {
                DB::table('teachers')->where('id', $teacher->id)->update([
                    'designation_id' => null,
                    'designation' => null,
                ]);
            }
        }
    }
};
