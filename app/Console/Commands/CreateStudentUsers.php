<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateStudentUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-student-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user accounts for all existing active students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $students = \App\Models\Student::whereNull('user_id')->active()->get();
        $total = $students->count();
        $count = 0;
        $parentRole = \App\Models\Role::where('name', \App\Models\Role::PARENT)->first();

        if (!$parentRole) {
            $this->error('Parent role not found!');
            return 1;
        }

        if ($total === 0) {
            $this->info("No active students found without user accounts.");
            return 0;
        }

        $this->info("Found {$total} students without user accounts. Processing...");

        $this->withProgressBar($students, function ($student) use ($parentRole, &$count) {
            if (!$student->student_id) {
                return;
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($student, $parentRole, &$count) {
                // Check if user already exists with this student_id (username)
                $user = \App\Models\User::where('username', $student->student_id)->first();
                
                if (!$user) {
                    $user = \App\Models\User::create([
                        'name' => $student->student_name_en ?: $student->student_name_bn ?: 'Student',
                        'username' => $student->student_id,
                        'email' => $student->student_id . '@institute.local',
                        'password' => \Illuminate\Support\Facades\Hash::make('123456'),
                    ]);
                }

                // Ensure the student is linked to this user
                $student->user_id = $user->id;
                $student->save();

                // Assign role for the student's school
                \App\Models\UserSchoolRole::updateOrCreate([
                    'user_id' => $user->id,
                    'school_id' => $student->school_id,
                    'role_id' => $parentRole->id,
                ], [
                    'status' => 'active',
                ]);

                $count++;
            });
        });

        $this->newLine();
        $this->info("Successfully created/linked {$count} user accounts.");
        return 0;
    }
}
