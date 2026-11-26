<?php

namespace App\Support;

use App\Models\DepositMethod;

/** Builds the homepage logo strip: wallets always; payment logos only for methods actually offered. */
class BrandStrip
{
    public static function build(): array
    {
        $active = DepositMethod::active()->get(['type', 'title']);
        $offers = function (string $rule) use ($active): bool {
            return match ($rule) {
                'upi' => $active->contains('type', 'upi'),
                'bank' => $active->contains('type', 'bank'),
                default => $active->contains(fn ($m) => stripos($m->title, $rule) !== false),
            };
        };

        $withLogo = fn (array $b) => $b + ['logo' => self::logo($b['slug'])];

        return [
            'wallets' => array_map($withLogo, config('brands.wallets')),
            'payments' => array_values(array_map($withLogo, array_filter(config('brands.payments'), fn ($p) => $offers($p['show'])))),
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
