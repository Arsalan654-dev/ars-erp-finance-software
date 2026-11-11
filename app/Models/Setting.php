<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    /**
     * Get a setting value by key (cached for 5 minutes).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting_{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * Set a setting value (upsert).
     */
    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => $value,
            'group' => $group,
        ]);

        Cache::forget("setting_{$key}");
    }

    /**
     * Get all settings for a group.
     */
    public static function group(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }
}
