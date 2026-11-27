{{-- Binance-style order chat. Params: $order, $staff (bool). --}}
@php
  $feedUrl = route($staff ? 'admin.orders.chat' : 'orders.chat', $order);
  $sendUrl = route($staff ? 'admin.orders.chat.send' : 'orders.chat.send', $order);
@endphp
<div class="oc" id="orderChat" data-feed="{{ $feedUrl }}" data-send="{{ $sendUrl }}" data-status="{{ $order->status }}">
  <div class="oc-head">
    <div class="oc-av">{{ $staff ? strtoupper(mb_substr($order->user->name, 0, 1)) : 'S' }}</div>
    <div class="oc-who">
      <b>{{ $staff ? $order->user->name : 'SRM Fintech Solutions' }}</b>
      <small>{{ $staff ? ($order->user->client_id . ' · ' . $order->user->countryName()) : 'Order ' . $order->order_no . ' · ' . $order->statusLabel() }}</small>
    </div>
    @unless ($staff)
      <a class="oc-appeal" href="{{ route('support.create', ['order' => $order->order_no, 'category' => $order->isBuy() ? 'buy' : 'sell']) }}">Appeal</a>
    @endunless
  </div>

  <div class="oc-notice">
    @if ($staff)
      ⚠ Always check the money (or USDT) has actually arrived in your account before releasing. Slips and screenshots can be edited.
    @else
      ⚠ Only send payment to the details shown on this order. If you have paid, do not cancel the order. For any problem, tap <b>Appeal</b>.
    @endif
  </div>

  <div class="oc-body" id="ocBody" aria-live="polite"><p class="oc-empty">Loading…</p></div>

  <form class="oc-form" id="ocForm" enctype="multipart/form-data">
    <label class="oc-attach" title="Attach a photo or PDF">＋<input type="file" name="attachment" accept="image/*,.pdf" hidden></label>
    <textarea name="body" rows="1" maxlength="1000" placeholder="Enter message here" aria-label="Message"></textarea>
    <button class="oc-send" aria-label="Send">➤</button>
  </form>
  <p class="oc-file" id="ocFile" hidden></p>
  <p class="oc-err" id="ocErr"></p>
</div>

@push('scripts')
<script>
(function () {
  var root = document.getElementById('orderChat'); if (!root) return;
  var body = document.getElementById('ocBody'), form = document.getElementById('ocForm'), err = document.getElementById('ocErr'), fileNote = document.getElementById('ocFile');
  var fileInput = form.querySelector('input[type=file]'), ta = form.querySelector('textarea');
  var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '{{ csrf_token() }}';
  var startStatus = root.dataset.status, lastSig = '', sending = false;

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function render(items) {
    var sig = items.map(function (i) { return i.key; }).join(',');
    if (sig === lastSig) return; lastSig = sig;
    var nearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 80;
    body.innerHTML = items.length ? items.map(function (i) {
      var media = (i.image ? '<a href="' + esc(i.image) + '" target="_blank" rel="noopener" class="oc-img"><img src="' + esc(i.image) + '" alt="Attachment" loading="lazy"></a>' : '')
        + (i.file ? '<a href="' + esc(i.file) + '" target="_blank" rel="noopener" class="oc-pdf">📄 Open PDF</a>' : '');
      if (i.kind === 'system') {
        return '<div class="oc-sys"><p>' + esc(i.body) + '</p>' + media + '<small>' + esc(i.time) + '</small></div>';
      }
      return '<div class="oc-msg ' + i.kind + '">' + (i.kind === 'them' ? '<span class="oc-name">' + esc(i.name) + '</span>' : '')
        + (i.body ? '<p>' + esc(i.body).replace(/\n/g, '<br>') + '</p>' : '') + media + '<small>' + esc(i.time) + '</small></div>';
    }).join('') : '<p class="oc-empty">No messages yet.</p>';
    if (nearBottom || !body.dataset.seen) { body.scrollTop = body.scrollHeight; body.dataset.seen = '1'; }
  }

  function handle(d) {
    if (d.items) render(d.items);
    // The order moved on (e.g. released) — reload so the rest of the page updates too.
    if (d.status && d.status !== startStatus && !ta.value) location.reload();
  }

  function poll() {
    if (document.hidden) return;
    fetch(root.dataset.feed, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; }).then(function (d) { if (d) handle(d); }).catch(function () {});
  }

  fileInput.addEventListener('change', function () {
    var f = fileInput.files[0];
    fileNote.hidden = !f; fileNote.textContent = f ? '📎 ' + f.name + ' — tap ➤ to send' : '';
  });
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit')); } });
  ta.addEventListener('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 110) + 'px'; });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (sending || (!ta.value.trim() && !fileInput.files[0])) return;
    sending = true; err.textContent = '';
    var fd = new FormData(form);
    fetch(root.dataset.send, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, status: r.status, d: d }; }); })
      .then(function (res) {
        sending = false;
        if (!res.ok) {
          err.textContent = res.status === 429 ? 'Too many messages — please wait a minute.' : (res.d.errors ? Object.values(res.d.errors)[0][0] : 'Could not send. Please try again.');
          return;
        }
        form.reset(); ta.style.height = 'auto'; fileNote.hidden = true; handle(res.d);
        body.scrollTop = body.scrollHeight;
      })
      .catch(function () { sending = false; err.textContent = 'Network error — please try again.'; });
  });

  poll(); setInterval(poll, 5000);
  document.addEventListener('visibilitychange', poll);
})();
</script>
@endpush
