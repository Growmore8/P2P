<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Consent;
use App\Models\OrderLog;
use Illuminate\Http\Request;

/** History: admin actions, order status changes and client consents. */
class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['admin', 'orders', 'consents'], true) ? $request->query('tab') : 'admin';
        $q = $request->query('q');

        $items = match ($tab) {
            'orders' => OrderLog::with(['actor', 'order:id,order_no,side,currency'])
                ->when($q, fn ($w) => $w->whereHas('order', fn ($o) => $o->where('order_no', 'like', "%{$q}%"))->orWhere('note', 'like', "%{$q}%"))
                ->latest('id')->paginate(30)->withQueryString(),
            'consents' => Consent::with('user:id,name,email')
                ->when($q, fn ($w) => $w->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
                ->latest('id')->paginate(30)->withQueryString(),
            default => ActivityLog::with('actor')
                ->when($q, fn ($w) => $w->where('description', 'like', "%{$q}%"))
                ->latest('id')->paginate(30)->withQueryString(),
        };

        return view('admin.activity', ['tab' => $tab, 'items' => $items]);
    }
}
