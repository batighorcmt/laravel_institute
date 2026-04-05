<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Attendance',
                'slug' => 'attendance',
                'description' => 'Manage student and teacher attendance.',
            ],
            [
                'name' => 'Homework',
                'slug' => 'homework',
                'description' => 'Assign and track student homework.',
            ],
            [
                'name' => 'Exams',
                'slug' => 'exams',
                'description' => 'Manage examination schedules and results.',
            ],
            [
                'name' => 'Accounts',
                'slug' => 'accounts',
                'description' => 'Finance and fee management.',
            ],
            [
                'name' => 'Notices',
                'slug' => 'notices',
                'description' => 'School announcement and notices.',
            ],
            [
                'name' => 'Library',
                'slug' => 'library',
                'description' => 'Book management and issuing system.',
            ],
            [
                'name' => 'Routine',
                'slug' => 'routine',
                'description' => 'Class and teacher routine management.',
            ],
            [
                'name' => 'Admission',
                'slug' => 'admission',
                'description' => 'Student enrollment and admission system.',
            ],
            [
                'name' => 'Results',
                'slug' => 'results',
                'description' => 'Publishing and managing student results.',
            ],
            [
                'name' => 'SMS',
                'slug' => 'sms',
                'description' => 'Send notifications and alerts via SMS.',
            ],
            [
                'name' => 'Extra Class',
                'slug' => 'extra_class',
                'description' => 'Manage additional classes and coaching sessions.',
            ],
            [
                'name' => 'Lesson Evaluation',
                'slug' => 'lesson_evaluation',
                'description' => 'Track teaching progress and student feedback.',
            ],
            [
                'name' => 'Documents',
                'slug' => 'documents',
                'description' => 'Generate certificates and testimonials.',
            ],
            [
                'name' => 'Frontend Website',
                'slug' => 'frontend_website',
                'description' => 'School public website frontend.',
            ],
        ];

        foreach ($modules as $moduleData) {
            $module = Module::updateOrCreate(['slug' => $moduleData['slug']], $moduleData);
            
            // Proactively enable for all existing schools if they don't have it
            foreach (\App\Models\School::all() as $school) {
                if (!$school->modules()->where('module_id', $module->id)->exists()) {
                    $school->modules()->attach($module->id, ['is_enabled' => true]);
                }
            }
        }
    }
}
