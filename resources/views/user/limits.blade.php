@extends('layouts.app')
@section('title', 'Verification')
@section('back', route('account'))
@section('heading', 'Verification')

@section('content')
@php
  $levels = [
    1 => ['title' => 'Identity Verification', 'need' => [$user->isIndian() ? 'PAN card + Aadhaar (front & back)' : 'ID document (front & back)', 'Selfie holding your ID'], 'route' => route('kyc.show')],
    2 => ['title' => 'Video Verification', 'need' => ['Selfie video — read a short statement aloud'], 'route' => route('kyc.video')],
    3 => ['title' => 'EDD', 'sub' => 'Enhanced Due Diligence', 'need' => ['Bank statement (last 6 months)', 'Income proof (ITR / salary slip)', 'Source of funds details'], 'route' => route('kyc.edd')],
  ];
  // the step the Proceed button opens: first one that needs action
  $action = collect([1, 2, 3])->first(fn ($l) => in_array($steps[$l]['state'], ['next', 'rejected'], true));
  $fmt = fn ($v) => $currency ? ($v > 0 ? money($v, $code, 0) : 'No limit') : '—';
@endphp

<ol class="vsteps">
  @foreach ($levels as $lvl => $info)
    @php
      $st = $steps[$lvl]['state'];
      $open = $lvl === $action || ($action === null && $st === 'review');
    @endphp
    <li class="vstep is-{{ $st }} {{ $open ? 'is-open' : '' }}">
      <span class="vdot">@if ($st === 'done')✓@elseif ($st === 'rejected')!@endif</span>
      <div class="vcard">
        <div class="vcard-head" @unless ($open) data-toggle="vbody{{ $lvl }}" role="button" tabindex="0" @endunless>
          <div>
            <b>{{ $info['title'] }}</b>
            @if (! empty($info['sub']))<small class="muted"> · {{ $info['sub'] }}</small>@endif
          </div>
          <span class="vstate">
            @switch($st)
              @case('done')<span class="badge ok">Verified</span>@break
              @case('review')<span class="badge warn">In review</span>@break
              @case('rejected')<span class="badge bad">Action needed</span>@break
              @case('locked')<span class="muted" style="font-size:13px">{{ $currency ? $fmt($currency->limitFor($lvl, 'buy')) . ' limit' : 'Locked' }} ⌄</span>@break
              @default<span class="badge info">Next step</span>
            @endswitch
          </span>
        </div>
        <div class="vbody" id="vbody{{ $lvl }}" @unless ($open) hidden @endunless>
          @if ($currency)
            <p class="vlimit">{{ $fmt($currency->limitFor($lvl, 'buy')) }} <span>Buy limit / month</span></p>
            <p class="vlimit">{{ $fmt($currency->limitFor($lvl, 'sell')) }} <span>Sell limit / month</span></p>
          @endif
          @if ($st === 'rejected' && ! empty($steps[$lvl]['reason']))
            <div class="alert alert-error" style="margin:10px 0 0">Rejected: {{ $steps[$lvl]['reason'] }}</div>
          @endif
          @if ($st === 'review')
            <p class="muted" style="font-size:13.5px;margin-top:8px">Submitted — our team reviews within working hours ({{ $settings['desk_hours'] }}).</p>
          @elseif ($st !== 'done')
            <p class="vreq-title">Required</p>
            @foreach ($info['need'] as $n)<p class="vreq"><span>🪪</span>{{ $n }}</p>@endforeach
          @endif
        </div>
      </div>
    </li>
  @endforeach
</ol>

@if ($limits)
  <p class="section-label" style="margin-top:4px">This month · {{ $code }}</p>
  <div class="box">
    @foreach (['buy' => 'Buy', 'sell' => 'Sell'] as $k => $label)
      @php $l = $limits[$k]; $pct = $l['limit'] > 0 ? min(100, $l['used'] / $l['limit'] * 100) : 0; @endphp
      <div class="srow">
        <div class="sbody">
          <b>{{ $label }}</b>
          <small>{{ money($l['used'], $code, 0) }} used of {{ $level < 1 ? 'verification required' : ($l['limit'] > 0 ? money($l['limit'], $code, 0) : 'no limit') }} · resets on the 1st</small>
          @if ($l['limit'] > 0)<div class="meter"><span style="width:{{ $pct }}%"></span></div>@endif
        </div>
      </div>
    @endforeach
  </div>
@elseif (! $currency)
  <div class="alert alert-info">Trading in {{ $code }} ({{ $user->countryName() }}) isn't available yet. You can still verify your account.</div>
@endif

<div class="stick-bottom">
  <p class="vsecure">🛡 Your data is 100% secure. We require verification to prevent ID theft and fraud.</p>
  @if ($action)
    <a href="{{ $levels[$action]['route'] }}" class="btn btn-primary btn-block btn-lg">{{ $steps[$action]['state'] === 'rejected' ? 'Resubmit' : 'Proceed' }}</a>
  @elseif ($level >= 3)
    <a href="{{ route('trade') }}" class="btn btn-primary btn-block btn-lg">Fully verified — start trading</a>
  @else
    <a href="{{ route('trade') }}" class="btn btn-ghost btn-block btn-lg">Back to trading</a>
  @endif
</div>
@endsection
