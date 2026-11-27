@extends('layouts.admin')
@section('title', $method->exists ? 'Edit way clients pay you' : 'Add a way clients pay you')
@section('heading', $method->exists ? 'Edit way clients pay you' : 'Add a way clients pay you')

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
      <span class="hint">Need another currency? Add it first in <a class="green" href="{{ route('admin.currencies.index') }}#add">Rates &amp; currencies</a> (it can stay inactive until you're ready).</span>
    </div>
    <div class="full"><label>How will clients pay?</label>
      @php
        $typeCards = ['upi' => ['📱', 'UPI', 'GPay, PhonePe, Paytm — QR made automatically'], 'bank' => ['🏦', 'Bank transfer', 'Account number + IFSC / SWIFT'], 'wallet' => ['👛', 'Mobile wallet', 'eZ Cash, M-Pesa, bKash…'], 'link' => ['🔗', 'Payment link', 'Razorpay.me or similar link'], 'other' => ['✏️', 'Other', 'Write your own instructions']];
        $curType = old('type', $method->type ?: 'upi');
      @endphp
      <select name="type" class="input" data-type-toggle data-type-select style="display:none">
        @foreach (\App\Models\DepositMethod::TYPES as $k => $l)<option value="{{ $k }}" @selected($curType === $k)>{{ $l }}</option>@endforeach
      </select>
      <div class="type-cards">
        @foreach ($typeCards as $k => [$ico, $l, $d])
          <button type="button" class="type-card {{ $curType === $k ? 'on' : '' }}" data-pick-type="{{ $k }}"><span class="tc-ico">{{ $ico }}</span><b>{{ $l }}</b><small>{{ $d }}</small></button>
        @endforeach
      </div>
    </div>
    <div class="full"><label>Display title</label><input class="input" name="title" value="{{ old('title', $method->title) }}" placeholder="e.g. UPI (GPay / PhonePe) · Bank transfer (CEFTS) · eZ Cash" required></div>

    <div data-type="link" class="full"><label>Payment link (https://…)</label><input class="input" name="pay_url" value="{{ old('pay_url', $method->pay_url) }}" placeholder="https://razorpay.me/@yourbusiness">
      <span class="hint">Clients open this link, pay the exact order amount, then paste the payment ID (e.g. pay_…) as their reference.</span></div>
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
<script>
document.querySelectorAll('[data-pick-type]').forEach(function (b) {
  b.addEventListener('click', function () {
    var sel = document.querySelector('[data-type-select]');
    sel.value = b.dataset.pickType;
    sel.dispatchEvent(new Event('change'));
    document.querySelectorAll('[data-pick-type]').forEach(function (x) { x.classList.toggle('on', x === b); });
  });
});
</script>
@endsection
