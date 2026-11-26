<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycSubmission extends Model
{
    protected $fillable = [
        'user_id', 'country', 'id_type', 'id_number', 'full_name', 'dob', 'pan_number', 'aadhaar_last4', 'address', 'city', 'state', 'pincode',
        'pan_image', 'aadhaar_front', 'aadhaar_back', 'selfie', 'video', 'checks', 'status', 'reject_reason', 'reviewed_by', 'reviewed_at',
    ];

    /**
     * The four upload columns. For Indian customers they hold PAN / Aadhaar;
     * for international customers they hold ID front / ID back / proof of address.
     */
    public const DOCS = [
        'pan_image' => 'PAN card',
        'aadhaar_front' => 'Aadhaar (front)',
        'aadhaar_back' => 'Aadhaar (back)',
        'selfie' => 'Selfie holding PAN',
    ];

    public const DOCS_INTL = [
        'pan_image' => 'ID document (front)',
        'aadhaar_front' => 'ID document (back)',
        'aadhaar_back' => 'Proof of address',
        'selfie' => 'Selfie holding ID',
    ];

    public const ID_TYPES = [
        'passport' => 'Passport',
        'national_id' => 'National ID card',
        'driving_licence' => 'Driving licence',
    ];

    protected function casts(): array
    {
        return ['dob' => 'date', 'reviewed_at' => 'datetime', 'checks' => 'array'];
    }

    public function isIndian(): bool
    {
        return $this->id_type === 'pan_aadhaar';
    }

    public function docLabels(): array
    {
        return $this->isIndian() ? self::DOCS : self::DOCS_INTL;
    }

    public function idTypeLabel(): string
    {
        return $this->isIndian() ? 'PAN + Aadhaar' : (self::ID_TYPES[$this->id_type] ?? $this->id_type);
    }

    public function countryName(): string
    {
        return config("countries.{$this->country}.0", (string) $this->country);
    }

    /** Masked primary ID for customer-facing screens. */
    public function maskedId(): string
    {
        $v = $this->isIndian() ? (string) $this->pan_number : (string) $this->id_number;

        return strlen($v) > 4 ? str_repeat('X', strlen($v) - 4) . substr($v, -4) : $v;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
