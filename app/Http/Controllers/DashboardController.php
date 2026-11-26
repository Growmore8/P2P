<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Like the reference app, customers land straight on the Buy screen. */
    public function index(Request $request)
    {
        if ($request->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('trade', ['side' => 'buy']);
    }
}
