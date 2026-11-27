{{-- "Install the app" — any element with [data-install-app] becomes an install button.
     Android/Chrome/Edge: shows the browser's install prompt. iPhone/iPad: shows Add-to-Home-Screen steps.
     Hidden automatically when already running as the installed app. --}}
@once
@push('modals')
<div class="modal" id="iosInstall" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-label="Install the app" style="max-width:380px;text-align:center">
    <button type="button" class="modal-close" data-close aria-label="Close">×</button>
    <img src="{{ asset('images/app/icon-192.png') }}" alt="" width="64" height="64" style="border-radius:16px;margin:4px auto 10px;display:block">
    <h3 style="margin-bottom:6px">Install SRM Fintech</h3>
    <p class="muted" style="font-size:14px;margin-bottom:14px">Add the app to your home screen in 2 steps:</p>
    <ol class="ios-steps">
      <li>Tap the <b>Share</b> button <span class="ios-share" aria-hidden="true">⬆︎</span> at the bottom of Safari</li>
      <li>Choose <b>Add to Home Screen</b> ➕, then tap <b>Add</b></li>
    </ol>
  </div>
</div>
<script>
(function () {
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var deferred = null;
  function buttons() { return document.querySelectorAll('[data-install-app]'); }
  function show(on) { buttons().forEach(function (b) { b.hidden = !on; }); }

  if (standalone) { document.addEventListener('DOMContentLoaded', function () { show(false); }); return; }

  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferred = e; show(true); });
  window.addEventListener('appinstalled', function () { deferred = null; show(false); });
  document.addEventListener('DOMContentLoaded', function () {
    show(isIOS); // iPhone never fires beforeinstallprompt — show the steps instead
    var modal = document.getElementById('iosInstall');
    modal.addEventListener('click', function (e) { if (e.target === modal || e.target.closest('[data-close]')) modal.classList.remove('open'); });
    buttons().forEach(function (b) {
      b.addEventListener('click', function (e) {
        e.preventDefault();
        if (deferred) { deferred.prompt(); deferred.userChoice.finally(function () { deferred = null; }); }
        else modal.classList.add('open');
      });
    });
  });
})();
</script>
@endpush
@endonce
