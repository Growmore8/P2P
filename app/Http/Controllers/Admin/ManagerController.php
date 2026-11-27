<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/** Admin → Managers: staff who review incoming payments before the admin approves an order. */
class ManagerController extends Controller
{
    public function index()
    {
        $managers = User::where('role', User::MANAGER)->where('is_admin', false)->orderBy('name')->get();
        $reviews = Order::whereIn('reviewed_by', $managers->pluck('id'))->selectRaw('reviewed_by, count(*) as n')->groupBy('reviewed_by')->pluck('n', 'reviewed_by');

        return view('admin.managers', ['managers' => $managers, 'reviews' => $reviews]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email'))), 'phone' => preg_replace('/[^\d+]/', '', (string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'regex:/^\+?\d{8,15}$/', 'unique:users,phone'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ], ['phone.regex' => 'Enter a valid phone number.', 'email.unique' => 'This email already has an account.', 'phone.unique' => 'This phone number already has an account.']);

        $m = new User(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'password' => $data['password'], 'country' => 'IN']);
        $m->forceFill(['role' => User::MANAGER, 'is_admin' => false, 'status' => 'active', 'email_verified_at' => now()])->save();
        ActivityLog::log('manager.created', "Added manager {$m->email}", $m);

        return redirect()->route('admin.managers.index')->with('success', "{$m->name} added. They can log in with {$m->email} and the password you set.");
    }

    public function toggle(User $manager)
    {
        $this->ensureManager($manager);
        $manager->forceFill(['status' => $manager->isBlocked() ? 'active' : 'blocked'])->save();
        if ($manager->isBlocked()) {
            DB::table('sessions')->where('user_id', $manager->id)->delete(); // log them out now
        }
        ActivityLog::log('manager.' . ($manager->isBlocked() ? 'deactivated' : 'activated'), "Manager {$manager->email} " . ($manager->isBlocked() ? 'deactivated' : 'activated'), $manager);

        return back()->with('success', $manager->name . ($manager->isBlocked() ? ' is now inactive and cannot log in.' : ' is active again.'));
    }

    public function password(Request $request, User $manager)
    {
        $this->ensureManager($manager);
        $request->validate(['password' => ['required', Password::min(8)->letters()->numbers()]]);
        $manager->forceFill(['password' => $request->input('password')])->save();
        DB::table('sessions')->where('user_id', $manager->id)->delete();
        ActivityLog::log('manager.password', "Reset password for manager {$manager->email}", $manager);

        return back()->with('success', "New password set for {$manager->name}.");
    }

    public function destroy(User $manager)
    {
        $this->ensureManager($manager);
        $label = "{$manager->name} ({$manager->email})";
        DB::table('sessions')->where('user_id', $manager->id)->delete();
        $manager->delete(); // their past reviews stay on the orders (reviewer shown as removed)
        ActivityLog::log('manager.deleted', "Removed manager {$label}");

        return back()->with('success', "Manager {$label} removed.");
    }

    private function ensureManager(User $user): void
    {
        abort_unless($user->isManager(), 404);
    }
}
