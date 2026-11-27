@extends('layouts.app')
@section('back', route('account.limits'))
@section('title', 'KYC Verification')
@section('heading', 'Verify your identity')

@section('content')
@php $status = $user->kyc_status; @endphp

@if ($status === 'approved')
  <div class="box" style="text-align:center;padding:40px">
    <div class="icon" style="margin:0 auto;width:64px;height:64px;font-size:28px">✓</div>
    <h2 style="margin:16px 0 8px">KYC verified</h2>
    <p class="muted">Verified on {{ $kyc?->reviewed_at?->format('d M Y') }}. You can now buy and sell USDT.</p>
    <div class="actions" style="justify-content:center;margin-top:20px">
      <a href="{{ route('trade', ['side' => 'buy']) }}" class="btn btn-primary">Buy USDT</a>
      <a href="{{ route('trade', ['side' => 'sell']) }}" class="btn btn-ghost">Sell USDT</a>
    </div>
  </div>
  @if ($kyc)
  <div class="box"><dl class="kv">
    <dt>Name</dt><dd>{{ $kyc->full_name }}</dd>
    <dt>Country</dt><dd>{{ $kyc->countryName() }}</dd>
    <dt>{{ $kyc->idTypeLabel() }}</dt><dd>{{ $kyc->maskedId() }}</dd>
    @if ($kyc->aadhaar_last4)<dt>Aadhaar</dt><dd>XXXX XXXX {{ $kyc->aadhaar_last4 }}</dd>@endif
  </dl></div>
  @endif
@elseif ($status === 'pending')
  <div class="box" style="text-align:center;padding:40px">
    <div class="icon gold" style="margin:0 auto;width:64px;height:64px;font-size:28px">⏳</div>
    <h2 style="margin:16px 0 8px">Under review</h2>
    <p class="muted">Submitted {{ $kyc?->created_at->diffForHumans() }}. Our team usually reviews KYC within working hours ({{ $settings['desk_hours'] }}).</p>
  </div>
  @if (! empty($kyc?->checks))
    <p class="section-label">Automatic checks</p>
    <ul class="kv-list kv-static">
      @foreach ($kyc->checks as $c)
        <li class="kv-item {{ $c['ok'] ? 'ok' : 'warn' }}">
          <span class="kv-avatar">{{ collect(explode(' ', $c['agent']))->map(fn ($x) => mb_substr($x, 0, 1))->take(2)->join('') }}</span>
          <div class="kv-body"><b>{{ $c['label'] }}</b><small class="kv-done">{{ $c['note'] }} · {{ $c['agent'] }}</small></div>
          <span class="kv-state"><span class="kv-tick">{{ $c['ok'] ? '✓' : '!' }}</span></span>
        </li>
      @endforeach
    </ul>
    <p class="muted center" style="font-size:12.5px;margin-top:10px">Final review in progress</p>
    <a href="{{ route('account.limits') }}" class="btn btn-ghost btn-block" style="margin-top:12px">View verification levels</a>
  @endif
