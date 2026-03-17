<?php

if (!function_exists('langField')) {
    /**
     * Get field value based on language suffix.
     * Example: student_name_bn or student_name_en
     */
    function langField($model, $field, $lang = 'bn') {
        if (!$model) return '';
        
        $langField = $field . '_' . ($lang === 'bn' ? 'bn' : 'en');
        
        // Fallback to original field name if language specific field doesn't exist
        if (isset($model->$langField)) {
            return $model->$langField;
        }
        
        return $model->$field ?? '';
    }
}

if (!function_exists('toBengaliNumber')) {
    /**
     * Convert English numbers to Bengali numbers.
     */
    function toBengaliNumber($number) {
        $bn_digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace(range(0, 9), $bn_digits, $number);
    }
}

if (!function_exists('detectGradeFromClass')) {
    /**
     * Helper to detect grade/group from class name for seating colors.
     */
    function detectGradeFromClass($className) {
        $className = strtolower($className);
        if (str_contains($className, 'six')) return '6';
        if (str_contains($className, 'seven')) return '7';
        if (str_contains($className, 'eight')) return '8';
        if (str_contains($className, 'nine')) return '9';
        if (str_contains($className, 'ten')) return '10';
        return '';
    }
}
