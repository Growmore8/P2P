{{-- Country checkboxes for a payment method. Shows only countries using the selected currency.
     Params: $selected (array|null), $currency (code). None ticked = all countries of that currency. --}}
@php $selected = (array) ($selected ?? []); @endphp
<div data-country-boxes>
  <label>Countries <span class="hint" style="display:inline">— leave all unticked to offer it in every country that uses this currency</span></label>
  <div class="cbox-grid">
    @foreach (config('countries') as $code => [$cname, $dial, $cur])
      <label class="cbox" data-cur="{{ $cur }}" @if ($cur !== ($currency ?? 'INR')) hidden @endif>
        <input type="checkbox" name="countries[]" value="{{ $code }}" @checked(in_array($code, $selected, true))>
        <img src="https://flagcdn.com/w20/{{ strtolower($code) }}.png" alt="" width="18" height="13"> {{ $cname }}
      </label>
    @endforeach
  </div>
</div>
@once
@push('scripts')
<script>
  document.querySelectorAll('form').forEach(function (form) {
    var cur = form.querySelector('select[name="currency"]'), box = form.querySelector('[data-country-boxes]');
    if (!cur || !box) return;
    cur.addEventListener('change', function () {
      box.querySelectorAll('.cbox').forEach(function (l) {
        var show = l.dataset.cur === cur.value; l.hidden = !show; if (!show) l.querySelector('input').checked = false;
      });
    });
  });
</script>
@endpush
@endonce
