<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    public const int SINGLETON_ID = 1;

    private const string CACHE_KEY_SHOW_DEMO = 'site_settings.show_demo_data';

    protected $table = 'site_settings';

    protected $fillable = [
        'show_demo_data',
    ];

    public function casts(): array
    {
        return [
            'show_demo_data' => 'boolean',
        ];
    }

    public static function showDemoData(): bool
    {
        return (bool) Cache::rememberForever(
            self::CACHE_KEY_SHOW_DEMO,
            static function (): bool {
                if (! Schema::hasTable('site_settings')) {
                    return false;
                }

                try {
                    return (bool) self::query()->whereKey(self::SINGLETON_ID)->value('show_demo_data');
                } catch (QueryException) {
                    return false;
                }
            },
        );
    }

    public static function setShowDemoData(bool $value): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        self::query()->whereKey(self::SINGLETON_ID)->update([
            'show_demo_data' => $value,
            'updated_at' => now(),
        ]);

        Cache::forget(self::CACHE_KEY_SHOW_DEMO);
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            Cache::forget(self::CACHE_KEY_SHOW_DEMO);
        });
    }
}
