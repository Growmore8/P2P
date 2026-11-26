<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;

use App\Http\Controllers\Controller;
use App\Models\DepositMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositMethodController extends Controller
{
    public function index()
    {
        return view('admin.methods.index', ['methods' => DepositMethod::orderBy('sort')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.methods.form', ['method' => new DepositMethod(['type' => 'upi', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $m = DepositMethod::create($this->validated($request));
        ActivityLog::log('deposit_method.created', "Added deposit method {$m->title} ({$m->currency})", $m);

        return redirect()->route('admin.methods.index')->with('success', 'Deposit method added.');
    }

    public function edit(DepositMethod $method)
    {
        return view('admin.methods.form', ['method' => $method]);
    }

    public function update(Request $request, DepositMethod $method)
    {
        $method->update($this->validated($request, $method));
        ActivityLog::log('deposit_method.updated', "Updated deposit method {$method->title} ({$method->currency})", $method);

        return redirect()->route('admin.methods.index')->with('success', 'Deposit method updated.');
    }

    public function toggle(DepositMethod $method)
    {
        $method->update(['is_active' => ! $method->is_active]);
        ActivityLog::log('deposit_method.toggled', "{$method->title} " . ($method->is_active ? 'enabled' : 'disabled'), $method);

        return back()->with('success', $method->title . ($method->is_active ? ' enabled.' : ' disabled.'));
    }

    public function destroy(DepositMethod $method)
    {
        // Old QR files are kept so past orders still show the details the customer paid to.
        ActivityLog::log('deposit_method.deleted', "Deleted deposit method {$method->title}", $method);
        $method->delete();

        return back()->with('success', 'Deposit method deleted.');
    }

    private function validated(Request $request, ?DepositMethod $method = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(DepositMethod::TYPES))],
            'currency' => ['required', 'exists:currencies,code'],
            'countries' => ['nullable', 'array'],
            'countries.*' => [Rule::in(array_keys(config('countries')))],
            'title' => ['required', 'string', 'max:100'],
            'upi_id' => ['required_if:type,upi', 'nullable', 'string', 'max:100'],
            'qr' => ['nullable', 'image', 'max:2048'],
            'account_holder' => ['required_if:type,bank', 'nullable', 'string', 'max:100'],
            'account_number' => ['required_if:type,bank', 'required_if:type,wallet', 'nullable', 'regex:/^[A-Za-z0-9 +\-]{5,34}$/'],
            'ifsc' => ['required_if:type,bank', 'nullable', 'regex:/^[A-Za-z0-9 \-]{3,20}$/'],
            'bank_name' => ['required_if:type,bank', 'nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('qr')) {
            $data['qr_image'] = $request->file('qr')->store('qr', 'local');
        }
        unset($data['qr']);
        $data['ifsc'] = isset($data['ifsc']) ? strtoupper($data['ifsc']) : null;
        $data['countries'] = array_values($data['countries'] ?? []) ?: null;
        $data['min_amount'] = $data['min_amount'] ?? 0;
        $data['max_amount'] = $data['max_amount'] ?? 0;
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
