<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function getCacheKey(string $key, $uniqueId = null): string
    {
        $appName = strtolower(str_replace(" ", "", env('APP_NAME')));

        return "{$appName}:{$key}:". app()->getLocale() . ($uniqueId ? ":{$uniqueId}" : '');
    }
}
