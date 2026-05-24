<?php

use App\Models\SchoolFrontendSetting;
use App\Services\FrontendHomepageContentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('school_frontend_settings');

    Schema::create('school_frontend_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->json('homepage_content')->nullable();
        $table->timestamps();
    });
});

it('merges stored homepage content with defaults', function () {
    $settings = new SchoolFrontendSetting([
        'school_id' => 1,
        'homepage_content' => [
            'mission' => ['title' => 'কাস্টম মিশন', 'body' => 'বিবরণ'],
        ],
    ]);

    $content = app(FrontendHomepageContentService::class)->resolve($settings);

    expect($content['mission']['title'])->toBe('কাস্টম মিশন')
        ->and($content['mission']['body'])->toBe('বিবরণ')
        ->and($content['vision']['title'])->toBe('আমাদের ভিশন');
});

it('maps gallery paths to public urls on resolve', function () {
    $settings = new SchoolFrontendSetting([
        'school_id' => 1,
        'homepage_content' => [
            'gallery' => ['frontend/1/gallery/photo.jpg'],
        ],
    ]);

    $content = app(FrontendHomepageContentService::class)->resolve($settings);

    expect($content['gallery'])->toHaveCount(1)
        ->and($content['gallery'][0])->toContain('frontend/1/gallery/photo.jpg');
});

it('includes blog section defaults', function () {
    $content = app(FrontendHomepageContentService::class)->resolve(null);

    expect($content['blog_section']['title'])->toBe('ব্লগ ও সংবাদ')
        ->and($content['blog_section']['subtitle'])->not->toBeEmpty();
});
