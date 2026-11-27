{{-- Monthly limits per verification level. $c = Currency or null (new). --}}
@php
  $defaults = ['1' => [0, 0], '2' => [0, 0], '3' => [0, 0]];
  $ll = $c?->level_limits ?: $defaults;
@endphp
<div class="lvl-limits">
  <label>Monthly limits per client (0 = no limit)</label>
  <div class="lvl-grid">
    <span></span><b>Buy</b><b>Sell</b>
    @foreach ([1 => 'Level 1 · Identity', 2 => 'Level 2 · Video', 3 => 'Level 3 · EDD'] as $lvl => $label)
      <span>{{ $label }}</span>
      <input class="input" type="number" step="0.01" min="0" name="limits[{{ $lvl }}][buy]" value="{{ old("limits.$lvl.buy", $ll[$lvl][0] ?? $ll[(string) $lvl][0] ?? 0) }}" required>
      <input class="input" type="number" step="0.01" min="0" name="limits[{{ $lvl }}][sell]" value="{{ old("limits.$lvl.sell", $ll[$lvl][1] ?? $ll[(string) $lvl][1] ?? 0) }}" required>
    @endforeach
  </div>
</div>
