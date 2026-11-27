<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'country', 'password'];

    /** Same defaults as the database columns, so new models have them before a reload. */
    protected $attributes = ['country' => 'IN', 'status' => 'active', 'kyc_status' => 'none', 'kyc_level' => 0, 'is_admin' => false];

    protected static function booted(): void
    {
        // Every account gets a permanent unique client ID, e.g. SRM4829173.
        static::creating(function (User $u) {
            $u->client_id ??= static::newClientId();
        });
    }

    public static function newClientId(): string
    {
        do {
            $id = 'SRM' . random_int(1000000, 9999999);
        } while (static::where('client_id', $id)->exists());

        return $id;
    }

    public function isIndian(): bool
    {
        return $this->country === 'IN';
    }

    public function countryName(): string
    {
        return (string) config("countries.{$this->country}.0", (string) $this->country);
    }

    /** Local currency code for the user's country (from config/countries.php). */
    public function currencyCode(): string
    {
        return (string) config("countries.{$this->country}.2", 'INR');
    }

    /** The active Currency the user trades in, or null if not offered in their country yet. */
    public function currency(): ?Currency
    {
        return Currency::where('code', $this->currencyCode())->where('is_active', true)->first();
    }

    public function kycUpgrades(): HasMany
    {
        return $this->hasMany(KycUpgrade::class);
    }

    /** Latest Level 2 / Level 3 request (or null). */
    public function upgrade(int $level): ?KycUpgrade
    {
        return $this->kycUpgrades()->where('level', $level)->latest('id')->first();
    }

    /** Level used for limits: approved identity is at least Level 1. */
    public function effectiveKycLevel(): int
    {
        return $this->isKycApproved() ? max(1, (int) $this->kyc_level) : 0;
    }

    /**
     * Status of each verification step for the Verification page.
     * done | review | rejected | next | locked
     */
    public function verificationSteps(): array
    {
        $level = $this->effectiveKycLevel();
        $kyc = $this->kyc;
        $steps = [];

        $steps[1] = match (true) {
            $this->kyc_status === 'approved' => ['state' => 'done'],
            $this->kyc_status === 'pending' => ['state' => 'review'],
            $this->kyc_status === 'rejected' => ['state' => 'rejected', 'reason' => $kyc?->reject_reason],
            default => ['state' => 'next'],
        };
        foreach ([KycUpgrade::VIDEO, KycUpgrade::EDD] as $lvl) {
            $u = $this->upgrade($lvl);
            $prevOk = $lvl === KycUpgrade::VIDEO ? in_array($this->kyc_status, ['approved', 'pending'], true) : $level >= KycUpgrade::VIDEO;
            $steps[$lvl] = match (true) {
                $level >= $lvl => ['state' => 'done'],
                $u?->status === 'pending' => ['state' => 'review'],
                $u?->status === 'rejected' && $prevOk => ['state' => 'rejected', 'reason' => $u->reject_reason],
                $prevOk => ['state' => 'next'],
                default => ['state' => 'locked'],
            };
        }

        return $steps;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /** Has the user accepted the current version of every account-level agreement? */
    public function hasCurrentConsent(): bool
    {
        $accepted = $this->consents()->where('version', Consent::currentVersion())
            ->whereIn('type', Consent::ACCOUNT_TYPES)->distinct()->pluck('type')->all();

        return count(array_diff(Consent::ACCOUNT_TYPES, $accepted)) === 0;
    }

    /** Phone digits only, for wa.me links. */
    public function whatsappNumber(): string
    {
        return preg_replace('/\D/', '', (string) $this->phone);
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'kyc_level' => 'integer',
        ];
    }

    public function kyc(): HasOne
    {
        return $this->hasOne(KycSubmission::class)->latestOfMany();
    }

    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isKycApproved(): bool
    {
        return $this->kyc_status === 'approved';
    }

    /** Managers check that payments arrived and mark orders "Reviewed"; admins approve. */
    public const MANAGER = 'manager';

    public function isManager(): bool
    {
        return $this->role === self::MANAGER && ! $this->is_admin;
    }

    /** Admin or manager — can open the staff panel. */
    public function isStaff(): bool
    {
        return $this->is_admin || $this->isManager();
    }

    /** Clients only (no admins, no managers). */
    public function scopeClients($q)
    {
        return $q->where('is_admin', false)->whereNull('role');
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3));
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
}
