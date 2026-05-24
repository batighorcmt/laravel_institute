<?php

if (! function_exists('storage_asset')) {
    /**
     * Public URL for a path on the "public" storage disk (hero images, logos, etc.).
     */
    function storage_asset(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim(str_replace(['\\', '/storage/', 'storage/'], ['/', '', ''], $path), '/');

        // Relative URL works on any domain/port (myjss.edu.bd:8000 vs APP_URL mismatch).
        return '/storage/'.$path;
    }
}

if (! function_exists('storage_asset_url')) {
    /**
     * Absolute URL for meta tags / OG images (uses current request host).
     */
    function storage_asset_url(?string $path): ?string
    {
        $relative = storage_asset($path);

        if ($relative === null) {
            return null;
        }

        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return $relative;
        }

        return url($relative);
    }
}

if (! function_exists('langField')) {
    /**
     * Get field value based on language suffix or specialized fields.
     */
    function langField($model, $field, $lang = 'bn')
    {
        if (! $model) {
            return '';
        }

        if ($lang === 'bn') {
            // Check common patterns for Bangla fields
            $patterns = [
                $field.'_bn',
                'bangla_name',
                'name_bn',
                $field.'_bangla',
                'student_name_bn',
                'subject_bangla_name',
                'title_bn',
                'full_name_bn',
            ];

            foreach ($patterns as $p) {
                if (isset($model->$p) && trim((string) $model->$p) !== '') {
                    return $model->$p;
                }
            }
        } else {
            // Priority for English fields
            $patterns = [
                $field.'_en',
                'name_en',
                $field,
                'name',
                'student_name_en',
                'full_name',
                'full_name_en',
            ];

            foreach ($patterns as $p) {
                if (isset($model->$p) && trim((string) $model->$p) !== '') {
                    return $model->$p;
                }
            }
        }

        return $model->$field ?? '';
    }
}

if (! function_exists('toBengaliNumber')) {
    /**
     * Convert English numbers to Bengali numbers.
     */
    function toBengaliNumber($number)
    {
        $bn_digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        return str_replace(range(0, 9), $bn_digits, $number);
    }
}

if (! function_exists('toBengaliMonth')) {
    /**
     * Convert English month name to Bengali.
     */
    function toBengaliMonth($string)
    {
        $en_months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $bn_months = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

        return str_replace($en_months, $bn_months, $string);
    }
}

if (! function_exists('detectGradeFromClass')) {
    /**
     * Helper to detect grade/group from class name for seating colors.
     */
    function detectGradeFromClass($className)
    {
        if (! $className) {
            return '';
        }
        $className = (string) $className;
        $lc = strtolower($className);

        // English
        if (str_contains($lc, 'six') || str_contains($lc, ' 6')) {
            return '6';
        }
        if (str_contains($lc, 'seven') || str_contains($lc, ' 7')) {
            return '7';
        }
        if (str_contains($lc, 'eight') || str_contains($lc, ' 8')) {
            return '8';
        }
        if (str_contains($lc, 'nine') || str_contains($lc, ' 9')) {
            return '9';
        }
        if (str_contains($lc, 'ten') || str_contains($lc, ' 10')) {
            return '10';
        }

        // Bangla
        if (str_contains($className, 'ষষ্ঠ') || str_contains($className, '৬')) {
            return '6';
        }
        if (str_contains($className, 'সপ্তম') || str_contains($className, '৭')) {
            return '7';
        }
        if (str_contains($className, 'অষ্টম') || str_contains($className, '৮')) {
            return '8';
        }
        if (str_contains($className, 'নবম') || str_contains($className, '৯')) {
            return '9';
        }
        if (str_contains($className, 'দশম') || str_contains($className, '১০')) {
            return '10';
        }

        // Direct numeric match
        if (preg_match('/\b(6|7|8|9|10)\b/', $className, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
