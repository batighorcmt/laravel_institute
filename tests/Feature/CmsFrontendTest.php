<?php

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\School;
use App\Services\CmsSlugService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropAllTables();

    Schema::create('schools', function ($table) {
        $table->id();
        $table->string('name')->default('Test School');
        $table->timestamps();
    });

    Schema::create('cms_pages', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->unsignedBigInteger('author_id')->nullable();
        $table->string('title');
        $table->string('slug');
        $table->longText('content')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->string('meta_keywords')->nullable();
        $table->string('og_image')->nullable();
        $table->string('robots')->default('index, follow');
        $table->timestamps();
        $table->unique(['school_id', 'slug']);
    });

    Schema::create('cms_posts', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->unsignedBigInteger('author_id')->nullable();
        $table->string('title');
        $table->string('slug');
        $table->text('excerpt')->nullable();
        $table->longText('content')->nullable();
        $table->string('featured_image')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->string('meta_keywords')->nullable();
        $table->string('og_image')->nullable();
        $table->string('robots')->default('index, follow');
        $table->timestamps();
        $table->unique(['school_id', 'slug']);
    });
});

it('generates unique slug and avoids reserved words', function () {
    $school = School::create(['name' => 'Demo']);
    $service = new CmsSlugService;

    expect($service->makeUniqueSlug('Blog', null, new CmsPage, $school->id))->toBe('blog-page');

    CmsPage::create([
        'school_id' => $school->id,
        'title' => 'About',
        'slug' => 'about',
        'status' => CmsPage::STATUS_PUBLISHED,
    ]);

    expect($service->makeUniqueSlug('About', 'about', new CmsPage, $school->id))->toBe('about-1');
});

it('scopes published cms pages', function () {
    $school = School::create(['name' => 'Demo']);

    CmsPage::create([
        'school_id' => $school->id,
        'title' => 'Draft',
        'slug' => 'draft',
        'status' => CmsPage::STATUS_DRAFT,
    ]);
    CmsPage::create([
        'school_id' => $school->id,
        'title' => 'Live',
        'slug' => 'live',
        'status' => CmsPage::STATUS_PUBLISHED,
        'published_at' => now()->subMinute(),
    ]);
    CmsPage::create([
        'school_id' => $school->id,
        'title' => 'Future',
        'slug' => 'future',
        'status' => CmsPage::STATUS_PUBLISHED,
        'published_at' => now()->addDay(),
    ]);

    $published = CmsPage::forSchool($school->id)->published()->pluck('slug')->all();

    expect($published)->toBe(['live']);
});

it('scopes published blog posts', function () {
    $school = School::create(['name' => 'Demo']);

    CmsPost::create([
        'school_id' => $school->id,
        'title' => 'Draft',
        'slug' => 'draft',
        'status' => CmsPost::STATUS_DRAFT,
    ]);
    CmsPost::create([
        'school_id' => $school->id,
        'title' => 'Live',
        'slug' => 'live-post',
        'status' => CmsPost::STATUS_PUBLISHED,
        'published_at' => now()->subMinute(),
    ]);

    expect(CmsPost::forSchool($school->id)->published()->count())->toBe(1);
});

it('detects published state on cms page model', function () {
    $page = new CmsPage([
        'status' => CmsPage::STATUS_PUBLISHED,
        'published_at' => now()->subMinute(),
    ]);

    expect($page->isPublished())->toBeTrue();

    $page->status = CmsPage::STATUS_DRAFT;
    expect($page->isPublished())->toBeFalse();
});
