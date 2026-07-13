<?php

use App\Models\CmsPage;
use App\Models\School;
use App\Models\WebsiteMenuTemplate;
use App\Models\WebsitePageTemplate;
use App\Models\WebsiteTheme;
use App\Services\DynamicPageContentService;
use App\Services\WebsiteTemplateApplyService;
use Illuminate\Support\Facades\Schema;

// This suite builds its own minimal schema (like CmsFrontendTest) instead of using
// RefreshDatabase, because running the full migration history against the sqlite
// test connection currently fails on an unrelated MySQL-specific migration
// (`information_schema.statistics` lookup in the `classes` table migration).
beforeEach(function () {
    Schema::dropAllTables();

    Schema::create('schools', function ($table) {
        $table->id();
        $table->string('name')->default('Test School');
        $table->string('domain')->nullable();
        $table->string('address')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->timestamps();
    });

    Schema::create('cms_pages', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->unsignedBigInteger('author_id')->nullable();
        $table->string('title');
        $table->string('slug');
        $table->longText('content')->nullable();
        $table->string('content_mode')->default('static');
        $table->string('data_source')->nullable();
        $table->unsignedBigInteger('page_template_id')->nullable();
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

    Schema::create('website_themes', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('description')->nullable();
        $table->json('colors');
        $table->string('font_family')->nullable();
        $table->string('preview_image')->nullable();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_default')->default(false);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('website_menu_templates', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->json('config');
        $table->boolean('is_active')->default(true);
        $table->boolean('is_default')->default(false);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('website_page_templates', function ($table) {
        $table->id();
        $table->string('key')->unique();
        $table->string('title');
        $table->string('title_bn')->nullable();
        $table->string('default_slug');
        $table->string('content_mode')->default('static');
        $table->string('data_source')->nullable();
        $table->longText('default_content')->nullable();
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('school_frontend_settings', function ($table) {
        $table->id();
        $table->unsignedBigInteger('school_id')->unique();
        $table->unsignedBigInteger('theme_id')->nullable();
        $table->json('theme_overrides')->nullable();
        $table->unsignedBigInteger('applied_menu_template_id')->nullable();
        $table->timestamp('applied_at')->nullable();
        $table->string('hero_title')->nullable();
        $table->string('contact_address')->nullable();
        $table->string('contact_email')->nullable();
        $table->string('contact_phone')->nullable();
        $table->text('about_text')->nullable();
        $table->json('homepage_content')->nullable();
        $table->json('frontend_menus')->nullable();
        $table->timestamps();
    });
});

function makeSchool(): School
{
    return School::create(['name' => 'Test School']);
}

it('applies a theme, menu template and page templates to a school', function () {
    $school = makeSchool();

    $theme = WebsiteTheme::create([
        'name' => 'Test Theme', 'slug' => 'test-theme',
        'colors' => ['primary' => '#111111', 'secondary' => '#222222', 'accent' => '#333333', 'bg' => '#ffffff', 'text' => '#000000'],
    ]);

    $menuTemplate = WebsiteMenuTemplate::create([
        'name' => 'Test Menu', 'slug' => 'test-menu',
        'config' => ['menus' => [['id' => 'menu-primary', 'name' => 'Primary', 'items' => []]], 'locations' => ['header' => 'menu-primary']],
    ]);

    $pageTemplate = WebsitePageTemplate::create([
        'key' => 'about', 'title' => 'About', 'default_slug' => 'about',
        'content_mode' => WebsitePageTemplate::MODE_DYNAMIC, 'data_source' => 'about',
    ]);

    $service = app(WebsiteTemplateApplyService::class);
    $settings = $service->apply($school, $theme->id, $menuTemplate->id, [$pageTemplate->id]);

    expect($settings->theme_id)->toBe($theme->id);
    expect($settings->applied_menu_template_id)->toBe($menuTemplate->id);
    expect($settings->frontend_menus)->toMatchArray($menuTemplate->config);
    expect($settings->applied_at)->not->toBeNull();

    $page = CmsPage::forSchool($school->id)->where('page_template_id', $pageTemplate->id)->first();
    expect($page)->not->toBeNull();
    expect($page->slug)->toBe('about');
    expect($page->content_mode)->toBe('dynamic');
    expect($page->data_source)->toBe('about');
    expect($page->status)->toBe(CmsPage::STATUS_PUBLISHED);
});

it('upserts instead of duplicating cms pages when re-applying', function () {
    $school = makeSchool();

    $pageTemplate = WebsitePageTemplate::create([
        'key' => 'faculty', 'title' => 'Faculty', 'default_slug' => 'faculty',
        'content_mode' => WebsitePageTemplate::MODE_DYNAMIC, 'data_source' => 'teachers',
    ]);

    $service = app(WebsiteTemplateApplyService::class);
    $service->apply($school, null, null, [$pageTemplate->id]);

    $page = CmsPage::forSchool($school->id)->where('page_template_id', $pageTemplate->id)->first();
    $page->update(['slug' => 'our-faculty-renamed']);

    $service->apply($school, null, null, [$pageTemplate->id]);

    expect(CmsPage::forSchool($school->id)->where('page_template_id', $pageTemplate->id)->count())->toBe(1);
    expect($page->fresh()->slug)->toBe('our-faculty-renamed');
});

it('avoids slug collisions with an existing hand-created page', function () {
    $school = makeSchool();

    CmsPage::create([
        'school_id' => $school->id, 'title' => 'Existing About', 'slug' => 'about',
        'status' => CmsPage::STATUS_PUBLISHED,
    ]);

    $pageTemplate = WebsitePageTemplate::create([
        'key' => 'about', 'title' => 'About', 'default_slug' => 'about',
        'content_mode' => WebsitePageTemplate::MODE_STATIC, 'default_content' => '<p>Hi</p>',
    ]);

    $service = app(WebsiteTemplateApplyService::class);
    $service->apply($school, null, null, [$pageTemplate->id]);

    $templatePage = CmsPage::forSchool($school->id)->where('page_template_id', $pageTemplate->id)->first();
    expect($templatePage->slug)->not->toBe('about');
    expect(CmsPage::forSchool($school->id)->count())->toBe(2);
});

it('applying a theme does not touch the menu or pages, and vice versa', function () {
    $school = makeSchool();

    $themeA = WebsiteTheme::create([
        'name' => 'Theme A', 'slug' => 'theme-a',
        'colors' => ['primary' => '#111111', 'secondary' => '#222222', 'accent' => '#333333', 'bg' => '#ffffff', 'text' => '#000000'],
    ]);
    $themeB = WebsiteTheme::create([
        'name' => 'Theme B', 'slug' => 'theme-b',
        'colors' => ['primary' => '#444444', 'secondary' => '#555555', 'accent' => '#666666', 'bg' => '#eeeeee', 'text' => '#111111'],
    ]);
    $menuTemplate = WebsiteMenuTemplate::create([
        'name' => 'Test Menu', 'slug' => 'test-menu-2',
        'config' => ['menus' => [['id' => 'menu-primary', 'name' => 'Primary', 'items' => []]], 'locations' => ['header' => 'menu-primary']],
    ]);

    $service = app(WebsiteTemplateApplyService::class);

    // Apply theme A and the menu template together first (simulates prior setup).
    $service->applyTheme($school, $themeA->id);
    $service->applyMenu($school, $menuTemplate->id);

    $before = \App\Models\SchoolFrontendSetting::where('school_id', $school->id)->first();
    expect($before->theme_id)->toBe($themeA->id);
    expect($before->frontend_menus)->toMatchArray($menuTemplate->config);

    // Switching the theme alone must not clear or alter the already-applied menu.
    $service->applyTheme($school, $themeB->id);

    $afterThemeChange = \App\Models\SchoolFrontendSetting::where('school_id', $school->id)->first();
    expect($afterThemeChange->theme_id)->toBe($themeB->id);
    expect($afterThemeChange->frontend_menus)->toMatchArray($menuTemplate->config);

    // And applying the menu again must not alter the theme.
    $service->applyMenu($school, $menuTemplate->id);

    $afterMenuReapply = \App\Models\SchoolFrontendSetting::where('school_id', $school->id)->first();
    expect($afterMenuReapply->theme_id)->toBe($themeB->id);
});

it('resolves contact data source from school and settings', function () {
    $school = makeSchool();
    $school->update(['email' => 'school@example.com', 'phone' => '0123456789']);

    $settings = \App\Models\SchoolFrontendSetting::create([
        'school_id' => $school->id,
        'contact_address' => 'Test Address',
    ]);

    $resolved = app(DynamicPageContentService::class)->resolve('contact', $school, $settings);

    expect($resolved['address'])->toBe('Test Address');
    expect($resolved['email'])->toBe('school@example.com');
    expect($resolved['phone'])->toBe('0123456789');
});
