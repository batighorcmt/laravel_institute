<?php

namespace App\Services;

use App\Models\SchoolFrontendSetting;

class WebsiteThemeResolver
{
    /**
     * Fallback palette matching the original hardcoded amber/gold design,
     * used whenever a school has not applied a super-admin theme yet.
     *
     * @return array<string, string>
     */
    public function defaultColors(): array
    {
        return [
            'primary' => '#d97706',
            'secondary' => '#92400e',
            'accent' => '#f59e0b',
            'bg' => '#fefcf5',
            'text' => '#1f2937',
            'font' => "'Hind Siliguri', sans-serif",
        ];
    }

    /**
     * @return array<string, string>
     */
    public function resolveColors(?SchoolFrontendSetting $settings): array
    {
        $colors = $this->defaultColors();

        $theme = $settings?->theme;
        if ($theme) {
            $colors = array_merge($colors, array_filter((array) $theme->colors));
            if ($theme->font_family) {
                $colors['font'] = $theme->font_family;
            }
        }

        $overrides = $settings?->theme_overrides;
        if (is_array($overrides)) {
            $colors = array_merge($colors, array_filter($overrides));
        }

        return $colors;
    }
}
