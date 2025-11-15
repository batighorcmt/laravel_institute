<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'System administrator with full access',
                'permissions' => [
                    'manage_schools',
                    'manage_users',
                    'manage_roles',
                    'view_all_data',
                    'system_settings'
                ]
            ],
            [
                'name' => 'principal',
                'display_name' => 'Principal',
                'description' => 'School principal with administrative access',
                'permissions' => [
                    'manage_school_users',
                    'manage_teachers',
                    'manage_students',
                    'manage_classes',
                    'manage_subjects',
                    'view_school_reports',
                    'school_settings'
                ]
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'description' => 'Teacher with class and student management',
                'permissions' => [
                    'manage_assigned_classes',
                    'view_students',
                    'manage_attendance',
                    'manage_grades',
                    'view_class_reports'
                ]
            ],
            [
                'name' => 'parent',
                'display_name' => 'Parent/Guardian',
                'description' => 'Parent or guardian with limited access',
                'permissions' => [
                    'view_own_children',
                    'view_attendance',
                    'view_grades',
                    'view_notices'
                ]
            ]
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
