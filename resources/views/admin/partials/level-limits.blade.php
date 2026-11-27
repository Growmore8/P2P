{{-- Monthly limits per verification level — set automatically (config/limits.php, converted by rate). $c = Currency or null. --}}
@php $levels = [1 => 'Level 1 · Identity', 2 => 'Level 2 · Video', 3 => 'Level 3 · EDD']; @endphp
@if ($c)
  <div class="lvl-limits">
    <label>Monthly limits per client — set automatically{{ $c->isBase() ? '' : ' (converted from INR at the current rate)' }}</label>
    <div class="lvl-auto">
      @foreach ($levels as $lvl => $label)
        <div><span>{{ $label }}</span>
          <b>Buy {{ $c->limitFor($lvl, 'buy') > 0 ? money($c->limitFor($lvl, 'buy'), $c->code, 0) : 'No limit' }}</b>
          <b>Sell {{ $c->limitFor($lvl, 'sell') > 0 ? money($c->limitFor($lvl, 'sell'), $c->code, 0) : 'No limit' }}</b></div>
      @endforeach
    </div>
  </div>
@else
  <p class="hint" style="margin:6px 0 14px">Monthly limits are set automatically, converted from the INR limits at this currency's rate.</p>
@endif