@else
  @if ($status === 'rejected')
    <div class="alert alert-error"><b>Your previous KYC was rejected:</b> {{ $kyc?->reject_reason }}<br>Please correct the issue and resubmit.</div>
  @endif
  @php $india = $user->isIndian(); @endphp
  <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data" class="form">
    @csrf
    <div class="box">
      <div class="box-head"><h3>1. Personal details</h3><span class="muted" style="font-size:13px">Country: <b>{{ $user->countryName() }}</b> · must match your ID</span></div>
      <div class="form-grid">
        <div><label>Full name (as per {{ $india ? 'PAN' : 'ID document' }})</label><input class="input" name="full_name" value="{{ old('full_name', $user->name) }}" required></div>
        <div><label>Date of birth</label><input class="input" type="date" name="dob" value="{{ old('dob') }}" max="{{ now()->subYears(18)->format('Y-m-d') }}" required></div>
        @if ($india)
          <div><label>PAN number</label><input class="input" name="pan_number" value="{{ old('pan_number') }}" maxlength="10" style="text-transform:uppercase" placeholder="ABCDE1234F" required></div>
          <div><label>Aadhaar number</label><input class="input" name="aadhaar_number" inputmode="numeric" maxlength="12" placeholder="12 digits" required><span class="hint">Only the last 4 digits are stored.</span></div>
        @else
          <div><label>ID document type</label>
            <select class="input" name="id_type" required>
              @foreach (\App\Models\KycSubmission::ID_TYPES as $k => $l)<option value="{{ $k }}" @selected(old('id_type') === $k)>{{ $l }}</option>@endforeach
            </select>
          </div>
          <div><label>Document number</label><input class="input" name="id_number" value="{{ old('id_number') }}" maxlength="30" style="text-transform:uppercase" required></div>
        @endif
        <div class="full"><label>Address</label><input class="input" name="address" value="{{ old('address') }}" required></div>
      </div>
      <div class="form-grid-3" style="margin-top:16px">
        <div><label>City</label><input class="input" name="city" value="{{ old('city') }}" required></div>
        <div><label>{{ $india ? 'State' : 'State / Province (optional)' }}</label><input class="input" name="state" value="{{ old('state', $india ? 'Tamil Nadu' : '') }}" @required($india)></div>
        <div><label>{{ $india ? 'PIN code' : 'Postal code' }}</label><input class="input" name="pincode" value="{{ old('pincode') }}" maxlength="{{ $india ? 6 : 12 }}" required></div>
      </div>
    </div>
    <div class="box">
      <div class="box-head"><h3>2. Documents</h3><span class="muted" style="font-size:13px">JPG, PNG, WEBP or PDF · max 5 MB each</span></div>
      <div class="form-grid">
        @if ($india)
          <div><label>PAN card</label>
            <label class="o-upload"><input type="file" name="pan_image" accept="image/*,.pdf" required data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo or PDF · max 5 MB</small></span></label></div>
          <div><label>Selfie holding your PAN card</label>
            <label class="o-upload"><input type="file" name="selfie" accept="image/*" capture="user" required data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo · max 5 MB</small></span></label></div>
          <div><label>Aadhaar — front</label>
            <label class="o-upload"><input type="file" name="aadhaar_front" accept="image/*,.pdf" required data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo or PDF · max 5 MB</small></span></label></div>
          <div><label>Aadhaar — back</label>
            <label class="o-upload"><input type="file" name="aadhaar_back" accept="image/*,.pdf" required data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo or PDF · max 5 MB</small></span></label></div>
        @else
          <div><label>ID document — front / photo page</label>
            <label class="o-upload"><input type="file" name="pan_image" accept="image/*,.pdf" required data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo or PDF · max 5 MB</small></span></label></div>
          <div><label>ID document — back (if any)</label>
            <label class="o-upload"><input type="file" name="aadhaar_front" accept="image/*,.pdf" data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo or PDF · max 5 MB</small></span></label><span class="hint">Not needed for passports.</span></div>
          <div><label>Selfie holding your ID</label>
            <label class="o-upload"><input type="file" name="selfie" accept="image/*" capture="user" required data-upload><span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>Photo · max 5 MB</small></span></label></div>
        @endif
      </div>
      <div class="notice" style="margin-top:18px">
        @if ($india) Tip: You may mask the first 8 digits of your Aadhaar number on the uploaded image. @endif
        Make sure all details are clear and readable.
      </div>
    </div>
    <div class="box">
      <label class="check"><input type="checkbox" name="consent" value="1" required> I confirm the information and documents are genuine and belong to me, and I consent to SRM Fintech Solutions using them for verification and compliance as per the <a href="{{ route('legal') }}#privacy" target="_blank" class="green">Privacy Policy</a>.</label>
      <button class="btn btn-primary btn-block btn-lg" style="margin-top:18px">Submit verification</button>
    </div>
  </form>
@endif
@push('scripts')
<script>
  document.querySelectorAll('[data-upload]').forEach(function (up) {
    up.addEventListener('change', function () {
      var box = up.closest('.o-upload'), l = box.querySelector('[data-upload-label]');
      if (up.files[0]) { l.innerHTML = '✓ ' + up.files[0].name.replace(/[<>&"]/g, '') + ' <small>Tap to change</small>'; box.classList.add('has-file'); }
    });
  });
</script>
@endpush
@endsection

