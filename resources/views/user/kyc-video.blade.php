@extends('layouts.app')
@section('title', 'Video Verification')
@section('back', route('account.limits'))
@section('heading', 'Video Verification')

@section('content')
@if ($rejected)
  <div class="alert alert-error">Your previous video was rejected: {{ $rejected->reject_reason }}. Please record again.</div>
@endif

<div class="vk2-cam" id="vkCam">
  <svg class="vk2-ring" viewBox="0 0 120 120" aria-hidden="true">
    <circle cx="60" cy="60" r="56" class="vk2-ring-bg"/>
    <circle cx="60" cy="60" r="56" class="vk2-ring-fg" id="vkRing"/>
  </svg>
  <video id="vkVideo" playsinline muted></video>
  <div class="vk2-placeholder" id="vkPlaceholder"><span>📷</span>Tap “Start recording” and allow camera access</div>
</div>

<div class="vk2-card">
  <div class="vk2-card-head">
    <span class="vk2-label">READ ALOUD</span>
    <span class="vk2-status" id="vkStatus">Ready</span>
  </div>
  @foreach ($scripts as $lang => $text)
    <div class="vk2-script" data-lang="{{ $lang }}" @if (! $loop->first) hidden @endif>
      @foreach (explode("\n", $text) as $line)<p>{{ $line }}</p>@endforeach
    </div>
  @endforeach
  <div class="vk2-langs" role="tablist" aria-label="Language">
    @foreach ($langs as $code => $label)
      <button type="button" class="vk2-lang {{ $loop->first ? 'active' : '' }}" data-lang="{{ $code }}" role="tab">{{ $label }}</button>
    @endforeach
  </div>
</div>

<ul class="vk2-tips">
  <li>Good light on your face · look at the camera</li>
  <li>Read the full statement clearly (10–30 seconds)</li>
  <li>Only you in the video — no one helping</li>
</ul>

<label class="check" style="font-size:13px;margin-top:6px"><input type="checkbox" id="vkConsent"> I agree to SRM Fintech Solutions recording and storing this video to verify my identity.</label>
<p class="err" id="vkError" style="margin-top:8px"></p>

