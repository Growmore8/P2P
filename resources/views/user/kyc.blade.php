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
          <div><label>PAN card</label><input class="input" type="file" name="pan_image" accept="image/*,.pdf" required></div>
          <div><label>Selfie holding your PAN card</label><input class="input" type="file" name="selfie" accept="image/*" capture="user" required></div>
          <div><label>Aadhaar — front</label><input class="input" type="file" name="aadhaar_front" accept="image/*,.pdf" required></div>
          <div><label>Aadhaar — back</label><input class="input" type="file" name="aadhaar_back" accept="image/*,.pdf" required></div>
        @else
          <div><label>ID document — front / photo page</label><input class="input" type="file" name="pan_image" accept="image/*,.pdf" required></div>
          <div><label>ID document — back (if any)</label><input class="input" type="file" name="aadhaar_front" accept="image/*,.pdf"><span class="hint">Not needed for passports.</span></div>
          <div><label>Proof of address</label><input class="input" type="file" name="aadhaar_back" accept="image/*,.pdf" required><span class="hint">Utility bill or bank statement, less than 3 months old.</span></div>
          <div><label>Selfie holding your ID</label><input class="input" type="file" name="selfie" accept="image/*" capture="user" required></div>
        @endif
      </div>
      <div class="notice" style="margin-top:18px">
        @if ($india) Tip: You may mask the first 8 digits of your Aadhaar number on the uploaded image. @endif
        Make sure all details are clear and readable.
      </div>
    </div>
    @php $videoRequired = (bool) $settings['kyc_video_required']; @endphp
    <div class="box" id="videoKyc">
      <div class="box-head"><h3>3. Video verification</h3><span class="muted" style="font-size:13px">{{ $videoRequired ? 'Required' : 'Optional' }} · about 10 seconds</span></div>
      <ol style="margin:0 0 14px 18px;font-size:14px;color:var(--muted)">
        <li>Hold your {{ $india ? 'PAN card' : 'ID document' }} next to your face.</li>
        <li>Say: <b style="color:var(--text)">"I am {{ $user->name }}, verifying my SRM Fintech account today."</b></li>
        <li>Turn your head slowly left and right.</li>
      </ol>
      <div class="vk-stage">
        <video id="vkPreview" playsinline muted></video>
        <div class="vk-badge" id="vkBadge" hidden>● REC <span id="vkTime">10</span>s</div>
        <div class="vk-empty" id="vkEmpty">📹 Camera preview</div>
      </div>
      <div class="actions" style="margin-top:12px">
        <button type="button" class="btn btn-primary btn-sm" id="vkStart">Start camera &amp; record</button>
        <button type="button" class="btn btn-ghost btn-sm" id="vkStop" hidden>Stop</button>
        <button type="button" class="btn btn-ghost btn-sm" id="vkRetake" hidden>Retake</button>
      </div>
      <p class="hint" id="vkStatus" style="margin-top:8px"></p>
      <div style="margin-top:12px"><label class="muted" style="font-size:13px">Or upload a short video from your phone</label>
        <input class="input" type="file" name="video" id="vkFile" accept="video/*" capture="user" @required($videoRequired)></div>
      <label class="check" style="margin-top:12px"><input type="checkbox" name="video_consent" value="1" @required($videoRequired)> I agree to SRM Fintech Solutions recording and storing this video to verify my identity.</label>
    </div>

    <div class="box">
      <label class="check"><input type="checkbox" name="consent" value="1" required> I confirm the information and documents are genuine and belong to me, and I consent to SRM Fintech Solutions using them for verification and compliance as per the <a href="{{ route('legal') }}#privacy" target="_blank" class="green">Privacy Policy</a>.</label>
      <button class="btn btn-primary btn-block btn-lg" style="margin-top:18px">Submit verification</button>
    </div>
  </form>
@endif
@endsection

@push('scripts')
<script>
/* In-browser video KYC: records ~10s from the front camera and puts the clip into the file input. */
(function () {
  var start = document.getElementById('vkStart'); if (!start) return;
  var stopB = document.getElementById('vkStop'), retake = document.getElementById('vkRetake');
  var prev = document.getElementById('vkPreview'), empty = document.getElementById('vkEmpty');
  var badge = document.getElementById('vkBadge'), time = document.getElementById('vkTime');
  var status = document.getElementById('vkStatus'), file = document.getElementById('vkFile');
  var stream, rec, chunks = [], timer, left;

  if (!navigator.mediaDevices || !window.MediaRecorder) {
    start.hidden = true;
    status.textContent = 'Your browser cannot record here — please upload a video instead.';
    return;
  }
  function stopTracks() { if (stream) stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
  function finish() {
    clearInterval(timer); badge.hidden = true; stopB.hidden = true;
    if (rec && rec.state !== 'inactive') rec.stop();
  }
  start.addEventListener('click', async function () {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 }, audio: true });
    } catch (e) {
      status.textContent = 'Camera/microphone permission was blocked. Allow access in your browser, or upload a video below.';
      return;
    }
    prev.srcObject = stream; prev.muted = true; prev.controls = false; prev.play(); empty.hidden = true;
    var type = ['video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'].find(function (t) { return MediaRecorder.isTypeSupported(t); }) || '';
    rec = new MediaRecorder(stream, type ? { mimeType: type, videoBitsPerSecond: 900000 } : {});
    chunks = [];
    rec.ondataavailable = function (e) { if (e.data.size) chunks.push(e.data); };
    rec.onstop = function () {
      stopTracks();
      var mime = (rec.mimeType || 'video/webm').split(';')[0];
      var blob = new Blob(chunks, { type: mime });
      var f = new File([blob], 'kyc-video.' + (mime.indexOf('mp4') > -1 ? 'mp4' : 'webm'), { type: mime });
      var dt = new DataTransfer(); dt.items.add(f); file.files = dt.files;
      prev.srcObject = null; prev.src = URL.createObjectURL(blob); prev.muted = false; prev.controls = true;
      status.textContent = '✓ Video recorded (' + (blob.size / 1048576).toFixed(1) + ' MB). Play it back to check, then submit.';
      retake.hidden = false; start.hidden = true;
    };
    rec.start(250);
    left = 10; time.textContent = left; badge.hidden = false; stopB.hidden = false; start.hidden = true; retake.hidden = true;
    status.textContent = 'Recording… follow the steps above.';
    timer = setInterval(function () { left--; time.textContent = left; if (left <= 0) finish(); }, 1000);
  });
  stopB.addEventListener('click', finish);
  retake.addEventListener('click', function () { file.value = ''; prev.removeAttribute('src'); prev.load(); start.hidden = false; start.click(); });
  file.addEventListener('change', function () {
    if (file.files[0] && !stream) { prev.src = URL.createObjectURL(file.files[0]); prev.controls = true; empty.hidden = true; status.textContent = '✓ Video selected.'; }
  });
})();
</script>
@endpush
