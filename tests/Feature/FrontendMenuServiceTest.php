<?php

use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Services\FrontendMenuService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('school_frontend_settings');
    Schema::dropIfExists('schools');

    Schema::create('schools', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('school_frontend_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->json('frontend_menus')->nullable();
        $table->timestamps();
    });
});

it('resolves header menu items with nested children', function () {
    $school = School::create(['name' => 'Test School', 'code' => 'TST1', 'status' => 'active']);

    $settings = SchoolFrontendSetting::create([
        'school_id' => $school->id,
        'frontend_menus' => [
            'menus' => [
                [
                    'id' => 'menu-primary',
                    'name' => 'Primary',
                    'items' => [
                        [
                            'id' => 'about',
                            'label' => 'পরিচিতি',
                            'type' => 'section',
                            'section' => 'about',
                            'children' => [
                                [
                                    'id' => 'mission-child',
                                    'label' => 'মিশন',
                                    'type' => 'section',
                                    'section' => 'mission',
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'locations' => ['header' => 'menu-primary', 'footer' => ''],
        ],
    ]);

    $menu = app(FrontendMenuService::class)->forLocation($settings, $school, 'header');

    expect($menu)->toHaveCount(1)
        ->and($menu[0]['label'])->toBe('পরিচিতি')
        ->and($menu[0]['url'])->toContain('#about')
        ->and($menu[0]['children'])->toHaveCount(1)
        ->and($menu[0]['children'][0]['url'])->toContain('#mission');
});

it('falls back to default menus when none stored', function () {
    $school = School::create(['name' => 'Test School', 'code' => 'TST2', 'status' => 'active']);

    $header = app(FrontendMenuService::class)->forLocation(null, $school, 'header');

    expect($header)->not->toBeEmpty()
        ->and(collect($header)->pluck('label'))->toContain('হোম');
});
