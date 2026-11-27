<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Level 2 (video) and Level 3 (EDD) verification requests. */
class KycUpgrade extends Model
{
    public const VIDEO = 2;
    public const EDD = 3;

    public const LEVELS = [
        1 => 'Identity Verification',
        2 => 'Video Verification',
        3 => 'EDD',
    ];

    public const EDD_FILES = [
        'bank_statement' => 'Bank statement (last 6 months, showing your address)',
        'income_proof' => 'Income proof (ITR / salary slip / business proof)',
        'extra_doc' => 'Additional document',
    ];

    protected $fillable = ['user_id', 'level', 'status', 'files', 'details', 'reject_reason', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['files' => 'array', 'details' => 'array', 'reviewed_at' => 'datetime', 'level' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function levelLabel(): string
    {
        return self::LEVELS[$this->level] ?? 'Level ' . $this->level;
    }

    /** Read-aloud script for the video, in the chosen language. */
    public static function script(string $name, string $lang = 'en'): string
    {
        $name = mb_strtoupper(trim($name));

        return match ($lang) {
            'hi' => "मेरा नाम {$name} है।\nमैं यह वीडियो SRM Fintech Solutions KYC सत्यापन के लिए रिकॉर्ड कर रहा/रही हूँ।\nकोई भी मुझे यह वीडियो बनाने के लिए मजबूर नहीं कर रहा है और न ही मदद कर रहा है।\nमैं इस KYC और इस खाते से किए गए सभी लेन-देन के लिए पूरी तरह ज़िम्मेदार हूँ।",
            'ta' => "என் பெயர் {$name}.\nSRM Fintech Solutions KYC சரிபார்ப்பிற்காக நான் இந்த வீடியோவைப் பதிவு செய்கிறேன்.\nஇந்த வீடியோவை உருவாக்க யாரும் என்னை வற்புறுத்தவோ உதவவோ இல்லை.\nஇந்த KYC மற்றும் இந்தக் கணக்கைப் பயன்படுத்தி செய்யப்படும் அனைத்து பரிவர்த்தனைகளுக்கும் நானே முழுப் பொறுப்பு.",
            default => "My name is {$name}.\nI am recording this video for SRM Fintech Solutions KYC verification.\nNo one is forcing or helping me to make this video.\nI am fully responsible for this KYC and for any transactions done using this account.",
        };
    }
}
