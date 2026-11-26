@extends('layouts.admin')
@section('title', 'KYC — ' . $kyc->full_name)
@section('heading', 'KYC — ' . $kyc->full_name)

@section('content')
<a href="{{ route('admin.kyc.index') }}" class="muted" style="font-size:14px">← Back to KYC queue</a>

@if ($duplicates->isNotEmpty())
  <div class="alert alert-error" style="margin-top:14px">⚠ This {{ $kyc->isIndian() ? 'PAN' : 'ID number' }} is also used by:
    @foreach ($duplicates as $dup)<a class="green" href="{{ route('admin.users.show', $dup->user) }}">{{ $dup->user->email }}</a> ({{ $dup->status }}) @endforeach
  </div>
@endif

<div class="split" style="margin-top:14px">
  <div>
  @if (! empty($kyc->checks))
  <div class="box">
    <div class="box-head"><h3>Automatic checks</h3><span class="muted" style="font-size:13px">run at submission</span></div>
    @foreach ($kyc->checks as $c)
      <div class="o-row"><span>{{ $c['label'] }}</span><b style="text-align:left;font-weight:500">{!! $c['ok'] ? '<span class="badge ok">✓</span>' : '<span class="badge warn">!</span>' !!} {{ $c['note'] }}</b><span class="muted" style="font-size:12px;white-space:nowrap">shown as {{ $c['agent'] }}</span></div>
    @endforeach
  </div>
  @endif
  <div class="box">
    <div class="box-head"><h3>Video verification</h3>
      @if ($kyc->video)<span class="badge ok">🎥 Recorded</span>@else<span class="badge warn">No video</span>@endif</div>
    @if ($kyc->video)
      <video class="kyc-video" controls preload="metadata" src="{{ route('files.kyc', [$kyc, 'video']) }}"></video>
      <p class="hint" style="margin-top:8px">Check: face matches the ID photo, ID is held up, client says their name, live movement (not a photo).</p>
    @else
      <p class="muted">This client submitted without a video{{ $settings['kyc_video_required'] ? '' : ' (video is optional in Settings)' }}.</p>
    @endif
  </div>
  <div class="box">
    <h3 style="margin-bottom:14px">Documents</h3>
    <div class="doc-grid">
      @foreach ($kyc->docLabels() as $field => $label)
        @continue(! $kyc->$field)
        @php $url = route('files.kyc', [$kyc, $field]); $isPdf = str_ends_with(strtolower($kyc->$field), '.pdf'); @endphp
        <div class="doc">
          @if ($isPdf)
            <a href="{{ $url }}" target="_blank" class="btn btn-ghost btn-block" style="height:240px">📄 Open PDF</a>
          @else
            <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" alt="{{ $label }}"></a>
          @endif
          <p><span>{{ $label }}</span><a href="{{ $url }}" target="_blank" class="green">Open ↗</a></p>
        </div>
      @endforeach
    </div>
  </div>

  </div>
  <div>
    <div class="box">
      <div class="box-head"><h3>Details</h3><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad'][$kyc->status] ?? 'warn' }}">{{ ucfirst($kyc->status) }}</span></div>
      <dl class="kv" style="grid-template-columns:120px 1fr">
        <dt>Full name</dt><dd>{{ $kyc->full_name }}</dd>
        <dt>Account name</dt><dd>{{ $kyc->user->name }}</dd>
        <dt>DOB</dt><dd>{{ $kyc->dob->format('d M Y') }} ({{ $kyc->dob->age }} yrs)</dd>
        <dt>Country</dt><dd>{{ $kyc->countryName() }}</dd>
        @if ($kyc->isIndian())
          <dt>PAN</dt><dd class="mono">{{ $kyc->pan_number }}</dd>
          <dt>Aadhaar</dt><dd class="mono">XXXX XXXX {{ $kyc->aadhaar_last4 }}</dd>
        @else
          <dt>ID type</dt><dd>{{ $kyc->idTypeLabel() }}</dd>
          <dt>ID number</dt><dd class="mono">{{ $kyc->id_number }}</dd>
        @endif
        <dt>Address</dt><dd>{{ $kyc->address }}, {{ $kyc->city }}{{ $kyc->state ? ', ' . $kyc->state : '' }} – {{ $kyc->pincode }}</dd>
        <dt>Phone</dt><dd>{{ $kyc->user->phone }}</dd>
        <dt>Email</dt><dd>{{ $kyc->user->email }}</dd>
        <dt>Submitted</dt><dd>{{ $kyc->created_at->format('d M Y, h:i A') }}</dd>
        @if ($kyc->reviewed_at)
          <dt>Reviewed</dt><dd>{{ $kyc->reviewed_at->format('d M Y, h:i A') }} by {{ $kyc->reviewer?->name }}</dd>
        @endif
        @if ($kyc->reject_reason)<dt>Reason</dt><dd class="red">{{ $kyc->reject_reason }}</dd>@endif
      </dl>
    </div>

    @if ($kyc->status === 'pending')
      <div class="box">
        <h3 style="margin-bottom:10px">Checklist</h3>
        <ul class="timeline" style="padding-left:0">
          @if ($kyc->isIndian())
            <li style="list-style:none;padding-left:0">☐ Name matches on PAN, Aadhaar and form</li>
            <li style="list-style:none;padding-left:0">☐ PAN number matches the image</li>
            <li style="list-style:none;padding-left:0">☐ Aadhaar last 4 digits match ({{ $kyc->aadhaar_last4 }})</li>
          @else
            <li style="list-style:none;padding-left:0">☐ Name matches on ID, proof of address and form</li>
            <li style="list-style:none;padding-left:0">☐ ID number matches the image and ID is not expired</li>
            <li style="list-style:none;padding-left:0">☐ Address proof is recent (under 3 months) and matches</li>
          @endif
          <li style="list-style:none;padding-left:0">☐ Selfie face matches the ID photo</li>
          <li style="list-style:none;padding-left:0">☐ Age 18+ and documents not edited</li>
        </ul>
        <form method="POST" action="{{ route('admin.kyc.approve', $kyc) }}" data-confirm="Approve this KYC?" style="margin:10px 0 18px">
          @csrf <button class="btn btn-primary btn-block">✓ Approve KYC</button>
        </form>
        <form method="POST" action="{{ route('admin.kyc.reject', $kyc) }}" class="form" data-confirm="Reject this KYC?">
          @csrf
          <select class="input" onchange="this.nextElementSibling.value=this.value">
            <option value="">Choose a reason…</option>
            <option>Documents are blurry or unreadable</option>
            <option>Name does not match across documents</option>
            <option>PAN number does not match the image</option>
            <option>Selfie does not match ID photo</option>
            <option>Selfie must show you holding your PAN card</option>
            <option>Documents appear edited or invalid</option>
          </select>
          <input class="input" name="reject_reason" placeholder="Reason shown to the customer" required>
          <button class="btn btn-danger btn-block">✕ Reject KYC</button>
        </form>
      </div>
    @endif
  </div>
</div>
@endsection
