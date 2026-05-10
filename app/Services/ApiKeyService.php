<?php

namespace App\Services;

use Illuminate\Support\Str;

class ApiKeyService
{
    public static function generate(int $length = 64): string
    {
        return Str::random($length);
    }

    public static function mask(string $apiKey, int $visible = 8): string
    {
        if (strlen($apiKey) <= $visible) {
            return $apiKey;
        }

        return substr($apiKey, 0, $visible) . str_repeat('*', strlen($apiKey) - $visible);
    }

    public static function validate(string $apiKey): bool
    {
        return strlen($apiKey) >= 32 && ctype_alnum($apiKey);
    }
}

