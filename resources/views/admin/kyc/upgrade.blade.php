@extends('layouts.admin')
@section('title', $up->levelLabel() . ' — ' . $up->user->name)
@section('heading', $up->levelLabel())

@section('content')
@php $u = $up->user; $k = $u->kyc; $d = $up->details ?? []; @endphp
<a href="{{ route('admin.kyc.index', ['level' => $up->level]) }}" class="muted" style="font-size:14px">← Back to {{ $up->levelLabel() }}</a>

@if ($u->kyc_status !== 'approved')
  <div class="alert alert-error" style="margin-top:14px">⚠ Identity (Level 1) is <b>{{ $u->kyc_status }}</b> — approve it before approving this level.</div>
@endif

<div class="split" style="margin-top:14px">
  <div>
    @if ($up->level === \App\Models\KycUpgrade::VIDEO)
      <div class="box">
        <div class="box-head"><h3>Selfie video</h3><span class="muted" style="font-size:13px">{{ strtoupper($d['lang'] ?? 'en') }} · {{ $d['size_mb'] ?? '?' }} MB</span></div>
        <video class="kyc-video" controls preload="metadata" src="{{ route('files.upgrade', [$up, 'video']) }}"></video>
      </div>
      <div class="box">
        <h3 style="margin-bottom:10px">Statement the client had to read</h3>
        <div class="vk2-script" style="font-size:15px">@foreach (explode("\n", $d['script'] ?? '') as $line)<p>{{ $line }}</p>@endforeach</div>
      </div>
    @else
      <div class="box">
        <h3 style="margin-bottom:12px">Source of funds</h3>
        <dl class="kv">
          <dt>Occupation</dt><dd>{{ $d['occupation'] ?? '—' }}</dd>
          <dt>Employer</dt><dd>{{ $d['employer'] ?? '—' }}</dd>
          <dt>Annual income</dt><dd>{{ $d['annual_income'] ?? '—' }} ({{ $u->currencyCode() }})</dd>
          <dt>Source</dt><dd>{{ $d['source_of_funds'] ?? '—' }}</dd>
          <dt>Details</dt><dd>{{ $d['source_details'] ?? '—' }}</dd>
          <dt>Purpose</dt><dd>{{ $d['purpose'] ?? '—' }}</dd>
        </dl>
      </div>
      <div class="box">
        <h3 style="margin-bottom:12px">Documents</h3>
        @foreach (\App\Models\KycUpgrade::EDD_FILES as $key => $label)
          @if (! empty($up->files[$key]))
            <div class="o-row"><span>{{ $label }}</span><b></b><a class="btn btn-ghost btn-sm" href="{{ route('files.upgrade', [$up, $key]) }}" target="_blank" rel="noopener">Open ↗</a></div>
          @endif
        @endforeach
      </div>
    @endif
  </div>

  <div>
    <div class="box">
      <div class="box-head"><h3>Client</h3><a href="{{ route('admin.users.show', $u) }}" class="btn btn-ghost btn-sm">Profile</a></div>
      <dl class="kv" style="grid-template-columns:110px 1fr">
        <dt>KYC name</dt><dd>{{ $k?->full_name ?? $u->name }}</dd>
        <dt>ID</dt><dd class="mono">{{ $k ? $k->idTypeLabel() . ': ' . ($k->pan_number ?? $k->id_number) : '—' }}</dd>
        <dt>Email</dt><dd>{{ $u->email }}</dd>
        <dt>Phone</dt><dd>{{ $u->phone }}</dd>
        <dt>Country</dt><dd>{{ $u->countryName() }}</dd>
        <dt>Level</dt><dd><span class="badge info">Level {{ $u->effectiveKycLevel() }}</span></dd>
        <dt>Submitted</dt><dd>{{ $up->created_at->format('d M Y, h:i A') }}</dd>
        @if ($up->reviewed_at)<dt>Reviewed</dt><dd>{{ $up->reviewed_at->format('d M Y, h:i A') }} by {{ $up->reviewer?->name }}</dd>@endif
        @if ($up->reject_reason)<dt>Reason</dt><dd class="red">{{ $up->reject_reason }}</dd>@endif
      </dl>
      @if ($k && $k->selfie)
        <p class="muted" style="font-size:12.5px;margin-top:10px">Compare with the identity selfie:</p>
        <a href="{{ route('admin.kyc.show', $k) }}" class="green" style="font-size:13.5px;font-weight:700">Open Level 1 documents →</a>
      @endif
    </div>

    @if ($up->status === 'pending')
      <div class="box">
        <h3 style="margin-bottom:10px">Checklist</h3>
        @if ($up->level === \App\Models\KycUpgrade::VIDEO)
          <p class="vreq">☐ Same person as the identity selfie / ID photo</p>
          <p class="vreq">☐ Reads the full statement with their own name</p>
          <p class="vreq">☐ Live person (moving, natural) — not a photo or replay</p>
          <p class="vreq">☐ No one else prompting or helping</p>
        @else
          <p class="vreq">☐ Bank statement name matches KYC name</p>
          <p class="vreq">☐ Income is consistent with expected trading volume</p>
          <p class="vreq">☐ Source of funds is legitimate and documented</p>
        @endif
        <form method="POST" action="{{ route('admin.kyc.upgrade.approve', $up) }}" data-confirm="Approve {{ $up->levelLabel() }}? The client's limits will increase." style="margin:12px 0 16px">
          @csrf <button class="btn btn-primary btn-block">✓ Approve — raise to Level {{ $up->level }}</button>
        </form>
        <form method="POST" action="{{ route('admin.kyc.upgrade.reject', $up) }}" class="form" data-confirm="Reject this request?">
          @csrf
          <select class="input" onchange="this.nextElementSibling.value=this.value">
            <option value="">Choose a reason…</option>
            @if ($up->level === \App\Models\KycUpgrade::VIDEO)
              <option>Face not clearly visible — please record in good light</option>
              <option>Statement not read completely</option>
              <option>Person in video does not match the ID</option>
              <option>Someone else is helping in the video</option>
            @else
              <option>Bank statement unclear or incomplete</option>
              <option>Name on documents does not match KYC</option>
              <option>Income proof missing or outdated</option>
              <option>Source of funds not sufficiently documented</option>
            @endif
          </select>
          <input class="input" name="reject_reason" placeholder="Reason shown to the client" required>
          <button class="btn btn-danger btn-block">✕ Reject</button>
        </form>
      </div>
    @endif

    @if ($history->isNotEmpty())
      <div class="box"><h3 style="margin-bottom:10px">Previous attempts</h3>
        @foreach ($history as $h)
          <a href="{{ route('admin.kyc.upgrade.show', $h) }}" class="order-item" style="margin-bottom:8px"><span>{{ $h->created_at->format('d M Y') }}<span class="sub muted" style="display:block;font-size:12px">{{ $h->reject_reason }}</span></span><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad'][$h->status] ?? 'warn' }}">{{ $h->status }}</span></a>
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection
