<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Sends emails without ever breaking the user's action if the mail server is down. */
class Notify
{
    public static function mail(?string $to, Mailable $mail): bool
    {
        if (! $to) {
            return false;
        }
        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** The support inbox (Admin → Settings → Support email). */
    public static function staff(Mailable $mail): bool
    {
        return self::mail((string) Setting::get('support_email'), $mail);
    }
}
