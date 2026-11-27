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
        $file = ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];
        $rules = [
            'full_name' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date', 'before:-18 years'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'state' => [$india ? 'required' : 'nullable', 'string', 'max:80'],
            'pincode' => $india ? ['required', 'digits:6'] : ['required', 'regex:/^[A-Za-z0-9 \-]{3,12}$/'],
            'pan_image' => $file,
            'aadhaar_front' => $india ? $file : ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'aadhaar_back' => $file,
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'consent' => ['accepted'],
        ];
        $rules += $india
            ? ['pan_number' => ['required', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'], 'aadhaar_number' => ['required', 'regex:/^[2-9][0-9]{11}$/']]
            : ['id_type' => ['required', 'in:' . implode(',', array_keys(KycSubmission::ID_TYPES))], 'id_number' => ['required', 'regex:/^[A-Za-z0-9\-]{4,30}$/']];

        $data = $request->validate($rules, [
            'dob.before' => 'You must be at least 18 years old.',
            'pan_number.regex' => 'Enter a valid PAN (e.g. ABCDE1234F).',
            'aadhaar_number.regex' => 'Enter a valid 12-digit Aadhaar number.',
            'id_number.regex' => 'Enter a valid document number (letters, numbers and dashes only).',
            'pincode.regex' => 'Enter a valid postal code.',
            'aadhaar_back.required' => $india ? 'Upload the back of your Aadhaar.' : 'Upload a proof of address.',
        ]);

        $paths = [];
        foreach (array_keys(KycSubmission::DOCS) as $doc) {
            if ($request->hasFile($doc)) {
                $paths[$doc] = $request->file($doc)->store("kyc/{$user->id}", 'local');
            }
        }

        $checks = $this->runChecks($request, $paths, $india);

        DB::transaction(function () use ($user, $data, $paths, $india, $checks) {
            $user->kycSubmissions()->create(array_merge(['aadhaar_front' => ''], $paths, [
                'checks' => $checks,
                'country' => $user->country,
                'id_type' => $india ? 'pan_aadhaar' : $data['id_type'],
                'id_number' => $india ? null : strtoupper($data['id_number']),
                'full_name' => $data['full_name'],
                'dob' => $data['dob'],
                'pan_number' => $india ? strtoupper($data['pan_number']) : null,
                // Only the last 4 digits of Aadhaar are stored (Aadhaar Act / UIDAI guidance).
                'aadhaar_last4' => $india ? substr($data['aadhaar_number'], -4) : null,
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
    private function runChecks(Request $request, array $paths, bool $india): array
    {
        $labels = $india
            ? ['pan_image' => 'PAN Card', 'aadhaar_front' => 'Aadhaar — Front', 'aadhaar_back' => 'Aadhaar — Back', 'selfie' => 'Selfie with ID', 'video' => 'Video verification']
            : ['pan_image' => 'ID Document — Front', 'aadhaar_front' => 'ID Document — Back', 'aadhaar_back' => 'Proof of Address', 'selfie' => 'Selfie with ID', 'video' => 'Video verification'];

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
