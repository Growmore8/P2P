@extends('layouts.admin')
@section('title', 'How you pay clients')
@section('heading', 'How you pay clients')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-modal-open="addModal">+ Add receive method</button>@parent @endsection

@section('content')
<p class="muted" style="margin:-4px 0 14px"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · The account types clients can add so you can pay them when they <b>sell</b> USDT.</p>
<div class="alert alert-info"><b>Receive methods</b> are the ways clients can get paid when they <b>sell</b> USDT (UPI, bank account, mobile wallet). Clients only see the methods for their own currency and country when adding a payout account.</div>

<div class="box">
  <div class="table-wrap"><table>
    <thead><tr><th>Method</th><th>Currency</th><th>Countries</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($methods as $m)
      <tr>
        <td><b>{{ $m->title }}</b><span class="sub">{{ \App\Models\PayoutMethod::TYPES[$m->type] ?? $m->type }}{{ $m->instructions ? ' · ' . $m->instructions : '' }}</span></td>
        <td class="mono">{{ $m->currency }}</td>
        <td style="max-width:260px">{{ $m->countriesLabel() }}</td>
        <td><span class="badge {{ $m->is_active ? 'ok' : 'bad' }}">{{ $m->is_active ? 'Active' : 'Disabled' }}</span></td>
        <td class="actions">
          <button type="button" class="btn btn-ghost btn-sm" data-toggle="pm{{ $m->id }}">Edit</button>
          <form method="POST" action="{{ route('admin.payout-methods.destroy', $m) }}" data-confirm="Delete this receive method? Saved client accounts stay but can't be added again.">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>
        </td>
      </tr>
      <tr id="pm{{ $m->id }}" hidden><td colspan="5">
        @include('admin.partials.payout-form', ['m' => $m])
      </td></tr>
    @empty
      <tr><td colspan="5" class="empty">No receive methods yet.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>

@php $addOpen = $errors->any() && old('_modal') === 'add'; @endphp
<div class="modal {{ $addOpen ? 'open' : '' }}" id="addModal" data-admin-modal aria-hidden="{{ $addOpen ? 'false' : 'true' }}">
  <div class="modal-box modal-form" id="add" role="dialog" aria-modal="true" aria-label="Add receive method">
    <div class="mf-head"><h3>Add receive method</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">×</button></div>
    <p class="muted" style="font-size:13px;margin:-6px 0 12px">An account type clients can add to get paid when they sell USDT.</p>
    @include('admin.partials.payout-form', ['m' => new \App\Models\PayoutMethod(['currency' => old('currency', 'INR'), 'type' => 'bank', 'is_active' => true]), 'modal' => true])
  </div>
</div>
@endsection
