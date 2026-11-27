@extends('layouts.admin')
@section('title', 'Buy & sell rates')
@section('heading', 'Buy & sell rates')

@section('content')
<p class="muted cur-intro">Change the USDT price for each active currency. New orders use the new rate straight away; orders already placed keep their rate.@if (auth()->user()->is_admin) Every change is logged with who made it.@endif</p>

<div class="box wl-box">
  <div class="wl-head"><b>Active currencies</b> <span class="muted">— buy = what clients pay per USDT · sell = what clients receive per USDT</span></div>
  @forelse ($currencies as $c)
    @php $sym = $c->symbol; $mine = old('_rate') === $c->code; @endphp
    <form method="POST" action="{{ route('admin.rates.update', $c) }}" class="form wl-item rate-row" id="rate-{{ $c->code }}" data-old-buy="{{ $c->buy_rate }}" data-old-sell="{{ $c->sell_rate }}">
      @csrf @method('PUT')
      <input type="hidden" name="_rate" value="{{ $c->code }}">
      <span class="wl-chip">{{ $c->code }}</span>
      <div class="wl-main">
        <b>{{ $c->name }} <span class="muted wl-name">({{ trim($sym) }})</span></b>
        <span class="wl-addr">
          @if ($c->rates_updated_at)Last changed {{ $c->rates_updated_at->diffForHumans() }} by <b>{{ $c->ratesUpdater?->name ?? 'removed user' }}</b>@else Not changed here yet @endif
          · <button type="button" class="linkish" data-market="{{ $c->code }}">↻ Market rate</button> <span class="muted" data-market-note></span>
        </span>
      </div>
      <label class="rate-in">Buy <input class="input" type="number" step="0.0001" min="0" name="buy_rate" value="{{ $mine ? old('buy_rate') : $c->buy_rate }}" required data-buy></label>
      <label class="rate-in">Sell <input class="input" type="number" step="0.0001" min="0" name="sell_rate" value="{{ $mine ? old('sell_rate') : $c->sell_rate }}" required data-sell></label>
      <span class="rate-margin">Margin <b data-margin data-sym="{{ $sym }}">{{ $sym }}{{ number_format($c->buy_rate - $c->sell_rate, 2) }}</b></span>
      <button class="btn btn-primary btn-sm">Save</button>
    </form>
  @empty
    <p class="muted" style="text-align:center;padding:20px 0">No active currencies.</p>
  @endforelse
</div>

<script>
(function () {
  var suggestUrl = @json(route('admin.currencies.suggest', ['code' => '__CODE__']));
  document.querySelectorAll('.rate-row').forEach(function (f) {
    var buy = f.querySelector('[data-buy]'), sell = f.querySelector('[data-sell]'), m = f.querySelector('[data-margin]');
    function upd() { var v = (parseFloat(buy.value) || 0) - (parseFloat(sell.value) || 0); m.textContent = m.dataset.sym + v.toFixed(2); m.classList.toggle('neg', v < 0); }
    buy.addEventListener('input', upd); sell.addEventListener('input', upd);

    // Big jumps (over 5%) ask for confirmation in the site pop-up
    f.addEventListener('submit', function (e) {
      if (f.dataset.bigOk === '1') { delete f.dataset.bigOk; return; }
      var ob = parseFloat(f.dataset.oldBuy), os = parseFloat(f.dataset.oldSell), nb = parseFloat(buy.value), ns = parseFloat(sell.value);
      var jump = Math.max(Math.abs(nb - ob) / ob, Math.abs(ns - os) / os);
      if (ob > 0 && os > 0 && jump > 0.05 && window.srmDialog) {
        e.preventDefault(); e.stopImmediatePropagation();
        window.srmDialog({ title: 'Big rate change', message: 'This changes the rate by ' + Math.round(jump * 100) + '%. Buy ' + ob + ' → ' + nb + ', sell ' + os + ' → ' + ns + '. Save it?', okText: 'Save', danger: true })
          .then(function (ok) { if (ok) { f.dataset.bigOk = '1'; f.requestSubmit ? f.requestSubmit() : f.submit(); } });
      }
    }, true);

    var mk = f.querySelector('[data-market]'), note = f.querySelector('[data-market-note]');
    mk.addEventListener('click', function () {
      note.textContent = 'checking…';
      fetch(suggestUrl.replace('__CODE__', mk.dataset.market), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (!d || !d.rates) { note.textContent = 'not available right now'; return; }
          buy.value = d.rates.buy; sell.value = d.rates.sell; upd();
          note.textContent = 'market ' + d.rates.market + ' → +2% / −1%. Review, then Save.';
        }).catch(function () { note.textContent = ''; });
    });
  });
})();
</script>
@endsection
