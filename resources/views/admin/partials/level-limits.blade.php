{{-- Monthly limits per verification level. $c = Currency or null (new).
     Typed only on the base currency (INR); every other currency is converted automatically by its rate. --}}
@php
  $levels = [1 => 'Level 1 · Identity', 2 => 'Level 2 · Video', 3 => 'Level 3 · EDD'];
  $isBase = $c && $c->isBase();
  $base = \App\Models\Currency::base();
@endphp
@if ($isBase || ! $base)
  @php $ll = $c?->level_limits ?: ['1' => [0, 0], '2' => [0, 0], '3' => [0, 0]]; @endphp
  <div class="lvl-limits">
    <label>Monthly limits per client (0 = no limit) — <b>all other currencies follow these automatically</b></label>
    <div class="lvl-grid">
      <span></span><b>Buy</b><b>Sell</b>
      @foreach ($levels as $lvl => $label)
        <span>{{ $label }}</span>
        <input class="input" type="number" step="1" min="0" name="limits[{{ $lvl }}][buy]" value="{{ old("limits.$lvl.buy", (float) ($ll[(string) $lvl][0] ?? 0)) }}" required>
        <input class="input" type="number" step="1" min="0" name="limits[{{ $lvl }}][sell]" value="{{ old("limits.$lvl.sell", (float) ($ll[(string) $lvl][1] ?? 0)) }}" required>
      @endforeach
    </div>
  </div>
@elseif ($c)
  <div class="lvl-limits">
    <label>Monthly limits per client — set automatically from the {{ $base->code }} limits at today's rate</label>
    <div class="lvl-auto">
      @foreach ($levels as $lvl => $label)
        <div><span>{{ $label }}</span>
          <b>Buy {{ $c->limitFor($lvl, 'buy') > 0 ? money($c->limitFor($lvl, 'buy'), $c->code, 0) : 'No limit' }}</b>
          <b>Sell {{ $c->limitFor($lvl, 'sell') > 0 ? money($c->limitFor($lvl, 'sell'), $c->code, 0) : 'No limit' }}</b></div>
      @endforeach
    </div>
    <span class="hint">To change, edit the limits on <a class="green" href="#cur-{{ $base->code }}">{{ $base->code }}</a>.</span>
  </div>
@else
  <p class="hint" style="margin:6px 0 14px">Monthly limits are set automatically from the {{ $base->code }} limits, converted at this currency's rate.</p>
@endif
