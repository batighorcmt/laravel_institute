<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get teacher role ID
        $teacherRoleId = DB::table('roles')->where('name', 'teacher')->value('id');
        
        if (!$teacherRoleId) {
            return; // No teacher role, skip migration
        }
        
        // Get all users who are teachers (from user_school_roles)
        $teacherRoles = DB::table('user_school_roles')
            ->where('role_id', $teacherRoleId)
            ->get();
        
        foreach ($teacherRoles as $teacherRole) {
            // Get user data
            $user = DB::table('users')->find($teacherRole->user_id);
            
            if (!$user) continue;
            
            // Check if teacher record already exists
            $exists = DB::table('teachers')
                ->where('user_id', $user->id)
                ->where('school_id', $teacherRole->school_id)
                ->exists();
            
            if ($exists) continue;
            
            // Insert into teachers table
            DB::table('teachers')->insert([
                'user_id' => $user->id,
                'school_id' => $teacherRole->school_id,
                'first_name' => $user->first_name ?? 'N/A',
                'last_name' => $user->last_name,
                'first_name_bn' => $user->first_name_bn,
                'last_name_bn' => $user->last_name_bn,
                'father_name_bn' => $user->father_name_bn,
                'father_name_en' => $user->father_name_en,
                'mother_name_bn' => $user->mother_name_bn,
                'mother_name_en' => $user->mother_name_en,
                'phone' => $user->phone,
                'plain_password' => $user->plain_password,
                'designation' => $teacherRole->designation,
                'serial_number' => $teacherRole->serial_number,
                'date_of_birth' => $user->date_of_birth,
                'joining_date' => $user->joining_date,
                'academic_info' => $user->academic_info,
                'qualification' => $user->qualification,
                'photo' => $user->photo,
                'signature' => $user->signature,
                'status' => $teacherRole->status ?? 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete data on rollback, just truncate teachers table
        DB::table('teachers')->truncate();
    }
};
