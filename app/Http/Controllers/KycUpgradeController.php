<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\KycUpgrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Level 2 (selfie video) and Level 3 (EDD) verification. */
class KycUpgradeController extends Controller
{
    public const LANGS = ['en' => 'English', 'hi' => 'हिंदी', 'ta' => 'தமிழ்'];

    public function video(Request $request)
    {
        $user = $request->user();
        $state = $user->verificationSteps()[KycUpgrade::VIDEO]['state'];
        if ($state === 'locked') {
            return redirect()->route('kyc.show')->with('error', 'Complete identity verification first.');
        }
        if (in_array($state, ['done', 'review'], true)) {
            return redirect()->route('account.limits');
        }
        $name = $user->kyc?->full_name ?: $user->name;

        return view('user.kyc-video', [
            'scripts' => collect(self::LANGS)->mapWithKeys(fn ($l, $k) => [$k => KycUpgrade::script($name, $k)])->all(),
            'langs' => self::LANGS,
            'rejected' => $state === 'rejected' ? $user->upgrade(KycUpgrade::VIDEO) : null,
        ]);
    }

    public function storeVideo(Request $request)
    {
        $user = $request->user();
        if ($user->verificationSteps()[KycUpgrade::VIDEO]['state'] !== 'next'
            && $user->verificationSteps()[KycUpgrade::VIDEO]['state'] !== 'rejected') {
            return $this->reply($request, ['message' => 'Video verification is not available right now.'], 409);
        }

        $data = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/webm,video/mp4,video/quicktime,video/x-matroska,video/3gpp', 'max:30720'],
            'lang' => ['required', Rule::in(array_keys(self::LANGS))],
            'consent' => ['accepted'],
        ], [
            'video.required' => 'Please record your video.',
            'video.max' => 'The video is too large (max 30 MB). Please record a shorter clip.',
            'consent.accepted' => 'Please allow us to use this video for verification.',
        ]);

        $path = $request->file('video')->store("kyc/{$user->id}", 'local');
        $user->kycUpgrades()->create([
            'level' => KycUpgrade::VIDEO,
            'status' => 'pending',
            'files' => ['video' => $path],
            'details' => [
                'lang' => $data['lang'],
                'script' => KycUpgrade::script($user->kyc?->full_name ?: $user->name, $data['lang']),
                'size_mb' => round($request->file('video')->getSize() / 1048576, 1),
            ],
        ]);
        Consent::record($user, ['video_kyc'], $request, 'level2');

        return $this->reply($request, ['redirect' => route('account.limits')], 200, 'Video submitted. Our team will review it shortly.');
    }

    public function edd(Request $request)
    {
        $user = $request->user();
        $state = $user->verificationSteps()[KycUpgrade::EDD]['state'];
        if (! in_array($state, ['next', 'rejected'], true)) {
            return redirect()->route('account.limits')->with('error', $state === 'locked' ? 'Complete video verification first.' : null);
        }

        return view('user.kyc-edd', [
            'rejected' => $state === 'rejected' ? $user->upgrade(KycUpgrade::EDD) : null,
            'currency' => $user->currencyCode(),
        ]);
    }

    public function storeEdd(Request $request)
    {
        $user = $request->user();
        if (! in_array($user->verificationSteps()[KycUpgrade::EDD]['state'], ['next', 'rejected'], true)) {
            return redirect()->route('account.limits');
        }
        $file = ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'];
        $data = $request->validate([
            'occupation' => ['required', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:120'],
            'annual_income' => ['required', Rule::in(['<5L', '5-10L', '10-25L', '25-50L', '50L+'])],
            'source_of_funds' => ['required', Rule::in(['Salary', 'Business income', 'Investments / trading', 'Savings', 'Other'])],
            'source_details' => ['nullable', 'string', 'max:500'],
            'purpose' => ['required', Rule::in(['Personal investment', 'Business payments', 'Family remittance', 'Other'])],
            'bank_statement' => ['required', ...$file],
            'income_proof' => ['required', ...$file],
            'extra_doc' => ['nullable', ...$file],
            'declaration' => ['accepted'],
        ], ['declaration.accepted' => 'Please confirm the declaration.']);

        $files = [];
        foreach (array_keys(KycUpgrade::EDD_FILES) as $k) {
            if ($request->hasFile($k)) {
                $files[$k] = $request->file($k)->store("kyc/{$user->id}", 'local');
            }
        }
        $user->kycUpgrades()->create([
            'level' => KycUpgrade::EDD,
            'status' => 'pending',
            'files' => $files,
            'details' => collect($data)->only(['occupation', 'employer', 'annual_income', 'source_of_funds', 'source_details', 'purpose'])->all(),
        ]);
        Consent::record($user, ['kyc'], $request, 'level3');

        return redirect()->route('account.limits')->with('success', 'EDD documents submitted. Our compliance team will review them.');
    }

    /** JSON for the in-browser recorder, redirect for normal form posts. */
    private function reply(Request $request, array $payload, int $status = 200, ?string $flash = null)
    {
        if ($flash) {
            session()->flash('success', $flash);
        }
        if ($request->expectsJson()) {
            return response()->json($payload, $status);
        }

        return isset($payload['redirect']) ? redirect($payload['redirect']) : back()->with('error', $payload['message'] ?? 'Something went wrong.');
    }
}
