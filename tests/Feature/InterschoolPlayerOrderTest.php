<?php

use App\Http\Controllers\Principal\InterschoolController;
use App\Models\InterschoolEvent;
use App\Models\InterschoolPlayer;
use App\Models\InterschoolSeason;
use App\Models\InterschoolSeasonEvent;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropAllTables();

    Schema::create('schools', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('students', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->string('student_id')->unique();
        $table->string('student_name_en');
        $table->string('student_name_bn')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('interschool_seasons', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->string('name');
        $table->date('age_date')->nullable();
        $table->timestamps();
    });

    Schema::create('interschool_events', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->string('name');
        $table->string('type');
        $table->timestamps();
    });

    Schema::create('interschool_season_events', function ($table) {
        $table->id();
        $table->unsignedBigInteger('interschool_season_id');
        $table->unsignedBigInteger('interschool_event_id');
        $table->unsignedBigInteger('interschool_sub_event_id')->nullable();
        $table->string('age_group')->nullable();
        $table->timestamps();
    });

    Schema::create('interschool_players', function ($table) {
        $table->id();
        $table->unsignedBigInteger('interschool_season_event_id');
        $table->unsignedBigInteger('student_id');
        $table->string('group_name')->nullable();
        $table->string('height')->nullable();
        $table->string('weight')->nullable();
        $table->boolean('is_captain')->default(false);
        $table->string('attendance_days')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('classes', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('sections', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('student_enrollments', function ($table) {
        $table->id();
        $table->unsignedBigInteger('student_id');
        $table->unsignedBigInteger('school_id');
        $table->unsignedBigInteger('class_id');
        $table->unsignedBigInteger('section_id')->nullable();
        $table->unsignedInteger('roll_no')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });
});

function createInterschoolTeamEventSetup(): array
{
    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $season = InterschoolSeason::create([
        'school_id' => $school->id,
        'name' => 'Summer 2026',
    ]);

    $event = InterschoolEvent::create([
        'school_id' => $school->id,
        'name' => 'Football',
        'type' => 'team',
    ]);

    $seasonEvent = InterschoolSeasonEvent::create([
        'interschool_season_id' => $season->id,
        'interschool_event_id' => $event->id,
    ]);

    $students = collect(range(1, 3))->map(function (int $n) use ($school) {
        return Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-'.$n,
            'student_name_en' => 'Player '.$n,
            'status' => 'active',
        ]);
    });

    $players = $students->map(function (Student $student, int $index) use ($seasonEvent) {
        return InterschoolPlayer::create([
            'interschool_season_event_id' => $seasonEvent->id,
            'student_id' => $student->id,
            'sort_order' => $index + 1,
        ]);
    });

    return compact('school', 'seasonEvent', 'players');
}

it('registers interschool player reorder route', function () {
    $route = collect(Route::getRoutes())->first(function ($route) {
        return str_contains($route->uri(), 'interschool/api/season-events/{seasonEvent}/players/reorder')
            && in_array('PATCH', $route->methods());
    });

    expect($route)->not->toBeNull();
});

it('returns players ordered by sort_order', function () {
    ['school' => $school, 'seasonEvent' => $seasonEvent, 'players' => $players] = createInterschoolTeamEventSetup();

    $controller = app(InterschoolController::class);
    $result = $controller->getPlayers($school->id, $seasonEvent->id);

    expect($result->pluck('id')->all())->toBe([
        $players[0]->id,
        $players[1]->id,
        $players[2]->id,
    ]);
});

it('reorders players via reorder endpoint', function () {
    ['school' => $school, 'seasonEvent' => $seasonEvent, 'players' => $players] = createInterschoolTeamEventSetup();

    $controller = app(InterschoolController::class);
    $request = Request::create('/', 'PATCH', [
        'order' => [$players[2]->id, $players[0]->id, $players[1]->id],
    ]);

    $response = $controller->reorderPlayers($request, $school->id, $seasonEvent->id);
    expect($response->getData(true))->toBe(['success' => true]);

    $ordered = InterschoolPlayer::where('interschool_season_event_id', $seasonEvent->id)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all();

    expect($ordered)->toBe([$players[2]->id, $players[0]->id, $players[1]->id]);
});

it('moves player from position 3 to 7 when updating sort_order', function () {
    ['school' => $school, 'seasonEvent' => $seasonEvent, 'players' => $players] = createInterschoolTeamEventSetup();

    collect(range(4, 7))->each(function (int $n) use ($school, $seasonEvent) {
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-'.$n,
            'student_name_en' => 'Player '.$n,
            'status' => 'active',
        ]);

        InterschoolPlayer::create([
            'interschool_season_event_id' => $seasonEvent->id,
            'student_id' => $student->id,
            'sort_order' => $n,
        ]);
    });

    $target = $players[2];

    $controller = app(InterschoolController::class);
    $request = Request::create('/', 'PUT', [
        'height' => '5 ft 6 in',
        'weight' => '50 kg',
        'sort_order' => 7,
    ]);

    $controller->updatePlayer($request, $school->id, $seasonEvent->id, $target->id);

    $target->refresh();
    expect($target->sort_order)->toBe(7)
        ->and($target->height)->toBe('5 ft 6 in')
        ->and($target->weight)->toBe('50 kg');

    $orderedIds = InterschoolPlayer::where('interschool_season_event_id', $seasonEvent->id)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all();

    expect($orderedIds[6])->toBe($target->id);
});

it('assigns next sort_order when storing a new player', function () {
    ['school' => $school, 'seasonEvent' => $seasonEvent] = createInterschoolTeamEventSetup();

    $student = Student::create([
        'school_id' => $school->id,
        'student_id' => 'STU-NEW',
        'student_name_en' => 'New Player',
        'status' => 'active',
    ]);

    $controller = app(InterschoolController::class);
    $request = Request::create('/', 'POST', [
        'student_id' => $student->id,
        'height' => '5 ft',
        'weight' => '45 kg',
    ]);

    $controller->storePlayer($request, $school->id, $seasonEvent->id);

    $player = InterschoolPlayer::where('student_id', $student->id)->first();
    expect($player?->sort_order)->toBe(4);
});
