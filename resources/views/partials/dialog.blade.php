{{-- Site-styled confirmation pop-up (replaces the browser's confirm / prompt boxes everywhere).
     • <form data-confirm="Message">                 → Yes / Cancel
     • <button data-confirm="Message"> inside a form  → only that button asks
     • <form data-type-confirm="CODE" data-type-message="Warning"> → admin must type CODE (sent as confirm_id) --}}
<div class="sd-wrap" id="srmDialog" aria-hidden="true">
  <div class="sd-box" role="alertdialog" aria-modal="true" aria-labelledby="sdTitle" aria-describedby="sdMsg">
    <div class="sd-ico" id="sdIco">!</div>
    <h3 id="sdTitle">Are you sure?</h3>
    <p id="sdMsg"></p>
    <div class="sd-type" id="sdTypeBox" hidden>
      <label for="sdInput">Type <b id="sdCode"></b> to confirm</label>
      <input class="input mono" id="sdInput" autocomplete="off" spellcheck="false">
    </div>
    <div class="sd-actions">
      <button type="button" class="btn btn-ghost" id="sdCancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="sdOk">Yes, continue</button>
    </div>
  </div>
</div>
<script>
(function () {
  var wrap = document.getElementById('srmDialog'), ok = document.getElementById('sdOk'), cancel = document.getElementById('sdCancel');
  var title = document.getElementById('sdTitle'), msg = document.getElementById('sdMsg'), ico = document.getElementById('sdIco');
  var typeBox = document.getElementById('sdTypeBox'), input = document.getElementById('sdInput'), codeEl = document.getElementById('sdCode');
  var done = null, code = '';
  var DANGER = /^(delete|remove|reject|deactivate|cancel|lock|block|close)\b/i;

  function close(result) {
    wrap.classList.remove('open'); wrap.setAttribute('aria-hidden', 'true');
    var d = done; done = null; if (d) d(result);
  }
  // srmDialog({ message, title, typeCode, okText, danger }) → Promise(false | true | typedText)
  window.srmDialog = function (o) {
    return new Promise(function (resolve) {
      done = resolve; code = o.typeCode || '';
      var danger = o.danger !== undefined ? o.danger : (!!code || DANGER.test(o.message || ''));
      var first = ((o.message || '').match(/[A-Za-z]+/) || [''])[0];
      title.textContent = o.title || (code ? 'Please confirm' : 'Are you sure?');
      msg.textContent = o.message || '';
      ico.textContent = danger ? '!' : '?'; ico.className = 'sd-ico ' + (danger ? 'is-danger' : 'is-info');
      ok.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
      ok.textContent = o.okText || (danger && first ? first.charAt(0).toUpperCase() + first.slice(1).toLowerCase() : 'Yes, continue');
      typeBox.hidden = !code; codeEl.textContent = code; input.value = '';
      ok.disabled = !!code;
      wrap.classList.add('open'); wrap.setAttribute('aria-hidden', 'false');
      setTimeout(function () { (code ? input : ok).focus(); }, 30);
    });
  };
  input.addEventListener('input', function () { ok.disabled = input.value.trim().toUpperCase() !== code.toUpperCase(); });
  ok.addEventListener('click', function () { if (!ok.disabled) close(code ? input.value.trim() : true); });
  cancel.addEventListener('click', function () { close(false); });
  wrap.addEventListener('click', function (e) { if (e.target === wrap) close(false); });
  document.addEventListener('keydown', function (e) {
    if (!wrap.classList.contains('open')) return;
    if (e.key === 'Escape') close(false);
    if (e.key === 'Enter' && document.activeElement === input && !ok.disabled) { e.preventDefault(); close(input.value.trim()); }
  });

  // Ask before submitting forms marked with data-confirm / data-type-confirm (or a button with data-confirm).
  document.addEventListener('submit', function (e) {
    var f = e.target, btn = e.submitter;
    if (f.dataset.srmConfirmed === '1') { delete f.dataset.srmConfirmed; return; }
    var message = (btn && btn.dataset && btn.dataset.confirm) || f.dataset.confirm, typeCode = f.dataset.typeConfirm;
    if (!message && !typeCode) return;
    e.preventDefault(); e.stopImmediatePropagation();
    var okText = (btn && btn.dataset && btn.dataset.okText) || f.dataset.okText || (typeCode ? 'Delete' : undefined);
    window.srmDialog({ message: typeCode ? (f.dataset.typeMessage || 'This cannot be undone.') : message, typeCode: typeCode, okText: okText }).then(function (res) {
      if (!res) return;
      if (typeCode) {
        var h = f.querySelector('input[name=confirm_id]') || f.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'confirm_id' }));
        h.value = res;
      }
      f.dataset.srmConfirmed = '1';
      if (f.requestSubmit) { btn && btn.form === f ? f.requestSubmit(btn) : f.requestSubmit(); }
      else { if (btn && btn.name) f.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: btn.name, value: btn.value })); f.submit(); }
    });
  }, true);
})();
</script>
