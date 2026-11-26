<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use App\Mail\OtpMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/** 6-digit email codes. Plain codes are never stored — only a hash. */
class OtpCode extends Model
{
    public const VERIFY_EMAIL = 'verify_email';
    public const RESET_PASSWORD = 'reset_password';

    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;

    use SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = ['email', 'purpose', 'code_hash', 'attempts', 'expires_at', 'consumed_at', 'created_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'created_at' => 'datetime'];
    }

    /** Seconds the user must wait before another code can be sent (0 = can send now). */
    public static function cooldown(string $email, string $purpose): int
    {
        $last = static::where('email', $email)->where('purpose', $purpose)->latest('id')->first();
        if (! $last) {
            return 0;
        }

        return max(0, self::RESEND_SECONDS - (int) $last->created_at->diffInSeconds(now(), true));
    }

    /** Create a new code (invalidating older ones) and email it. Returns the plain code. */
    public static function send(string $email, string $purpose, ?string $name = null): string
    {
        static::where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);
        static::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'created_at' => now(),
        ]);

        Mail::to($email)->send(new OtpMail($code, $purpose, $name));

        // Local testing without a mail server: remember the code so the OTP screen can show it.
        if (app()->environment('local') && config('mail.default') === 'log') {
            session()->put("dev_otp.$purpose", $code);
        }

        return $code;
    }

    /** @return true|string  true on success, otherwise an error message */
    public static function check(string $email, string $purpose, string $code)
    {
        $otp = static::where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at')->latest('id')->first();

        if (! $otp || $otp->expires_at->isPast()) {
            return 'This code has expired. Please request a new one.';
        }
        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return 'Too many wrong attempts. Please request a new code.';
        }
        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            $left = self::MAX_ATTEMPTS - $otp->attempts;

            return $left > 0 ? "Incorrect code. {$left} attempt(s) left." : 'Too many wrong attempts. Please request a new code.';
        }

        $otp->update(['consumed_at' => now()]);
        session()->forget("dev_otp.$purpose");

        return true;
    }
}
