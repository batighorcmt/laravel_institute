<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        if (!DB::table('roles')->where('name', Role::APPLICANT)->exists()) {
            DB::table('roles')->insert([
                'name' => Role::APPLICANT,
                'display_name' => 'Applicant',
                'description' => 'Admission applicant user',
                'permissions' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', Role::APPLICANT)->delete();
    }
};