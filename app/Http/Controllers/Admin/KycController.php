<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KycSubmission;
use App\Models\KycUpgrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** KYC review for all three levels: Identity (1), Video (2), EDD (3). */
class KycController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $level = (int) $request->query('level', 1);
        $level = in_array($level, [1, 2, 3], true) ? $level : 1;
        $q = $request->query('q');

        if ($level === 1) {
            $items = KycSubmission::with('user')
                ->when($status !== 'all', fn ($w) => $w->where('status', $status))
                ->when($q, fn ($w, $s) => $w->where(fn ($x) => $x
                    ->where('full_name', 'like', "%{$s}%")->orWhere('pan_number', 'like', "%{$s}%")->orWhere('id_number', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))))
                ->latest()->paginate(20)->withQueryString();
        } else {
            $items = KycUpgrade::with('user.kyc')->where('level', $level)
                ->when($status !== 'all', fn ($w) => $w->where('status', $status))
                ->when($q, fn ($w, $s) => $w->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
                ->latest()->paginate(20)->withQueryString();
        }

        $pending = [
            1 => KycSubmission::where('status', 'pending')->count(),
            2 => KycUpgrade::where('level', 2)->where('status', 'pending')->count(),
            3 => KycUpgrade::where('level', 3)->where('status', 'pending')->count(),
        ];

        return view('admin.kyc.index', ['items' => $items, 'status' => $status, 'level' => $level, 'pending' => $pending]);
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
            $user = $kyc->user;
            $user->forceFill([
                'kyc_status' => $status,
                'kyc_level' => $status === 'approved' ? max(1, (int) $user->kyc_level) : $user->kyc_level,
            ])->save();
        });
        ActivityLog::log('kyc.' . $status, "Identity KYC {$status} for {$kyc->full_name} ({$kyc->user->email})" . ($reason ? ": {$reason}" : ''), $kyc);

        return redirect()->route('admin.kyc.index')->with('success', "KYC {$status} for {$kyc->full_name}.");
    }

    /* ---------------- Level 2 (video) & Level 3 (EDD) ---------------- */

    public function showUpgrade(KycUpgrade $upgrade)
    {
        $upgrade->load('user.kyc', 'reviewer');

        return view('admin.kyc.upgrade', [
            'up' => $upgrade,
            'history' => KycUpgrade::where('user_id', $upgrade->user_id)->where('level', $upgrade->level)->where('id', '!=', $upgrade->id)->latest()->get(),
        ]);
    }

    public function approveUpgrade(Request $request, KycUpgrade $upgrade)
    {
        return $this->reviewUpgrade($request, $upgrade, 'approved');
    }

    public function rejectUpgrade(Request $request, KycUpgrade $upgrade)
    {
        $request->validate(['reject_reason' => ['required', 'string', 'max:255']]);

        return $this->reviewUpgrade($request, $upgrade, 'rejected', $request->input('reject_reason'));
    }

    private function reviewUpgrade(Request $request, KycUpgrade $upgrade, string $status, ?string $reason = null)
    {
        if ($upgrade->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }
        $user = $upgrade->user;
        if ($status === 'approved' && $user->kyc_status !== 'approved') {
            return back()->with('error', 'Approve the client\'s identity verification (Level 1) first.');
        }
        if ($status === 'approved' && $upgrade->level === KycUpgrade::EDD && $user->kyc_level < KycUpgrade::VIDEO) {
            return back()->with('error', 'Approve the client\'s video verification (Level 2) first.');
        }

        DB::transaction(function () use ($request, $upgrade, $user, $status, $reason) {
            $upgrade->update(['status' => $status, 'reject_reason' => $reason, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            if ($status === 'approved') {
                $user->forceFill(['kyc_level' => max((int) $user->kyc_level, $upgrade->level)])->save();
            }
        });
        ActivityLog::log('kyc_level' . $upgrade->level . '.' . $status, "{$upgrade->levelLabel()} {$status} for {$user->email}" . ($reason ? ": {$reason}" : ''), $upgrade);

        return redirect()->route('admin.kyc.index', ['level' => $upgrade->level])->with('success', "{$upgrade->levelLabel()} {$status} for {$user->name}.");
    }
}
