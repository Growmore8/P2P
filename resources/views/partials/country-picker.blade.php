{{-- Country picker with flags + search. Params: $name (input name), $selected (ISO code), $showName (bool) --}}
@php
  $countries = config('countries');
  $cc = strtoupper($selected ?? 'IN');
  if (! isset($countries[$cc])) { $cc = 'IN'; }
@endphp
<div class="cpick" data-country-picker>
  <input type="hidden" name="{{ $name ?? 'country' }}" value="{{ $cc }}">
  <button type="button" class="cpick-btn" aria-label="Select country">
    <img src="https://flagcdn.com/w40/{{ strtolower($cc) }}.png" alt="" width="22" height="22" data-flag>
    @if ($showName ?? false)<span data-cname>{{ $countries[$cc][0] }}</span>@endif
    <span data-dial>+{{ $countries[$cc][1] }}</span>
    <span class="cpick-caret">▾</span>
  </button>
</div>

@once
@push('scripts')
<div class="modal" id="countryModal" aria-hidden="true">
  <div class="modal-box cmodal" role="dialog" aria-modal="true" aria-label="Select country">
    <div class="cmodal-head"><h3>Select Country</h3><button type="button" class="modal-close" data-close aria-label="Close">×</button></div>
    <input type="search" class="input cmodal-search" placeholder="Search" autocomplete="off">
    <ul class="clist">
      @foreach ($countries as $code => [$cname, $dial])
        <li><button type="button" data-code="{{ $code }}" data-name="{{ $cname }}" data-dial="{{ $dial }}">
          <img src="https://flagcdn.com/w40/{{ strtolower($code) }}.png" alt="" width="28" height="28" loading="lazy">
          <span class="cname">{{ $cname }}</span><span class="cdial">+{{ $dial }}</span><span class="carrow">›</span>
        </button></li>
      @endforeach
    </ul>
  </div>
</div>
@endpush
@endonce
