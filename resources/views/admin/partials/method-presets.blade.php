{{-- Quick-pick buttons for the usual payment methods of the chosen currency (config/payment_presets.php).
     Params: $list = 'receive' (payout form) or 'pay' (deposit method form).
     Place <div data-presets-for></div> inside a form that has select[name=currency], a type field and input[name=title]. --}}
<div class="preset-chips" data-presets-for="{{ $list }}"></div>
@once
@push('scripts')
@php $methodPresets = json_encode(config('payment_presets'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); @endphp
<script>
(function () {
  var presets = {!! $methodPresets !!};
  function setup(box) {
    var form = box.closest('form'), list = box.dataset.presetsFor;
    var cur = form.querySelector('select[name=currency]'), title = form.querySelector('input[name=title]');
    var typeSel = form.querySelector('select[name=type]');
    if (!cur || !title || !typeSel) return;

    function setType(t) {
      typeSel.value = t; typeSel.dispatchEvent(new Event('change'));
      form.querySelectorAll('[data-pick-type]').forEach(function (c) { c.classList.toggle('on', c.dataset.pickType === t); });
    }
    function render() {
      var code = cur.value, p = presets[code] || presets['*'], items = (p && p[list]) || [];
      // UPI exists only in India — hide it for other currencies.
      var upiOk = code === 'INR';
      typeSel.querySelectorAll('option[value=upi]').forEach(function (o) { o.hidden = !upiOk; o.disabled = !upiOk; });
      form.querySelectorAll('[data-pick-type=upi]').forEach(function (c) { c.hidden = !upiOk; });
      if (!upiOk && typeSel.value === 'upi') setType('bank');

      box.innerHTML = items.length ? '<span class="preset-label">Usual in ' + code + ':</span>' : '';
      items.forEach(function (it) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'preset-chip'; b.textContent = '+ ' + it[1];
        b.addEventListener('click', function () {
          setType(it[0]); title.value = it[1];
          box.querySelectorAll('.preset-chip').forEach(function (x) { x.classList.toggle('on', x === b); });
          title.focus();
        });
        box.appendChild(b);
      });
    }
    cur.addEventListener('change', render); render();
  }
  document.querySelectorAll('[data-presets-for]').forEach(setup);
})();
</script>
@endpush
@endonce
