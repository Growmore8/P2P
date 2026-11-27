<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->withCount('orders')
            ->where('is_admin', false)
            ->when($request->query('kyc'), fn ($q, $k) => $q->where('kyc_status', $k))
            ->when($request->query('state') === 'locked', fn ($q) => $q->where('status', 'blocked'))
            ->when($request->query('country'), fn ($q, $c) => $q->where('country', $c))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('client_id', strtoupper($s))->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function show(User $user)
    {
        $user->load(['kycSubmissions' => fn ($q) => $q->latest(), 'bankAccounts', 'consents' => fn ($q) => $q->latest('id')]);

        return view('admin.users.show', [
            'u' => $user,
            'orders' => $user->orders()->latest()->limit(20)->get(),
            'volume' => $user->orders()->where('status', 'completed')->selectRaw('currency, sum(fiat_amount) as total, sum(usdt_amount) as usdt')->groupBy('currency')->get(),
        ]);
    }

    public function toggleBlock(Request $request, User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Admin accounts cannot be blocked here.');
        }
        $user->forceFill(['status' => $user->isBlocked() ? 'active' : 'blocked'])->save();
        ActivityLog::log('user.' . $user->status, "Client {$user->email} set to {$user->status}", $user);

        return back()->with('success', $user->name . ($user->isBlocked() ? ' is now inactive and cannot log in.' : ' is now active.'));
    }

    /**
     * Permanently delete a client who has never placed an order (spam / unused sign-ups).
     * Clients with orders are kept for record-keeping (AML rules) — deactivate them instead.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Admin accounts cannot be deleted here.');
        }
        if ($user->orders()->exists()) {
            return back()->with('error', "{$user->name} has orders, so the account must be kept for records. Deactivate it instead.");
        }

        $label = "{$user->name} ({$user->client_id}, {$user->email})";
        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete(); // KYC, consents, bank accounts and tickets are removed with it
        });
        foreach (["kyc/{$user->id}", "tickets/{$user->id}", "proofs/{$user->id}"] as $dir) {
            Storage::disk('local')->deleteDirectory($dir);
        }
        ActivityLog::log('user.deleted', "Deleted client {$label}");

        return redirect()->route('admin.users.index')->with('success', "{$user->name}'s account was deleted permanently.");
    }
}
