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
            ->withCount(['orders', 'orders as all_orders_count' => fn ($q) => $q->withTrashed()])
            ->clients()
            ->when($request->query('kyc'), fn ($q, $k) => $q->where('kyc_status', $k))
            ->when($request->query('state') === 'locked', fn ($q) => $q->where('status', 'blocked'))
            ->when($request->query('state') === 'deleted', fn ($q) => $q->where('status', 'deleted'), fn ($q) => $q->where('status', '!=', 'deleted'))
            ->when($request->query('country'), fn ($q, $c) => $q->where('country', $c))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $request->user()->is_admin
                ? $w->where('name', 'like', "%{$s}%")->orWhere('client_id', strtoupper($s))->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")
                : $w->where('name', 'like', "%{$s}%")->orWhere('client_id', strtoupper($s))))
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
    /** Bring back an account that was deleted but kept for records. */
    public function restore(User $user)
    {
        abort_unless($user->isDeleted(), 404);
        $user->forceFill(['status' => 'active'])->save();
        ActivityLog::log('user.restored', "Restored client {$user->name} ({$user->client_id})", $user);

        return back()->with('success', "{$user->name}'s account is active again.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Admin accounts cannot be deleted here.');
        }
        // Clients with orders: remove the account but keep orders / KYC for records (AML rules). Needs the client ID typed in.
        if ($user->orders()->withTrashed()->exists()) { // deleted orders are still records
            if (strtoupper(trim((string) $request->input('confirm_id'))) !== $user->client_id) {
                return back()->with('error', "Type the client ID {$user->client_id} exactly to delete this account.");
            }
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill(['status' => 'deleted', 'remember_token' => null])->save();
            ActivityLog::log('user.deleted', "Deleted client {$user->name} ({$user->client_id}, {$user->email}) — orders and KYC kept for records", $user);

            return redirect()->route('admin.users.index')->with('success', "{$user->name}'s account was deleted. They can't log in; their orders and KYC are kept for records (Clients → Deleted).");
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
