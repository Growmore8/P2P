{{-- Website chat: instant assistant answers + messages to the admin Live Chat inbox. --}}
@php $me = auth()->user(); @endphp
<div class="cw" id="cw" data-poll="{{ route('chat.poll') }}" data-send="{{ route('chat.send') }}" data-need-contact="{{ $me ? 0 : 1 }}">
  <button type="button" class="cw-bubble" id="cwBubble" aria-label="Chat with us" aria-expanded="false">
    <span class="cw-bubble-ico" aria-hidden="true">💬</span>
    <span class="cw-badge" id="cwBadge" hidden>1</span>
  </button>

  <section class="cw-panel" id="cwPanel" role="dialog" aria-label="Chat with SRM Fintech Solutions" hidden>
    <header class="cw-head">
      <div class="cw-avatar"><img src="{{ asset('images/logo-mark.png') }}" alt=""><span class="cw-online"></span></div>
      <div class="cw-head-text"><b>SRM Assistant</b><small>Online · We're here to help</small></div>
      <button type="button" class="cw-close" id="cwClose" aria-label="Close chat">✕</button>
    </header>

    <div class="cw-body" id="cwBody" aria-live="polite"></div>

    <div class="cw-chips" id="cwChips">
      <button type="button" data-q="What are today's rates?">💱 Rates</button>
      <button type="button" data-q="How do I buy USDT?">⇣ How to buy</button>
      <button type="button" data-q="How do I sell USDT?">⇡ How to sell</button>
      <button type="button" data-q="What is needed for KYC verification?">🛡 KYC</button>
      <button type="button" data-q="My order is pending, please check">⏱ Order status</button>
    </div>

    <form class="cw-form" id="cwForm" autocomplete="off" novalidate>
      <div class="cw-contact" id="cwContact" @if ($me) hidden @endif>
        <input type="text" name="name" placeholder="Your name" maxlength="80" aria-label="Your name">
        <input type="email" name="email" placeholder="Email (for our reply)" maxlength="150" aria-label="Your email">
      </div>
      <input type="text" name="website" class="cw-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="cw-input">
        <textarea name="message" rows="1" maxlength="1000" placeholder="Type your message…" aria-label="Message"></textarea>
        <button type="submit" aria-label="Send">➤</button>
      </div>
      <p class="cw-err" id="cwErr"></p>
      <p class="cw-legal">Automatic replies are labelled. Never share your password, OTP or UPI PIN.</p>
    </form>
  </section>
</div>

@push('scripts')
<script>
(function () {
  var root = document.getElementById('cw'); if (!root) return;
  var bubble = document.getElementById('cwBubble'), panel = document.getElementById('cwPanel'), body = document.getElementById('cwBody');
  var form = document.getElementById('cwForm'), err = document.getElementById('cwErr'), badge = document.getElementById('cwBadge');
  var contact = document.getElementById('cwContact'), chips = document.getElementById('cwChips');
  var csrf = document.querySelector('meta[name=csrf-token]').content;
  var last = 0, started = false, open = false, sending = false, needContact = root.dataset.needContact === '1';

  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  function add(m) {
    if (m.id && m.id <= last) return; if (m.id) last = m.id;
    var cls = m.from === 'visitor' ? 'me' : (m.from === 'bot' ? 'bot' : 'staff');
    var label = m.from === 'visitor' ? '' : '<span class="cw-who">' + esc(m.name) + '</span>';
    body.insertAdjacentHTML('beforeend', '<div class="cw-msg ' + cls + '">' + label + '<div class="cw-text">' + esc(m.body) + '</div>' + (m.time ? '<span class="cw-time">' + esc(m.time) + '</span>' : '') + '</div>');
    body.scrollTop = body.scrollHeight;
  }
  function poll(markRead) {
    return fetch(root.dataset.poll + '?after=' + last + (markRead ? '&open=1' : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (d) {
        if (!d.started) {
          if (!body.children.length) add({ from: 'bot', name: 'SRM Assistant · automatic reply', body: d.greeting });
          return;
        }
        started = true; contact.hidden = true; needContact = false;
        (d.messages || []).forEach(add);
        if (!open && d.unread > 0) { badge.textContent = d.unread; badge.hidden = false; }
      }).catch(function () {});
  }
  function toggle(show) {
    open = show; panel.hidden = !show; bubble.setAttribute('aria-expanded', String(show)); root.classList.toggle('is-open', show);
    if (show) { badge.hidden = true; poll(true); setTimeout(function () { form.message.focus(); }, 100); }
  }
  bubble.addEventListener('click', function () { toggle(!open); });
  document.getElementById('cwClose').addEventListener('click', function () { toggle(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && open) toggle(false); });
  if (location.hash === '#chat') toggle(true);

  chips.addEventListener('click', function (e) { var b = e.target.closest('button[data-q]'); if (!b) return; form.message.value = b.dataset.q; send(); });
  form.message.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
  form.message.addEventListener('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 110) + 'px'; });
  form.addEventListener('submit', function (e) { e.preventDefault(); send(); });

  function send() {
    var text = form.message.value.trim(); if (!text || sending) return;
    if (needContact && (!form.name.value.trim() || !/^\S+@\S+\.\S+$/.test(form.email.value.trim()))) {
      err.textContent = 'Please add your name and email so our team can reply.'; (form.name.value.trim() ? form.email : form.name).focus(); return;
    }
    err.textContent = ''; sending = true;
    var fd = new FormData(form); fd.append('after', last);
    add({ from: 'visitor', body: text });            // optimistic
    var tmpLast = last; form.message.value = ''; form.message.style.height = 'auto';
    fetch(root.dataset.send, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        sending = false;
        if (!res.ok) { err.textContent = res.d.errors ? Object.values(res.d.errors)[0][0] : (res.d.message || 'Could not send. Please try again.'); return; }
        // replace the optimistic bubble with the server's copy
        var mine = body.querySelector('.cw-msg.me:last-child'); if (mine) mine.remove();
        last = tmpLast; (res.d.messages || []).forEach(add);
        started = true; needContact = false; contact.hidden = true;
      }).catch(function () { sending = false; err.textContent = 'Network error — please try again.'; });
  }

  poll(false);
  setInterval(function () { if (!document.hidden && (open || started)) poll(open); }, open ? 4000 : 15000);
  setInterval(function () { if (!document.hidden && open) poll(true); }, 4000);
})();
</script>
@endpush
