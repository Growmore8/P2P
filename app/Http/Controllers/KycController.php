<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\KycSubmission;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KycController extends Controller
{
    public function show(Request $request)
    {
        return view('user.kyc', ['user' => $request->user(), 'kyc' => $request->user()->kyc]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (in_array($user->kyc_status, ['pending', 'approved'], true)) {
            return back()->with('error', 'Your KYC is already ' . $user->kyc_status . '.');
        }

        $india = $user->isIndian();
        // Level 1 = one ID card, front and back. The card type is worked out from the number.
        $number = strtoupper(preg_replace('/[\s\-]/', '', (string) $request->input('id_number')));
        $request->merge(['id_number' => $number]);
        $type = $india ? (preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $number) ? 'pan_card' : 'aadhaar') : 'id_document';
        $file = ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];
        $rules = [
            'full_name' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date', 'before:-18 years'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'state' => [$india ? 'required' : 'nullable', 'string', 'max:80'],
            'pincode' => $india ? ['required', 'digits:6'] : ['required', 'regex:/^[A-Za-z0-9 \-]{3,12}$/'],
            'id_number' => $india ? ['required', 'regex:/^([A-Z]{5}[0-9]{4}[A-Z]|[2-9][0-9]{11})$/'] : ['required', 'regex:/^[A-Z0-9\-]{4,30}$/'],
            'pan_image' => array_merge(['required'], $file),                              // ID card front
            'aadhaar_front' => array_merge([$india ? 'required' : 'nullable'], $file),    // ID card back (passports have none)
            'consent' => ['accepted'],
        ];

        $data = $request->validate($rules, [
            'dob.before' => 'You must be at least 18 years old.',
            'id_number.regex' => $india ? 'Enter a valid PAN (e.g. ABCDE1234F) or 12-digit Aadhaar number.' : 'Enter a valid document number (letters, numbers and dashes only).',
            'pincode.regex' => 'Enter a valid postal code.',
            'pan_image.required' => 'Upload the front of your ID card.',
            'aadhaar_front.required' => 'Upload the back of your ID card.',
        ]);
        $data['id_type'] = $type;
        $label = $india ? KycSubmission::ID_TYPES_IN[$type] : 'ID card';

        $paths = [];
        foreach (['pan_image', 'aadhaar_front'] as $doc) {
            if ($request->hasFile($doc)) {
                $paths[$doc] = $request->file($doc)->store("kyc/{$user->id}", 'local');
            }
        }

        $checks = $this->runChecks($request, $paths, ['pan_image' => "{$label} — Front", 'aadhaar_front' => "{$label} — Back"]);

        DB::transaction(function () use ($user, $data, $paths, $india, $checks, $type) {
            $user->kycSubmissions()->create(array_merge(['aadhaar_front' => '', 'aadhaar_back' => '', 'selfie' => ''], $paths, [
                'checks' => $checks,
                'country' => $user->country,
                'id_type' => $data['id_type'],
                'id_number' => $india ? null : $data['id_number'],
                'full_name' => $data['full_name'],
                'dob' => $data['dob'],
                'pan_number' => $type === 'pan_card' ? $data['id_number'] : null,
                // Only the last 4 digits of Aadhaar are stored (Aadhaar Act / UIDAI guidance).
                'aadhaar_last4' => $type === 'aadhaar' ? substr($data['id_number'], -4) : null,
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'pincode' => strtoupper($data['pincode']),
                'status' => 'pending',
            ]));
            $user->forceFill(['kyc_status' => 'pending'])->save();
        });
        Consent::record($user, ['kyc'], $request);

        return redirect()->route('kyc.verifying');
    }

    /** Animated "checking your documents" screen shown right after submission. */
    public function verifying(Request $request)
    {
        $kyc = $request->user()->kyc;
        if (! $kyc || empty($kyc->checks)) {
            return redirect()->route('kyc.show');
        }

        return view('user.kyc-verifying', ['kyc' => $kyc]);
    }

    /**
     * Real automatic checks on each uploaded file (received, format, image size / quality),
     * each tagged with a name from the verification team list in Settings.
     */
    private function runChecks(Request $request, array $paths, array $labels): array
    {

        $team = collect(preg_split('/\r\n|\r|\n|,/', (string) Setting::get('kyc_agent_names')))
            ->map(fn ($n) => trim($n))->filter()->unique()->shuffle()->values();
        if ($team->isEmpty()) {
            $team = collect(['Verification team']);
        }

        $out = [];
        $i = 0;
        foreach ($labels as $key => $label) {
            if (! isset($paths[$key])) {
                continue;
            }
            $file = $request->file($key);
            $mime = (string) $file->getMimeType();
            $note = 'File received';
            $ok = true;
            if (str_starts_with($mime, 'image/')) {
                $size = @getimagesize($file->getRealPath());
                if ($size) {
                    [$w, $h] = $size;
                    $ok = min($w, $h) >= 480;
                    $note = $ok ? "Clear image ({$w}×{$h})" : "Low resolution ({$w}×{$h}) — reviewer may ask for a clearer copy";
                } else {
                    $ok = false;
                    $note = 'Image could not be read — reviewer may ask you to re-upload';
                }
            } elseif ($mime === 'application/pdf') {
                $note = 'PDF document received (' . round($file->getSize() / 1024) . ' KB)';
            } elseif (str_starts_with($mime, 'video/')) {
                $note = 'Video received (' . number_format($file->getSize() / 1048576, 1) . ' MB)';
            }
            $out[] = ['key' => $key, 'label' => $label, 'agent' => $team[$i % $team->count()], 'ok' => $ok, 'note' => $note];
            $i++;
        }

        return $out;
    }
}
