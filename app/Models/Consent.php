<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/** A record of an agreement a client accepted (legal evidence: what, which version, when, from where). */
class Consent extends Model
{
    public $timestamps = false;

    public const LABELS = [
        'terms' => 'Terms & Conditions',
        'privacy' => 'Privacy Policy',
        'risk' => 'Risk Disclosure',
        'age' => 'Age 18+ confirmation',
        'kyc' => 'KYC data processing consent',
        'video_kyc' => 'Video recording consent',
        'p2p_order' => 'P2P order declaration',
    ];

    /** Accepted at sign-up and again whenever the terms version changes. */
    public const ACCOUNT_TYPES = ['terms', 'privacy', 'risk', 'age'];

    protected $fillable = ['user_id', 'type', 'version', 'context', 'ip', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function currentVersion(): string
    {
        return (string) Setting::get('terms_version', '1.0');
    }

    public static function record(User $user, array $types, Request $request, ?string $context = null): void
    {
        foreach ($types as $type) {
            static::create([
                'user_id' => $user->id,
                'type' => $type,
                'version' => static::currentVersion(),
                'context' => $context,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        }
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }
}
