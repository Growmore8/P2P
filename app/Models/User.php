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
    protected $attributes = ['country' => 'IN', 'status' => 'active', 'kyc_status' => 'none', 'is_admin' => false];

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
            'password' => 'hashed',
            'is_admin' => 'boolean',
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

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
}
