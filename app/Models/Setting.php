<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'setting:';

    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key) {
            return static::query()->where('key', $key)->value('value');
        });

        if ($raw === null) {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value)]
        );

        Cache::forget(self::CACHE_PREFIX.$key);
    }

    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX.$key);
    }
}
