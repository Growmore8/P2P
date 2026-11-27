<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Visitor's country from their network (IP) location — used to pre-select the phone
 * country and to show "not available in your region" notices. Visitors can always change it.
 *
 * Order: CDN header (Cloudflare) → cached lookup (GeoJS, then country.is) → null.
 * Only the IP address is sent to the lookup service; results are cached for a day.
 */
class GeoCountry
{
    public static function detect(Request $request): ?string
    {
        $countries = config('countries');

        $header = strtoupper((string) $request->header('CF-IPCountry'));
        if ($header !== '' && isset($countries[$header])) {
            return $header;
        }

        $ip = $request->ip();
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null; // localhost / private network (e.g. local testing)
        }

        $code = Cache::remember('geo:' . sha1($ip), now()->addDay(), function () use ($ip) {
            foreach ([
                fn () => Http::timeout(2)->acceptJson()->get("https://get.geojs.io/v1/ip/country/{$ip}.json")->json('country'),
                fn () => Http::timeout(2)->acceptJson()->get("https://api.country.is/{$ip}")->json('country'),
            ] as $lookup) {
                try {
                    $c = strtoupper((string) $lookup());
                    if (preg_match('/^[A-Z]{2}$/', $c)) {
                        return $c;
                    }
                } catch (Throwable) {
                    // try the next provider
                }
            }

            return '';
        });

        return ($code !== '' && isset($countries[$code])) ? $code : null;
    }

    /** Country to pre-select: detected → fallback (India). */
    public static function preselect(Request $request, string $fallback = 'IN'): string
    {
        return self::detect($request) ?? $fallback;
    }
}
