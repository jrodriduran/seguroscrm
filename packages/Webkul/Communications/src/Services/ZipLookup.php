<?php

namespace Webkul\Communications\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * City and state for a US ZIP code, so nobody has to type them.
 * Answers are cached for good; ZIP codes rarely change.
 */
class ZipLookup
{
    public function lookup(string $zip): ?array
    {
        $zip = substr(preg_replace('/\D/', '', $zip), 0, 5);

        if (strlen($zip) !== 5) {
            return null;
        }

        $cached = Cache::get("zip-lookup:{$zip}");

        if ($cached !== null) {
            return $cached ?: null;
        }

        try {
            $response = Http::timeout(4)->acceptJson()->get("https://api.zippopotam.us/us/{$zip}");
        } catch (Throwable) {
            return null;
        }

        if ($response->status() === 404) {
            Cache::put("zip-lookup:{$zip}", [], now()->addDays(30));

            return null;
        }

        $place = $response->successful() ? $response->json('places.0') : null;

        if (! $place) {
            return null;
        }

        $result = [
            'zip' => $zip,
            'city' => $place['place name'] ?? null,
            'state' => $place['state abbreviation'] ?? null,
            'country' => 'US',
        ];

        Cache::forever("zip-lookup:{$zip}", $result);

        return $result;
    }
}
