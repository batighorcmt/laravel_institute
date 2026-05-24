<?php

use App\Models\Notice;
use App\Models\School;
use App\Services\FrontendNoticeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('notices');
    Schema::dropIfExists('schools');

    Schema::create('schools', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('notices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('school_id')->nullable()->index();
        $table->string('title');
        $table->text('body');
        $table->string('audience_type', 50)->default('all');
        $table->json('audience_channels')->nullable();
        $table->boolean('reply_required')->default(false);
        $table->timestamp('publish_at')->nullable();
        $table->timestamp('expiry_at')->nullable();
        $table->string('status')->default('draft');
        $table->boolean('show_on_frontend_marquee')->default(false);
        $table->boolean('show_on_frontend_board')->default(true);
        $table->string('attachment_path')->nullable();
        $table->timestamps();
    });
});

it('returns only board-flagged published notices for the school', function () {
    $school = School::create(['name' => 'Test School', 'code' => 'TST1', 'status' => 'active']);
    $otherSchool = School::create(['name' => 'Other School', 'code' => 'TST2', 'status' => 'active']);

    Notice::create([
        'school_id' => $school->id,
        'title' => 'Board notice',
        'body' => 'Body',
        'status' => 'published',
        'publish_at' => now()->subDay(),
        'show_on_frontend_board' => true,
        'show_on_frontend_marquee' => false,
    ]);

    Notice::create([
        'school_id' => $school->id,
        'title' => 'Marquee only',
        'body' => 'Body',
        'status' => 'published',
        'publish_at' => now()->subDay(),
        'show_on_frontend_board' => false,
        'show_on_frontend_marquee' => true,
    ]);

    Notice::create([
        'school_id' => $otherSchool->id,
        'title' => 'Other school',
        'body' => 'Body',
        'status' => 'published',
        'publish_at' => now()->subDay(),
        'show_on_frontend_board' => true,
    ]);

    $board = app(FrontendNoticeService::class)->boardNoticesForSchool($school->id);

    expect($board)->toHaveCount(1)
        ->and($board->first()['title'])->toBe('Board notice');
});

it('returns only marquee-flagged published notices for the school', function () {
    $school = School::create(['name' => 'Test School', 'code' => 'TST3', 'status' => 'active']);

    Notice::create([
        'school_id' => $school->id,
        'title' => 'Ticker notice',
        'body' => 'Body',
        'status' => 'published',
        'publish_at' => now()->subDay(),
        'show_on_frontend_marquee' => true,
        'show_on_frontend_board' => false,
    ]);

    Notice::create([
        'school_id' => $school->id,
        'title' => 'Board only',
        'body' => 'Body',
        'status' => 'published',
        'publish_at' => now()->subDay(),
        'show_on_frontend_marquee' => false,
        'show_on_frontend_board' => true,
    ]);

    $marquee = app(FrontendNoticeService::class)->marqueeNoticesForSchool($school->id);

    expect($marquee)->toHaveCount(1)
        ->and($marquee->first()['title'])->toBe('Ticker notice');
});

it('excludes draft and expired notices from frontend lists', function () {
    $school = School::create(['name' => 'Test School', 'code' => 'TST4', 'status' => 'active']);

    Notice::create([
        'school_id' => $school->id,
        'title' => 'Draft',
        'body' => 'Body',
        'status' => 'draft',
        'show_on_frontend_board' => true,
        'show_on_frontend_marquee' => true,
    ]);

    Notice::create([
        'school_id' => $school->id,
        'title' => 'Expired',
        'body' => 'Body',
        'status' => 'published',
        'publish_at' => now()->subWeek(),
        'expiry_at' => now()->subDay(),
        'show_on_frontend_board' => true,
        'show_on_frontend_marquee' => true,
    ]);

    $service = app(FrontendNoticeService::class);

    expect($service->boardNoticesForSchool($school->id))->toBeEmpty()
        ->and($service->marqueeNoticesForSchool($school->id))->toBeEmpty();
});
