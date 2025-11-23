<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all teachers with their school info
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->join('schools', 'teachers.school_id', '=', 'schools.id')
            ->whereNull('users.username')
            ->select('teachers.*', 'schools.code as school_code', 'users.id as user_id')
            ->orderBy('teachers.serial_number')
            ->get();
        
        // Group by school to generate sequential usernames per school
        $schoolCounters = [];
        
        foreach ($teachers as $teacher) {
            $schoolCode = $teacher->school_code;
            
            // Initialize counter for this school if not exists
            if (!isset($schoolCounters[$schoolCode])) {
                // Find the highest existing username number for this school
                $existingMax = DB::table('users')
                    ->where('username', 'LIKE', $schoolCode . 'T%')
                    ->whereNotNull('username')
                    ->get()
                    ->map(function($u) use ($schoolCode) {
                        $num = str_replace($schoolCode . 'T', '', $u->username);
                        return is_numeric($num) ? (int)$num : 0;
                    })
                    ->max();
                
                $schoolCounters[$schoolCode] = $existingMax ? $existingMax : 0;
            }
            
            // Increment counter and generate username
            $schoolCounters[$schoolCode]++;
            $username = $schoolCode . 'T' . str_pad($schoolCounters[$schoolCode], 3, '0', STR_PAD_LEFT);
            
            // Double check uniqueness
            while (DB::table('users')->where('username', $username)->exists()) {
                $schoolCounters[$schoolCode]++;
                $username = $schoolCode . 'T' . str_pad($schoolCounters[$schoolCode], 3, '0', STR_PAD_LEFT);
            }
            
            // Generate 6-digit password if not exists
            $plainPassword = $teacher->plain_password;
            if (!$plainPassword) {
                $plainPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                // Update teacher table with plain password
                DB::table('teachers')
                    ->where('id', $teacher->id)
                    ->update(['plain_password' => $plainPassword]);
            }
            
            // Update user with username
            DB::table('users')
                ->where('id', $teacher->user_id)
                ->update(['username' => $username]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set usernames back to null for migrated teachers
        $teacherUserIds = DB::table('teachers')->pluck('user_id');
        
        DB::table('users')
            ->whereIn('id', $teacherUserIds)
            ->update(['username' => null]);
    }
};
