{{-- Site-styled confirmation pop-up (replaces the browser's confirm / prompt boxes everywhere).
     • <form data-confirm="Message">                 → Yes / Cancel
     • <button data-confirm="Message"> inside a form  → only that button asks
     • <form data-type-confirm="CODE" data-type-message="Warning"> → admin must type CODE (sent as confirm_id) --}}
<style>
  /* Kept inline so the pop-up looks right even if an old copy of the site's CSS is cached. */
  .sd-wrap{position:fixed;inset:0;z-index:300;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(3,6,12,.6);backdrop-filter:blur(4px)}
  .sd-wrap.open{display:flex}
  .sd-box{width:100%;max-width:420px;background:var(--card,#fff);color:var(--text,#0d1424);border:1px solid var(--line,#e3e8f2);border-radius:20px;padding:24px 24px 20px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.35)}
  .sd-ico{width:52px;height:52px;margin:0 auto 12px;border-radius:50%;display:grid;place-items:center;font-size:24px;font-weight:800}
  .sd-ico.is-danger{background:#fee2e2;color:#dc2626}
  .sd-ico.is-info{background:var(--brand-soft,#eef3ff);color:var(--brand,#1f56e0)}
  .sd-box h3{font-size:18px;margin:0 0 8px}
  .sd-box p{margin:0 0 16px;color:var(--muted,#5b6577);font-size:14.5px;line-height:1.55;white-space:pre-line}
  .sd-type{text-align:left;margin:0 0 16px}
  .sd-type label{display:block;font-size:13px;margin-bottom:6px;color:var(--muted,#5b6577)}
  .sd-type b{color:var(--text,#0d1424);font-family:ui-monospace,Consolas,monospace}
  .sd-type .input{width:100%;box-sizing:border-box;padding:10px 12px;font-size:15px;letter-spacing:.5px}
  .sd-actions{display:flex;gap:10px}
  .sd-actions .btn{flex:1}
  .sd-actions .btn:disabled{opacity:.45;cursor:not-allowed}
  .sd-actions .btn-danger{background:#dc2626;border-color:#dc2626;color:#fff}
  .sd-actions .btn-danger:hover:not(:disabled){background:#b91c1c}
  :root[data-theme="dark"] .sd-ico.is-danger{background:rgba(239,68,68,.16);color:#f87171}
</style>
<div class="sd-wrap" id="srmDialog" aria-hidden="true" style="display:none">
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
    wrap.classList.remove('open'); wrap.style.display = 'none'; wrap.setAttribute('aria-hidden', 'true');
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
      wrap.classList.add('open'); wrap.style.display = 'flex'; wrap.setAttribute('aria-hidden', 'false');
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
