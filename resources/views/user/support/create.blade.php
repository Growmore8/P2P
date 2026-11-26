@extends('layouts.app')
@section('title', 'Raise a ticket')
@section('back', route('support.index'))
@section('heading', 'Raise a ticket')

@section('content')
@php
  $selectedCat = old('category', $category);
  $selectedOrder = old('order_id', optional($orders->firstWhere('order_no', $orderNo))->id);
@endphp
<form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="form">
  @csrf
  <div>
    <label>What is your issue about?</label>
    <div class="cat-grid">
      @foreach (\App\Models\Ticket::CATEGORIES as $k => [$label, $icon, $hint])
        <label class="cat-card">
          <input type="radio" name="category" value="{{ $k }}" @checked($selectedCat === $k) required>
          <span class="cat-ico">{{ $icon }}</span>
          <span class="cat-text"><b>{{ $label }}</b><small>{{ $hint }}</small></span>
        </label>
      @endforeach
    </div>
  </div>

  <div>
    <label>Related order (optional)</label>
    <select name="order_id" class="input">
      <option value="">— Not about a specific order —</option>
      @foreach ($orders as $o)
        <option value="{{ $o->id }}" @selected($selectedOrder == $o->id)>{{ $o->order_no }} · {{ strtoupper($o->side) }} {{ number_format($o->usdt_amount, 2) }} USDT · {{ $o->statusLabel() }}</option>
      @endforeach
    </select>
  </div>

  <div><label>Subject</label><input class="input" name="subject" value="{{ old('subject') }}" maxlength="150" placeholder="e.g. Paid ₹10,000 but USDT not received" required></div>

  <div><label>Describe the issue</label>
    <textarea class="input" name="message" rows="6" maxlength="5000" placeholder="What happened? Include the UTR / transaction hash, amount and time if possible." required>{{ old('message') }}</textarea>
    <span class="hint">Never share your password, OTP, UPI PIN or wallet seed phrase.</span>
  </div>

  <div>
    <label>Screenshot (optional)</label>
    <label class="o-upload">
      <input type="file" name="attachment" accept="image/*,.pdf" data-upload>
      <span class="o-upload-ico">⬆</span>
      <span data-upload-label>Tap to attach <small>JPG, PNG or PDF · max 5 MB</small></span>
    </label>
  </div>

  <button class="btn btn-primary btn-block btn-lg">Submit ticket</button>
</form>
@endsection

@push('scripts')
<script>
  (function () {
    var up = document.querySelector('[data-upload]');
    if (up) up.addEventListener('change', function () {
      var l = document.querySelector('[data-upload-label]');
      if (up.files[0]) { l.innerHTML = '✓ ' + up.files[0].name.replace(/[<>&"]/g, '') + ' <small>Tap to change</small>'; up.closest('.o-upload').classList.add('has-file'); }
    });
  })();
</script>
@endpush
