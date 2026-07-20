<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => Cache::forget("system_setting:{$setting->key}"));
        static::deleted(fn (self $setting) => Cache::forget("system_setting:{$setting->key}"));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("system_setting:{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
