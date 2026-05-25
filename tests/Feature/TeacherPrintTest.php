<?php

use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Facades\Route;

it('registers the teacher list print route', function () {
    expect(Route::has('principal.institute.teachers.print'))->toBeTrue();
});

it('renders teacher print view with teacher rows', function () {
    $teacher = new Teacher([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'first_name_bn' => 'জন',
        'last_name_bn' => 'ডো',
        'designation' => 'সহকারী শিক্ষক',
        'phone' => '01700000000',
        'status' => 'active',
        'serial_number' => 1,
    ]);

    $html = view('principal.teachers.print', [
        'school' => School::make(['name' => 'Test School', 'name_bn' => 'টেস্ট স্কুল']),
        'teachers' => collect([$teacher]),
        'printTitle' => 'শিক্ষক তালিকা',
        'printSubtitle' => 'টেস্ট স্কুল | মোট শিক্ষক: ১ জন',
    ])->render();

    expect($html)
        ->toContain('শিক্ষক তালিকা')
        ->toContain('জন ডো')
        ->toContain('John Doe')
        ->toContain('সহকারী শিক্ষক')
        ->toContain('01700000000');
});
