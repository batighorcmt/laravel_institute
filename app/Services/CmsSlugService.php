<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsSlugService
{
    /** @var array<int, string> */
    public const RESERVED_SLUGS = [
        'blog',
        'login',
        'logout',
        'password',
        'principal',
        'teacher',
        'admission',
        'api',
        'billing',
        'payment',
        'page',
        'pages',
        'print',
        'verify',
        'up',
        'admin',
        'storage',
    ];

    public function makeUniqueSlug(string $title, ?string $requested, Model $model, int $schoolId, ?int $ignoreId = null): string
    {
        $base = Str::slug($requested ?: $title);
        if ($base === '') {
            $base = 'item-'.time();
        }

        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-page';
        }

        $slug = $base;
        $i = 1;

        while ($this->slugExists($model, $schoolId, $slug, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    protected function slugExists(Model $model, int $schoolId, string $slug, ?int $ignoreId): bool
    {
        $query = $model->newQuery()->where('school_id', $schoolId)->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
