<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->hasCurrentConsent()) {
            return redirect()->route('dashboard');
        }

        return view('user.consent', ['version' => Consent::currentVersion()]);
    }

    public function store(Request $request)
    {
        $request->validate(
            ['terms' => ['accepted'], 'risk' => ['accepted'], 'age' => ['accepted']],
            ['*.accepted' => 'Please tick every box to continue.']
        );
        Consent::record($request->user(), Consent::ACCOUNT_TYPES, $request);

        return redirect()->intended(route('dashboard'))->with('success', 'Thank you. Your agreement has been recorded.');
    }
}
