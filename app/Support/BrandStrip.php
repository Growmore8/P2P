<?php

namespace App\Support;

use App\Models\DepositMethod;

/** Builds the homepage logo strip: wallets always; payment logos only for methods actually offered. */
class BrandStrip
{
    public static function build(): array
    {
        $active = DepositMethod::active()->get(['type', 'title', 'instructions']);
        // A brand shows when any of its rules match: a method type (upi / bank / link) or a word in a method's title or instructions.
        $offers = function (string|array $rules) use ($active): bool {
            foreach ((array) $rules as $rule) {
                $hit = in_array($rule, ['upi', 'bank', 'link', 'wallet'], true)
                    ? $active->contains('type', $rule)
                    : $active->contains(fn ($m) => stripos($m->title . ' ' . $m->instructions, $rule) !== false);
                if ($hit) {
                    return true;
                }
            }

            return false;
        };

        $picked = array_values(array_filter(explode(',', (string) \App\Models\Setting::get('pay_logos', ''))));
        $withLogo = fn (array $b) => $b + ['logo' => self::logo($b['slug'])];

        return [
            'wallets' => array_map($withLogo, config('brands.wallets')),
            // Admin can pick the "Pay with" logos in Settings; if none are picked, they follow the active deposit methods.
            'payments' => array_values(array_map($withLogo, array_filter(config('brands.payments'), function ($p) use ($offers, $picked) {
                return $picked ? in_array($p['slug'], $picked, true) : $offers($p['show']);
            }))),
        ];
    }

    /**
     * Inline markup for a local single-colour SVG logo, recoloured with currentColor.
     * Only reads our own files in public/images/brands (never user input).
     */
    public static function inlineSvg(string $path): ?string
    {
        $svg = @file_get_contents(public_path($path));
        if (! $svg || ! str_contains($svg, '<svg') || preg_match('/<script|on\w+=/i', $svg)) {
            return null;
        }
        // Multi-colour logos keep their own colours → shown as a normal <img> instead.
        if (preg_match('/fill="#|fill:\s*#|stop-color|Gradient/i', $svg)) {
            return null;
        }
        $svg = preg_replace('#<title>.*?</title>#s', '', $svg);

        return preg_replace('/<svg\b/', '<svg class="blogo" fill="currentColor" aria-hidden="true" focusable="false"', $svg, 1);
    }

    private static function logo(string $slug): ?string
    {
        foreach (['svg', 'png', 'webp'] as $ext) {
            if (is_file(public_path("images/brands/{$slug}.{$ext}"))) {
                return "images/brands/{$slug}.{$ext}";
            }
        }

        return null;
    }
}