<div class="stick-bottom">
  <button type="button" class="btn btn-primary btn-block btn-lg" id="vkMain">Start recording</button>
  <div class="vk2-progress" id="vkProgress" hidden><span id="vkBar"></span></div>
  <button type="button" class="linkish center" id="vkRetake" style="display:none;margin:12px auto 0">Record again</button>
  <label class="linkish center vk2-upload" id="vkUploadWrap" style="display:block;margin-top:12px">
    or upload a video from your phone
    <input type="file" accept="video/*" capture="user" id="vkFile" hidden>
  </label>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var MIN = 8, MAX = 30;                       // seconds
  var ring = document.getElementById('vkRing'), video = document.getElementById('vkVideo');
  var ph = document.getElementById('vkPlaceholder'), cam = document.getElementById('vkCam');
  var status = document.getElementById('vkStatus'), main = document.getElementById('vkMain');
  var retake = document.getElementById('vkRetake'), err = document.getElementById('vkError');
  var consent = document.getElementById('vkConsent'), fileInput = document.getElementById('vkFile');
  var progress = document.getElementById('vkProgress'), bar = document.getElementById('vkBar');
  var uploadWrap = document.getElementById('vkUploadWrap');
  var C = 2 * Math.PI * 56; ring.style.strokeDasharray = C; ring.style.strokeDashoffset = C;
  var lang = 'en', stream = null, rec = null, chunks = [], blob = null, t0 = 0, timer = null, state = 'idle';

  // language switch
  document.querySelectorAll('.vk2-lang').forEach(function (b) {
    b.addEventListener('click', function () {
      if (state === 'recording') return;
      lang = b.dataset.lang;
      document.querySelectorAll('.vk2-lang').forEach(function (x) { x.classList.toggle('active', x === b); });
      document.querySelectorAll('.vk2-script').forEach(function (s) { s.hidden = s.dataset.lang !== lang; });
    });
  });

  function setStatus(t) { status.textContent = t; }
  function fail(msg) { err.textContent = msg; }
  function stopTracks() { if (stream) stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
  function fmt(s) { return '0:' + String(s).padStart(2, '0'); }

  async function startCamera() {
    if (!navigator.mediaDevices || !window.MediaRecorder) { fail('Recording is not supported in this browser — please upload a video instead.'); return false; }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } }, audio: true });
    } catch (e) { fail('Please allow camera and microphone access, or upload a video instead.'); return false; }
    video.srcObject = stream; video.muted = true; video.controls = false; video.play(); ph.hidden = true;
    return true;
  }

  function startRecording() {
    var type = ['video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'].find(function (t) { return MediaRecorder.isTypeSupported(t); }) || '';
    rec = new MediaRecorder(stream, type ? { mimeType: type, videoBitsPerSecond: 1000000 } : {});
    chunks = [];
    rec.ondataavailable = function (e) { if (e.data.size) chunks.push(e.data); };
    rec.onstop = function () {
      stopTracks();
      var mime = (rec.mimeType || 'video/webm').split(';')[0];
      blob = new Blob(chunks, { type: mime });
      video.srcObject = null; video.src = URL.createObjectURL(blob); video.muted = false; video.controls = true;
      cam.classList.remove('is-recording'); cam.classList.add('is-done');
      state = 'recorded'; setStatus('Recorded · ' + (blob.size / 1048576).toFixed(1) + ' MB');
      main.textContent = 'Submit video'; main.disabled = false; retake.style.display = 'block';
    };
    rec.start(250); t0 = Date.now(); state = 'recording';
    cam.classList.add('is-recording'); uploadWrap.style.display = 'none';
    main.textContent = 'Stop recording'; main.disabled = true;
    timer = setInterval(function () {
      var s = Math.floor((Date.now() - t0) / 1000);
      ring.style.strokeDashoffset = C * (1 - Math.min(s / MAX, 1));
      setStatus('Recording ' + fmt(s));
      if (s >= MIN) main.disabled = false;
      if (s >= MAX) stopRecording();
    }, 250);
  }
  function stopRecording() { clearInterval(timer); if (rec && rec.state !== 'inactive') rec.stop(); }

  function upload(file) {
    if (!consent.checked) { fail('Please tick the consent box first.'); return; }
    err.textContent = ''; state = 'uploading'; setStatus('Processing…');
    main.textContent = 'Processing…'; main.disabled = true; retake.style.display = 'none'; progress.hidden = false;
    var fd = new FormData();
    fd.append('video', file, file.name || ('kyc-video.' + (file.type.indexOf('mp4') > -1 ? 'mp4' : 'webm')));
    fd.append('lang', lang); fd.append('consent', '1');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', @json(route('kyc.video.store')));
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.upload.onprogress = function (e) { if (e.lengthComputable) bar.style.width = Math.round(e.loaded / e.total * 100) + '%'; };
    xhr.onload = function () {
      var res = {}; try { res = JSON.parse(xhr.responseText); } catch (e) {}
      if (xhr.status === 200 && res.redirect) { setStatus('Submitted ✓'); bar.style.width = '100%'; location.href = res.redirect; return; }
      var msg = res.errors ? Object.values(res.errors)[0][0] : (res.message || 'Upload failed. Please try again.');
      fail(msg); state = 'recorded'; setStatus('Try again'); main.textContent = 'Submit video'; main.disabled = false;
      retake.style.display = 'block'; progress.hidden = true;
    };
    xhr.onerror = function () { fail('Network error — please check your connection and try again.'); state = 'recorded'; main.textContent = 'Submit video'; main.disabled = false; progress.hidden = true; };
    xhr.send(fd);
  }

  main.addEventListener('click', async function () {
    err.textContent = '';
    if (state === 'idle') {
      if (!consent.checked) { fail('Please tick the consent box first.'); return; }
      if (await startCamera()) { setStatus('Get ready…'); setTimeout(startRecording, 800); }
    } else if (state === 'recording') {
      stopRecording();
    } else if (state === 'recorded' && blob) {
      upload(blob);
    }
  });
  retake.addEventListener('click', async function () {
    blob = null; video.removeAttribute('src'); video.load(); cam.classList.remove('is-done');
    ring.style.strokeDashoffset = C; retake.style.display = 'none'; state = 'idle';
    if (await startCamera()) { setStatus('Get ready…'); setTimeout(startRecording, 800); }
  });
  fileInput.addEventListener('change', function () {
    var f = fileInput.files[0]; if (!f) return;
    blob = f; video.srcObject = null; video.src = URL.createObjectURL(f); video.controls = true; ph.hidden = true;
    cam.classList.add('is-done'); state = 'recorded'; setStatus('Video selected');
    main.textContent = 'Submit video'; main.disabled = false;
  });
})();
</script>
@endpush
