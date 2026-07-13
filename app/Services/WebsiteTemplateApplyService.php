<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Models\WebsiteMenuTemplate;
use App\Models\WebsitePageTemplate;
use App\Models\WebsiteTheme;
use Illuminate\Support\Facades\DB;

class WebsiteTemplateApplyService
{
    public function __construct(protected CmsSlugService $slugService) {}

    /**
     * Apply only the theme (colors/pattern). Does not touch menus or pages.
     */
    public function applyTheme(School $school, int $themeId): SchoolFrontendSetting
    {
        $theme = WebsiteTheme::active()->findOrFail($themeId);

        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $settings->update([
            'theme_id' => $theme->id,
            'theme_overrides' => null,
            'applied_at' => now(),
        ]);

        return $settings->fresh();
    }

    /**
     * Apply only the menu template. Does not touch the theme or pages.
     */
    public function applyMenu(School $school, int $menuTemplateId): SchoolFrontendSetting
    {
        $menuTemplate = WebsiteMenuTemplate::active()->findOrFail($menuTemplateId);

        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $settings->update([
            'applied_menu_template_id' => $menuTemplate->id,
            'frontend_menus' => $menuTemplate->config,
            'applied_at' => now(),
        ]);

        return $settings->fresh();
    }

    /**
     * Apply only the selected default pages. Does not touch the theme or menu.
     *
     * @param  list<int>  $pageTemplateIds
     * @return list<CmsPage>
     */
    public function applyPages(School $school, array $pageTemplateIds, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($school, $pageTemplateIds, $actorUserId) {
            $pageTemplates = WebsitePageTemplate::active()
                ->whereIn('id', $pageTemplateIds)
                ->get();

            return $pageTemplates
                ->map(fn (WebsitePageTemplate $pageTemplate) => $this->applyPageTemplate($school, $pageTemplate, $actorUserId))
                ->all();
        });
    }

    /**
     * Convenience wrapper that applies theme + menu + pages together in one transaction.
     * Kept for programmatic/bulk use (e.g. seeding demo schools); the Principal-facing
     * UI applies each section independently so changing the theme never silently
     * overwrites the menu or vice versa.
     *
     * @param  list<int>  $pageTemplateIds
     */
    public function apply(School $school, ?int $themeId, ?int $menuTemplateId, array $pageTemplateIds, ?int $actorUserId = null): SchoolFrontendSetting
    {
        return DB::transaction(function () use ($school, $themeId, $menuTemplateId, $pageTemplateIds, $actorUserId) {
            if ($themeId) {
                $this->applyTheme($school, $themeId);
            }

            if ($menuTemplateId) {
                $this->applyMenu($school, $menuTemplateId);
            }

            if ($pageTemplateIds !== []) {
                $this->applyPages($school, $pageTemplateIds, $actorUserId);
            }

            $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
            $settings->update(['applied_at' => now()]);

            return $settings->fresh();
        });
    }

    protected function applyPageTemplate(School $school, WebsitePageTemplate $pageTemplate, ?int $actorUserId): CmsPage
    {
        $page = CmsPage::forSchool($school->id)
            ->where('page_template_id', $pageTemplate->id)
            ->first();

        $slug = $page?->slug ?? $this->slugService->makeUniqueSlug(
            $pageTemplate->title,
            $pageTemplate->default_slug,
            new CmsPage,
            $school->id
        );

        $attributes = [
            'title' => $pageTemplate->title_bn ?: $pageTemplate->title,
            'slug' => $slug,
            'content' => $pageTemplate->content_mode === WebsitePageTemplate::MODE_STATIC
                ? $pageTemplate->default_content
                : null,
            'content_mode' => $pageTemplate->content_mode,
            'data_source' => $pageTemplate->data_source,
            'page_template_id' => $pageTemplate->id,
            'status' => CmsPage::STATUS_PUBLISHED,
            'published_at' => $page?->published_at ?? now(),
            'sort_order' => $pageTemplate->sort_order,
        ];

        if ($page) {
            $page->update($attributes);

            return $page;
        }

        return CmsPage::create($attributes + [
            'school_id' => $school->id,
            'author_id' => $actorUserId,
        ]);
    }
}
