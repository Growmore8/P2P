@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('heading', 'Dashboard')

@section('content')
@if (! $settings['trading_enabled'])
  <div class="alert alert-error">Trading is currently <b>paused</b>. <a href="{{ route('admin.settings') }}" class="green">Change in settings →</a></div>
@endif

{{-- Period filter (one row, above everything it controls) --}}
<div class="tabs-line" role="tablist" aria-label="Period">
  @foreach (\App\Http\Controllers\Admin\DashboardController::PERIODS as $k => $l)
    <a href="{{ route('admin.dashboard', ['period' => $k]) }}" class="{{ $period === $k ? 'active' : '' }}" role="tab" aria-selected="{{ $period === $k ? 'true' : 'false' }}">{{ $l }}</a>
  @endforeach
</div>

{{-- Action queue first: what needs the admin right now --}}
<div class="stats">
  <a class="stat hl" href="{{ route('admin.orders.index', ['status' => 'payment_submitted']) }}"><small>Payments to verify</small><b class="green">{{ $stats['to_verify'] }}</b><span class="stat-link">Review →</span></a>
  <a class="stat" href="{{ route('admin.orders.index', ['status' => 'processing']) }}"><small>Processing (to complete)</small><b>{{ $stats['processing'] }}</b><span class="stat-link">Open →</span></a>
  <a class="stat" href="{{ route('admin.kyc.index') }}"><small>KYC waiting</small><b style="color:var(--gold)">{{ $stats['pending_kyc'] }}</b><span class="stat-link">Verify →</span></a>
  <a class="stat" href="{{ route('admin.orders.index', ['status' => 'pending_payment']) }}"><small>Awaiting client payment</small><b>{{ $stats['awaiting'] }}</b><span class="stat-link">View →</span></a>
</div>

{{-- Business totals for the selected period --}}
<div class="stats">
  <a class="stat" href="{{ route('admin.users.index') }}"><small>Total clients</small><b>{{ number_format($stats['clients']) }}</b><span class="muted" style="font-size:12.5px">+{{ $stats['new_clients'] }} new · {{ $stats['verified'] }} verified</span></a>
  <div class="stat"><small>USDT bought by clients</small><b>{{ number_format($stats['buy_usdt'], 2) }}</b><span class="muted" style="font-size:12.5px">{{ $stats['buy_orders'] }} completed orders</span></div>
  <div class="stat"><small>USDT sold by clients</small><b>{{ number_format($stats['sell_usdt'], 2) }}</b><span class="muted" style="font-size:12.5px">{{ $stats['sell_orders'] }} completed orders</span></div>
  <div class="stat"><small>Total USDT volume</small><b>{{ number_format($stats['buy_usdt'] + $stats['sell_usdt'], 2) }}</b><span class="muted" style="font-size:12.5px">{{ \App\Http\Controllers\Admin\DashboardController::PERIODS[$period] }}</span></div>
</div>

<div class="split">
  {{-- Daily volume chart (USDT, completed, last 14 days) --}}
  <div class="box">
    @php $max = max(1, $chart->max(fn ($d) => max($d['buy'], $d['sell']))); @endphp
    <div class="box-head">
      <h3>Daily volume · last 14 days</h3>
      <div class="legend"><span><i class="sw sw-buy"></i>Buy</span><span><i class="sw sw-sell"></i>Sell</span></div>
    </div>
    <div class="vchart" role="img" aria-label="Daily completed USDT volume, buy and sell, last 14 days">
      <div class="vgrid"><span>{{ number_format($max) }}</span><span>{{ number_format($max / 2) }}</span><span>0</span></div>
      <div class="vbars">
        @foreach ($chart as $d)
          <div class="vday" tabindex="0" data-tip="{{ $d['label'] }} · Buy {{ number_format($d['buy'], 2) }} · Sell {{ number_format($d['sell'], 2) }} USDT">
            <div class="vpair">
              <span class="vbar vb-buy" style="height:{{ $d['buy'] > 0 ? max(2, $d['buy'] / $max * 100) : 0 }}%"></span>
              <span class="vbar vb-sell" style="height:{{ $d['sell'] > 0 ? max(2, $d['sell'] / $max * 100) : 0 }}%"></span>
            </div>
            <small>{{ $d['short'] }}</small>
          </div>
        @endforeach
      </div>
      <div class="vtip" hidden></div>
    </div>
    <details style="margin-top:12px"><summary class="muted" style="cursor:pointer;font-size:13px">View as table</summary>
      <div class="table-wrap" style="margin-top:8px"><table style="min-width:0">
        <thead><tr><th>Date</th><th>Buy USDT</th><th>Sell USDT</th></tr></thead>
        <tbody>@foreach ($chart as $d)<tr><td>{{ $d['label'] }}</td><td>{{ number_format($d['buy'], 2) }}</td><td>{{ number_format($d['sell'], 2) }}</td></tr>@endforeach</tbody>
      </table></div>
    </details>
  </div>

  {{-- Money in / out per currency --}}
  <div class="box">
    <div class="box-head"><h3>Deposits &amp; payouts</h3><span class="muted" style="font-size:13px">{{ \App\Http\Controllers\Admin\DashboardController::PERIODS[$period] }}</span></div>
    @forelse ($byCurrency as $code => $r)
      <div class="cur-block">
        <b class="mono">{{ $code }}</b>
        <dl class="kv" style="grid-template-columns:1fr auto;margin-top:6px">
          <dt>Deposits received (buy)</dt><dd><b>{{ money($r['buy']->fiat ?? 0, $code) }}</b></dd>
          <dt>Payouts sent (sell)</dt><dd><b>{{ money($r['sell']->payout ?? 0, $code) }}</b></dd>
          <dt>TDS withheld</dt><dd>{{ money(($r['sell']->fiat ?? 0) - ($r['sell']->payout ?? 0), $code) }}</dd>
          <dt>Orders</dt><dd>{{ ($r['buy']->orders ?? 0) }} buy · {{ ($r['sell']->orders ?? 0) }} sell</dd>
        </dl>
      </div>
    @empty
      <p class="empty">No completed orders in this period.</p>
    @endforelse
  </div>
