<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        // Rates, limits and TDS live on each Currency (Admin → Currencies & Rates).
        'order_expiry_minutes' => '30',
        'terms_version' => '1.0',        // bump to make every client re-accept the terms
        'kyc_video_required' => '1',
        // Names shown on the KYC "checking your documents" screen — use real team members or their working names.
        'kyc_agent_names' => "Emilya Johnson\nDaniel Brooks\nPriya Raman\nArun Kumar\nSophia Martin\nRahul Nair\nOlivia Carter\nKarthik Subramani",
        'trading_enabled' => '1',
        'desk_hours' => '10:00 AM – 9:00 PM IST, Mon–Sat',
        'avg_completion' => '15–30 min',
        'support_whatsapp' => '910000000000',
        'support_phone' => '+91 00000 00000',
        'support_email' => 'info@srmfintechsolutions.com',
        'instagram_url' => 'https://instagram.com/',
        'announcement' => '',
    ];

    public static function values(): array
    {
        // Defaults are merged outside the cache so newly added settings work without clearing it.
        return array_merge(self::DEFAULTS, Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all()));
    }

    public static function get(string $key, $default = null)
    {
        return self::values()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings');
    }
}
