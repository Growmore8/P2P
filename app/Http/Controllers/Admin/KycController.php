<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;

use App\Http\Controllers\Controller;
use App\Models\KycSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KycController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $items = KycSubmission::with('user')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$s}%")->orWhere('pan_number', 'like', "%{$s}%")->orWhere('id_number', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.kyc.index', ['items' => $items, 'status' => $status]);
    }

    public function show(KycSubmission $kyc)
    {
        $kyc->load('user', 'reviewer');
        $duplicates = KycSubmission::where('user_id', '!=', $kyc->user_id)
            ->where(fn ($q) => $kyc->isIndian()
                ? $q->where('pan_number', $kyc->pan_number)
                : $q->where('id_number', $kyc->id_number)->where('id_type', $kyc->id_type))
            ->with('user')->get();

        return view('admin.kyc.show', ['kyc' => $kyc, 'duplicates' => $duplicates]);
    }

    public function approve(Request $request, KycSubmission $kyc)
    {
        return $this->review($request, $kyc, 'approved');
    }

    public function reject(Request $request, KycSubmission $kyc)
    {
        $request->validate(['reject_reason' => ['required', 'string', 'max:255']]);

        return $this->review($request, $kyc, 'rejected', $request->input('reject_reason'));
    }

    private function review(Request $request, KycSubmission $kyc, string $status, ?string $reason = null)
    {
        if ($kyc->status !== 'pending') {
            return back()->with('error', 'This KYC has already been reviewed.');
        }

        DB::transaction(function () use ($request, $kyc, $status, $reason) {
            $kyc->update([
                'status' => $status,
                'reject_reason' => $reason,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            $kyc->user->forceFill(['kyc_status' => $status])->save();
        });
        ActivityLog::log('kyc.' . $status, "KYC {$status} for {$kyc->full_name} ({$kyc->user->email})" . ($reason ? ": {$reason}" : ''), $kyc);

        return redirect()->route('admin.kyc.index')->with('success', "KYC {$status} for {$kyc->full_name}.");
    }
}
