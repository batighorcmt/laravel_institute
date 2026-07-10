<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;

class FixBiometricIds extends Command
{
    protected $signature = 'biometric:fix-ids';
    protected $description = 'Remove non-numeric prefix from all student biometric_ids (e.g. JSS260121 → 260121)';

    public function handle()
    {
        $students = Student::whereNotNull('student_id')->get();
        $count = 0;

        foreach ($students as $student) {
            $numericId = preg_replace('/[^0-9]/', '', $student->student_id);
            if (!empty($numericId) && $student->biometric_id !== $numericId) {
                $student->biometric_id = $numericId;
                $student->save();
                $count++;
            }
        }

        $this->info("✅ Done! Updated biometric_id for {$count} student(s).");
        $this->info("   Example: JSS260121 → 260121");
    }
}
