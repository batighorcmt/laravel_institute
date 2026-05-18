<?php

use App\Models\InterschoolEvent;
use App\Models\InterschoolSeasonEvent;
use App\Models\InterschoolSubEvent;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\InterschoolAppendixKaService;

function makeAppendixKaStudent(array $attributes = []): Student
{
    $student = Student::make($attributes);
    $student->id = $attributes['id'] ?? 1;
    $student->setRelation('currentEnrollment', StudentEnrollment::make([
        'roll_no' => $attributes['roll_no'] ?? 1,
    ])->setRelation('class', (object) ['bangla_name' => 'নবম', 'name' => 'Nine']));

    return $student;
}

it('formats event labels with optional sub events', function () {
    $service = new InterschoolAppendixKaService;

    $seasonEvent = new InterschoolSeasonEvent;
    $seasonEvent->setRelation('event', new InterschoolEvent(['name' => 'Swimming']));
    $seasonEvent->setRelation('subEvent', new InterschoolSubEvent(['name' => '50m Freestyle']));

    expect($service->formatEventLabel($seasonEvent))->toBe('Swimming (50m Freestyle)');

    $seasonEventWithoutSub = new InterschoolSeasonEvent;
    $seasonEventWithoutSub->setRelation('event', new InterschoolEvent(['name' => 'Athletics']));
    $seasonEventWithoutSub->setRelation('subEvent', null);

    expect($service->formatEventLabel($seasonEventWithoutSub))->toBe('Athletics');
});

it('registers appendix ka route', function () {
    $appendixKaRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(function ($route) {
        return str_contains($route->uri(), 'interschool/appendix/ka');
    });

    expect($appendixKaRoute)->not->toBeNull();
});

it('renders one appendix ka form per student for grouped single events', function () {
    $halim = makeAppendixKaStudent([
        'id' => 1,
        'student_name_bn' => 'হালিম',
        'father_name_bn' => 'আব্দুল',
        'mother_name_bn' => 'রোকসানা',
    ]);

    $noyon = makeAppendixKaStudent([
        'id' => 2,
        'student_name_bn' => 'নয়ন',
        'father_name_bn' => 'করিম',
        'mother_name_bn' => 'সালমা',
        'roll_no' => 2,
    ]);

    $school = School::make([
        'name_bn' => 'টেস্ট বিদ্যালয়',
        'upazila' => 'টেস্ট',
        'district' => 'ঢাকা',
        'eiin' => '123456',
    ]);

    $html = view('principal.game_and_sports.interschool.print.appendix-ka', [
        'school' => $school,
        'isGroupedSingleEvents' => true,
        'groupedPlayers' => collect([
            [
                'student' => $halim,
                'attendance_days' => '120',
                'calculated_age' => ['years' => 14, 'months' => 2, 'days' => 5],
                'events' => ['সাতার', 'দীর্ঘ লাফ'],
            ],
            [
                'student' => $noyon,
                'attendance_days' => '100',
                'calculated_age' => ['years' => 13, 'months' => 6, 'days' => 0],
                'events' => ['সাতার'],
            ],
        ]),
        'competitionName' => '২০২৬',
        'ageDate' => '2026-01-01',
    ])->render();

    expect(substr_count($html, 'পরিশিষ্ট "ক"'))->toBe(2);
    expect($html)->toContain('ইভেন্টের নাম: সাতার, দীর্ঘ লাফ');
    expect($html)->toContain('ইভেন্টের নাম: সাতার');
    expect($html)->toContain('হালিম');
    expect($html)->toContain('নয়ন');
    expect($html)->not->toContain('ইভেন্টসমূহ');
    expect($html)->toContain('ka-form');
});

it('renders a single student appendix ka when filtered to one entry', function () {
    $student = makeAppendixKaStudent(['student_name_bn' => 'হালিম']);

    $html = view('principal.game_and_sports.interschool.print.appendix-ka', [
        'school' => School::make(['name_bn' => 'টেস্ট বিদ্যালয়']),
        'isGroupedSingleEvents' => true,
        'groupedPlayers' => collect([
            [
                'student' => $student,
                'attendance_days' => '120',
                'calculated_age' => null,
                'events' => ['সাতার', 'দীর্ঘ লাফ'],
            ],
        ]),
        'competitionName' => '২০২৬',
        'ageDate' => '2026-01-01',
    ])->render();

    expect(substr_count($html, 'পরিশিষ্ট "ক"'))->toBe(1);
    expect($html)->toContain('ইভেন্টের নাম: সাতার, দীর্ঘ লাফ');
});

it('keeps team event appendix ka as one form listing all players', function () {
    $seasonEvent = new InterschoolSeasonEvent;
    $seasonEvent->setRelation('event', new InterschoolEvent(['name' => 'ফুটবল', 'type' => 'team']));
    $seasonEvent->setRelation('subEvent', null);
    $seasonEvent->setRelation('players', collect());

    $html = view('principal.game_and_sports.interschool.print.appendix-ka', [
        'school' => School::make(['name_bn' => 'টেস্ট বিদ্যালয়']),
        'isGroupedSingleEvents' => false,
        'seasonEvent' => $seasonEvent,
        'competitionName' => '২০২৬',
        'ageDate' => '2026-01-01',
    ])->render();

    expect(substr_count($html, 'পরিশিষ্ট "ক"'))->toBe(1);
    expect($html)->toContain('ইভেন্টের নাম: ফুটবল');
});
