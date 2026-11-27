<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['s' => Setting::values()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'order_expiry_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'terms_version' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'kyc_agent_names' => ['required', 'string', 'max:1500'],
            'desk_hours' => ['required', 'string', 'max:100'],
            'avg_completion' => ['required', 'string', 'max:50'],
            'support_whatsapp' => ['required', 'regex:/^\d{10,15}$/'],
            'support_phone' => ['required', 'string', 'max:30'],
            'support_email' => ['required', 'email'],
            'ticket_email' => ['required', 'email'],
            'instagram_url' => ['nullable', 'url'],
            'announcement' => ['nullable', 'string', 'max:200'],
        ]);

        $data['trading_enabled'] = $request->boolean('trading_enabled') ? '1' : '0';
        $oldVersion = Setting::get('terms_version');
        Setting::put($data);

        ActivityLog::log('settings.updated', 'Settings updated'
            . ($oldVersion !== $data['terms_version'] ? " · terms version {$oldVersion} → {$data['terms_version']} (clients must re-accept)" : '')
            . ($data['trading_enabled'] === '1' ? '' : ' · trading paused'));

        return back()->with('success', 'Settings saved.');
    }
}
