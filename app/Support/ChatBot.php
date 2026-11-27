<?php

namespace App\Support;

use App\Models\Currency;
use App\Models\Setting;

/**
 * Free rule-based assistant: instant answers to common questions using live settings.
 * Returns null when it doesn't know — the message then simply waits for the team.
 * (A paid AI model could be plugged in here later.)
 */
class ChatBot
{
    public static function greeting(): string
    {
        return "Hi! 👋 I'm the SRM Assistant. Ask me about rates, buying or selling USDT, KYC or payments — or type your question and our team will reply here and by email.";
    }

    public static function reply(string $text): ?string
    {
        $t = mb_strtolower($text);
        $s = Setting::values();
        // match at the start of a word, so "hi" doesn't fire on "this" but "rate" matches "rates"
        $has = fn (array $words) => collect($words)->contains(fn ($w) => preg_match('/(?<![\p{L}\p{N}])' . preg_quote(trim($w), '/') . '/u', $t) === 1);

        if ($has(['rate', 'price', 'how much', 'today'])) {
            $lines = Currency::active()->get()->map(fn ($c) => "• USDT/{$c->code}: we sell at {$c->symbol}" . number_format($c->buy_rate, 2) . ", we buy at {$c->symbol}" . number_format($c->sell_rate, 2))->join("\n");

            return "Today's desk rates:\n{$lines}\nThe rate is locked when you place an order.";
        }
        if ($has(['kyc', 'verify', 'verification', 'document', 'pan', 'aadhaar', 'passport'])) {
            return "Verification has 3 levels:\n1) Identity — PAN + Aadhaar (or passport/ID) and a selfie\n2) Video — a short selfie video reading a statement\n3) EDD — income / source-of-funds documents\nEach level raises your monthly limit. Start from Profile → Verification after signing up.";
        }
        if ($has(['limit', 'maximum', 'minimum', 'max ', 'min '])) {
            $c = Currency::active()->first();

            return $c
                ? "Per order: {$c->symbol}" . number_format($c->min_amount) . ' – ' . $c->symbol . number_format($c->max_amount) . ".\nMonthly limits grow with your verification level (Identity → Video → EDD). See Profile → Verification."
                : 'Limits depend on your verification level — see Profile → Verification.';
        }
        if ($has(['buy', 'purchase'])) {
            return "To buy USDT:\n1) Sign up and verify\n2) Enter the amount, network and your wallet address\n3) Pay to the shown UPI / bank details within {$s['order_expiry_minutes']} minutes and submit the UTR\n4) We verify and send USDT to your wallet.";
        }
        if ($has(['sell'])) {
            return "To sell USDT:\n1) Sign up, verify and add your bank / UPI account\n2) Enter the USDT amount and network\n3) Send USDT to the address shown and submit the transaction hash\n4) We verify and pay to your account (TDS may apply in India).";
        }
        if ($has(['how long', 'time', 'when', 'delay', 'pending', 'not received', 'waiting'])) {
            return "Orders usually complete in {$s['avg_completion']} after payment is verified. Unpaid orders are auto-cancelled after {$s['order_expiry_minutes']} minutes.\nIf your order is delayed, share your order number here and our team will check.";
        }
        if ($has(['hour', 'open', 'timing', 'available'])) {
            return "Our desk hours: {$s['desk_hours']}. Messages sent outside these hours are answered in the next session.";
        }
        if ($has(['network', 'trc20', 'bep20', 'erc20', 'polygon', 'wallet'])) {
            return 'We support USDT on TRC20, BEP20, ERC20 and Polygon. Always double-check the network before sending — the wrong network means permanent loss.';
        }
        if ($has(['safe', 'scam', 'fraud', 'trust', 'legit'])) {
            return 'Only pay to the details shown inside your logged-in order page. We never ask for your password, OTP, UPI PIN or wallet seed phrase.';
        }
        if ($has(['hi', 'hello', 'hey', 'vanakkam', 'namaste'])) {
            return 'Hello! How can we help you today? You can ask about rates, buying, selling or verification.';
        }

        return null;
    }

    public static function handoff(): string
    {
        return "Thanks! I've passed your message to our team. They'll reply here — and by email if you shared it. Desk hours: " . Setting::get('desk_hours') . '.';
    }
}
