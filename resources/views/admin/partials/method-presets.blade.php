{{-- Country-aware helpers for the payment method forms (no extra buttons on screen):
     • picking a type fills "Title" with the usual name for that currency (e.g. LKR + Mobile wallet → "eZ Cash"), if the title is empty
     • UPI is hidden for currencies other than INR
     Params: $list = 'receive' (payout form) or 'pay' (deposit method form). --}}
<span data-presets-for="{{ $list }}" hidden></span>
@once
@push('scripts')
@php $methodPresets = json_encode(config('payment_presets'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); @endphp
<script>
(function () {
  var presets = {!! $methodPresets !!};
  function setup(marker) {
    var form = marker.closest('form'), list = marker.dataset.presetsFor;
    var cur = form.querySelector('select[name=currency]'), title = form.querySelector('input[name=title]'), typeSel = form.querySelector('select[name=type]');
    if (!cur || !title || !typeSel) return;
    var autoTitle = '';

    function usual(type) {
      var p = presets[cur.value] || presets['*'], items = (p && p[list]) || [];
      var hit = items.filter(function (it) { return it[0] === type; })[0];
      return hit ? hit[1] : '';
    }
    function fillTitle() {
      if (title.value && title.value !== autoTitle) return; // never overwrite what the admin typed
      autoTitle = usual(typeSel.value); title.value = autoTitle;
    }
    function onCurrency() {
      var upiOk = cur.value === 'INR';
      typeSel.querySelectorAll('option[value=upi]').forEach(function (o) { o.hidden = !upiOk; o.disabled = !upiOk; });
      form.querySelectorAll('[data-pick-type=upi]').forEach(function (c) { c.hidden = !upiOk; });
      if (!upiOk && typeSel.value === 'upi') {
        typeSel.value = 'bank'; typeSel.dispatchEvent(new Event('change'));
        form.querySelectorAll('[data-pick-type]').forEach(function (c) { c.classList.toggle('on', c.dataset.pickType === 'bank'); });
      }
      fillTitle();
    }
    cur.addEventListener('change', onCurrency);
    typeSel.addEventListener('change', fillTitle);
    onCurrency();
  }
  document.querySelectorAll('[data-presets-for]').forEach(setup);
})();
</script>
@endpush
@endonce
