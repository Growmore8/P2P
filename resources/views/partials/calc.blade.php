{{-- Public P2P calculator widget (logged-in trade screen has its own markup). --}}
@php $curs = $pricing['currencies'] ?? []; @endphp
<div class="widget" data-calc data-mode="widget" data-side="{{ $side ?? 'buy' }}" data-amount="{{ $amount ?? '' }}">
  <div class="tabs">
    <button type="button" class="tab buy active" data-side="buy">Buy USDT</button>
    <button type="button" class="tab sell" data-side="sell">Sell USDT</button>
  </div>
  @if (count($curs) > 1)
    <select class="select" data-cur aria-label="Currency" style="margin-bottom:10px">
      @foreach ($curs as $c)
        <option value="{{ $c['code'] }}">{{ $c['code'] }} · {{ $c['name'] }}</option>
      @endforeach
    </select>
  @endif
  <div class="field">
    <label><span data-l="pay">You pay</span><span data-limits></span></label>
    <div class="field-row"><input type="text" inputmode="decimal" data-in="top" placeholder="0.00" autocomplete="off" aria-label="Amount"><span class="coin" data-coin="top"></span></div>
  </div>
  <div class="swap"><span>⇅</span></div>
  <div class="field">
    <label><span>You receive (approx.)</span><span></span></label>
    <div class="field-row"><input type="text" inputmode="decimal" data-in="bot" placeholder="0.00" autocomplete="off" aria-label="Receive amount"><span class="coin" data-coin="bot"></span></div>
  </div>
  <div class="err" data-err></div>
  <select class="select" data-net name="network" aria-label="Network">
    @foreach ($pricing['networks'] as $n)
      <option value="{{ $n['code'] }}" @selected(($network ?? null) === $n['code'])>{{ $n['name'] }}</option>
    @endforeach
  </select>
  <div class="rate-box" data-box></div>
  <button class="btn btn-primary btn-block" data-go>Buy USDT</button>
  <p class="widget-note">🔒 KYC-verified customers only · Own-account payments only</p>
</div>
