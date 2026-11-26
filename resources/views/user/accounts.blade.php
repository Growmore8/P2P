@extends('layouts.app')
@section('title', 'Bank Accounts')
@section('back', route('account'))
@section('heading', 'Bank Accounts')

@section('content')
@php $india = auth()->user()->isIndian(); @endphp
<p class="section-label">{{ $accounts->count() }} account(s)</p>

@forelse ($accounts as $a)
  <div class="lrow">
    <span class="pr-ico" style="width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:var(--card)">{{ ['upi' => '📲', 'wallet' => '👛'][$a->type] ?? '🏦' }}</span>
    <div class="lmain"><b>{{ $a->label() }}</b><small>{{ $a->holder_name }} · {{ $a->currency }}@if ($a->type === 'bank' && $a->ifsc) · {{ $a->ifsc }}@endif</small></div>
    <form method="POST" action="{{ route('accounts.destroy', $a) }}" data-confirm="Remove this account?">@csrf @method('DELETE')
      <button class="icon-btn" aria-label="Remove" title="Remove">🗑</button>
    </form>
  </div>
@empty
  <div class="empty-card">
    <div class="hero-icon">🏦</div>
    <b>You've not added any Bank Account yet.</b>
    <p>Please click on the button below to add a Bank Account.</p>
  </div>
@endforelse

<div class="stick-bottom">
  @if ($methods->isEmpty())
    <div class="alert alert-info">Receiving money isn't available in your country yet.</div>
  @else
    <button type="button" class="btn btn-primary btn-block btn-lg" data-sheet-open="sheetAdd">+ Add Bank Account</button>
  @endif
</div>
@endsection

@section('sheets')
@if ($methods->isNotEmpty())
<div class="sheet-backdrop {{ $errors->any() ? 'open' : '' }}" id="sheetAdd">
  <div class="sheet">
    <div class="sheet-head">Add Bank Account <button type="button" data-sheet-close aria-label="Close">×</button></div>
    <form method="POST" action="{{ route('accounts.store') }}" class="form">
      @csrf
      @if ($errors->any())<div class="alert alert-error" style="margin:0">@foreach ($errors->all() as $e){{ $e }}<br>@endforeach</div>@endif
      <div><label>Receive via</label>
        <select name="payout_method_id" class="input" data-type-toggle>
          @foreach ($methods as $m)
            <option value="{{ $m->id }}" data-kind="{{ $m->type }}" @selected(old('payout_method_id') == $m->id)>{{ $m->title }}</option>
          @endforeach
        </select>
      </div>
      <div><label>Account holder name</label><input class="input" name="holder_name" value="{{ old('holder_name', auth()->user()->kyc?->full_name ?? auth()->user()->name) }}" required><span class="hint">Must match your verified name.</span></div>
      <div data-type="upi"><label>UPI ID</label><input class="input" name="upi_id" value="{{ old('upi_id') }}" placeholder="name@okbank"></div>
      <div data-type="bank"><label>Bank name</label><input class="input" name="bank_name" value="{{ old('bank_name') }}"></div>
      <div data-type="wallet"><label>Wallet provider</label><input class="input" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. eZ Cash, mCash, M-Pesa"></div>
      <div data-type="bank wallet"><label><span data-type="bank">Account number / IBAN</span><span data-type="wallet">Wallet number</span></label><input class="input" name="account_number" value="{{ old('account_number') }}"></div>
      <div data-type="bank wallet"><label>Confirm number</label><input class="input" name="account_number_confirmation" autocomplete="off"></div>
      <div data-type="bank"><label>{{ $india ? 'IFSC' : 'SWIFT / branch code' }}</label><input class="input" name="ifsc" value="{{ old('ifsc') }}" maxlength="20" style="text-transform:uppercase"></div>
      <button class="btn btn-primary btn-block btn-lg">Save account</button>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
  // Two inputs share name="bank_name" (bank vs wallet). Disable the hidden one so only the visible value is sent.
  (function () {
    var form = document.querySelector('#sheetAdd form'); if (!form) return;
    var sel = form.querySelector('[data-type-toggle]');
    var sync = function () {
      form.querySelectorAll('[data-type] input').forEach(function (i) { i.disabled = i.closest('[data-type]').style.display === 'none'; });
    };
    sel.addEventListener('change', function () { setTimeout(sync, 0); }); sync();
  })();
</script>
@endpush