</div>

<div class="split">
  <div class="box">
    <div class="box-head"><h3>Action queue</h3><a href="{{ route('admin.orders.index') }}" class="btn btn-ghost btn-sm">All orders</a></div>
    @include('partials.order-rows', ['orders' => $queue, 'admin' => true])
  </div>
  <div class="box">
    <div class="box-head"><h3>KYC queue</h3><a href="{{ route('admin.kyc.index') }}" class="btn btn-ghost btn-sm">All</a></div>
    @forelse ($kycQueue as $k)
      <a href="{{ route('admin.kyc.show', $k) }}" class="order-item" style="margin-bottom:8px">
        <span style="display:flex;gap:10px;align-items:center"><img src="https://flagcdn.com/w40/{{ strtolower($k->country ?? 'in') }}.png" alt="" width="22" height="22" style="border-radius:50%;object-fit:cover">
          <span><b>{{ $k->full_name }}</b><span class="sub muted" style="display:block;font-size:12.5px">{{ $k->idTypeLabel() }}{{ $k->video ? ' · 🎥 video' : '' }} · {{ $k->created_at->diffForHumans() }}</span></span></span>
        <span class="badge warn">Review</span>
      </a>
    @empty
      <p class="empty">No pending KYC 🎉</p>
    @endforelse
  </div>
</div>

<div class="box">
  <div class="box-head"><h3>Clients by country</h3><a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">All clients</a></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Country</th><th>Currency</th><th>Clients</th><th>Verified</th><th>Completed volume (USDT)</th></tr></thead>
    <tbody>
      @forelse ($countries as $c)
        <tr class="row-link" onclick="location.href='{{ route('admin.users.index', ['country' => $c->country]) }}'">
          <td><img src="https://flagcdn.com/w20/{{ strtolower($c->country) }}.png" alt="" width="18" height="13" style="vertical-align:middle;margin-right:8px">{{ config('countries.' . $c->country . '.0', $c->country) }}</td>
          <td class="mono">{{ config('countries.' . $c->country . '.2') }}</td>
          <td>{{ number_format($c->clients) }}</td>
          <td>{{ number_format($c->verified) }}</td>
          <td>{{ number_format((float) ($volByCountry[$c->country] ?? 0), 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty">No clients yet.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>
@endsection

@push('scripts')
<script>
  // Chart hover tooltip (mouse + keyboard focus)
  (function () {
    var chart = document.querySelector('.vchart'); if (!chart) return;
    var tip = chart.querySelector('.vtip');
    var show = function (el) {
      tip.textContent = el.dataset.tip; tip.hidden = false;
      var r = el.getBoundingClientRect(), c = chart.getBoundingClientRect();
      tip.style.left = Math.min(Math.max(r.left - c.left + r.width / 2, 80), c.width - 80) + 'px';
    };
    chart.querySelectorAll('.vday').forEach(function (d) {
      d.addEventListener('mouseenter', function () { show(d); });
      d.addEventListener('focus', function () { show(d); });
      d.addEventListener('mouseleave', function () { tip.hidden = true; });
      d.addEventListener('blur', function () { tip.hidden = true; });
    });
  })();
</script>
@endpush
