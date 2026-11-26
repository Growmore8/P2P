@extends('layouts.admin')
@section('title', $method->exists ? 'Edit Deposit Method' : 'Add Deposit Method')
@section('heading', $method->exists ? 'Edit Deposit Method' : 'Add Deposit Method')

@section('content')
@php $cur = old('currency', $method->currency ?? 'INR'); @endphp
<a href="{{ route('admin.methods.index') }}" class="muted" style="font-size:14px">← Back</a>
<form method="POST" enctype="multipart/form-data" class="box form" style="margin-top:14px;max-width:860px"
      action="{{ $method->exists ? route('admin.methods.update', $method) : route('admin.methods.store') }}">
  @csrf
  @if ($method->exists) @method('PUT') @endif
  <div class="form-grid">
    <div><label>Currency</label>
      <select name="currency" class="input" required>
        @foreach (\App\Models\Currency::orderBy('sort')->get() as $c)<option value="{{ $c->code }}" @selected($cur === $c->code)>{{ $c->code }} — {{ $c->name }}{{ $c->is_active ? '' : ' (inactive)' }}</option>@endforeach
      </select>
    </div>
    <div><label>Type</label>
      <select name="type" class="input" data-type-toggle>
        @foreach (\App\Models\DepositMethod::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('type', $method->type) === $k)>{{ $l }}</option>@endforeach
      </select>
    </div>
    <div class="full"><label>Display title</label><input class="input" name="title" value="{{ old('title', $method->title) }}" placeholder="e.g. UPI (GPay / PhonePe) · Bank transfer (CEFTS) · eZ Cash" required></div>

    <div data-type="upi"><label>UPI ID</label><input class="input" name="upi_id" value="{{ old('upi_id', $method->upi_id) }}" placeholder="business@okbank"></div>
    <div data-type="upi wallet"><label>QR code image (optional)</label><input class="input" type="file" name="qr" accept="image/*">
      <span class="hint">UPI: if empty, a QR with the exact amount is generated automatically.</span>
      @if ($method->qr_image)<img src="{{ route('files.qr', $method) }}" alt="QR" style="width:110px;margin-top:8px;border-radius:8px;background:#fff;padding:6px">@endif
    </div>

    <div data-type="bank wallet"><label>Account / wallet holder</label><input class="input" name="account_holder" value="{{ old('account_holder', $method->account_holder) }}"></div>
    <div data-type="bank wallet"><label><span data-type="bank">Bank name</span><span data-type="wallet">Wallet provider</span></label><input class="input" name="bank_name" value="{{ old('bank_name', $method->bank_name) }}"></div>
    <div data-type="bank wallet"><label><span data-type="bank">Account number / IBAN</span><span data-type="wallet">Wallet number</span></label><input class="input" name="account_number" value="{{ old('account_number', $method->account_number) }}"></div>
    <div data-type="bank"><label>IFSC / SWIFT / branch code</label><input class="input" name="ifsc" value="{{ old('ifsc', $method->ifsc) }}" style="text-transform:uppercase"></div>

    <div><label>Min amount (0 = none)</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ old('min_amount', $method->min_amount ?? 0) }}"></div>
    <div><label>Max amount (0 = no limit)</label><input class="input" type="number" step="0.01" name="max_amount" value="{{ old('max_amount', $method->max_amount ?? 0) }}"></div>
    <div><label>Sort order</label><input class="input" type="number" name="sort" value="{{ old('sort', $method->sort ?? 0) }}"></div>
    <div style="align-self:end"><label class="switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $method->is_active))> Active (visible to clients)</label></div>
    <div class="full">@include('partials.admin-countries', ['selected' => old('countries', $method->countries), 'currency' => $cur])</div>
    <div class="full"><label>Instructions shown to client</label><textarea name="instructions" class="input" rows="4">{{ old('instructions', $method->instructions) }}</textarea></div>
  </div>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
