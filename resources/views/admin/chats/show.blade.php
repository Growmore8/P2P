@extends('layouts.admin')
@section('title', 'Chat · ' . $chat->displayName())
@section('heading', 'Chat · ' . $chat->displayName())

@section('content')
<a href="{{ route('admin.chats.index') }}" class="muted" style="font-size:14px">← Back to Live Chat</a>
<div class="split" style="margin-top:14px">
  <div class="box">
    <div class="thread" id="adminThread" data-poll="{{ route('admin.chats.poll', $chat) }}" data-last="{{ $chat->messages->last()?->id ?? 0 }}" style="max-height:60vh;overflow:auto;padding-right:6px">
      @foreach ($chat->messages as $m)
        <div class="bubble {{ $m->sender === 'visitor' ? 'staff' : 'me' }} {{ $m->sender === 'bot' ? 'is-bot' : '' }}">
          <div class="bubble-head">{{ $m->sender === 'visitor' ? $chat->displayName() : ($m->sender === 'bot' ? 'Assistant (automatic)' : 'Support · ' . ($m->author?->name ?? 'Admin')) }} · {{ $m->created_at->format('d M, h:i A') }}</div>
          <div class="bubble-body">{{ $m->body }}</div>
        </div>
      @endforeach
    </div>
    <form method="POST" action="{{ route('admin.chats.reply', $chat) }}" class="form" id="adminReply" style="margin-top:14px">
      @csrf
      <textarea class="input" name="message" rows="3" maxlength="2000" placeholder="Type a reply — it appears in the visitor's chat{{ $chat->contactEmail() ? ' and is emailed to ' . $chat->contactEmail() : '' }}" required></textarea>
      <div class="actions"><button class="btn btn-primary">Send reply</button><span class="muted" id="replyStatus" style="font-size:13px;align-self:center"></span></div>
    </form>
  </div>
  <div>
    <div class="box">
      <h3 style="margin-bottom:12px">Visitor</h3>
      <dl class="kv" style="grid-template-columns:90px 1fr">
        <dt>Name</dt><dd>{{ $chat->displayName() }}</dd>
        <dt>Email</dt><dd>{{ $chat->contactEmail() ?? '—' }}</dd>
        @if ($chat->user)<dt>Account</dt><dd><a class="green" href="{{ route('admin.users.show', $chat->user) }}">Open client profile →</a></dd>@endif
        <dt>Started</dt><dd>{{ $chat->created_at->format('d M Y, h:i A') }}</dd>
        <dt>Page</dt><dd style="word-break:break-all">{{ $chat->page ?: '—' }}</dd>
        <dt>Status</dt><dd><span class="badge {{ $chat->status === 'open' ? 'ok' : 'bad' }}">{{ $chat->status }}</span></dd>
      </dl>
      <form method="POST" action="{{ route('admin.chats.close', $chat) }}" style="margin-top:14px">@csrf
        <button class="btn btn-ghost btn-sm btn-block">{{ $chat->status === 'closed' ? 'Re-open chat' : 'Close chat' }}</button></form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var thread = document.getElementById('adminThread'), form = document.getElementById('adminReply'), st = document.getElementById('replyStatus');
  var last = parseInt(thread.dataset.last, 10) || 0, visitor = @json($chat->displayName());
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  function add(m) {
    if (m.id <= last) return; last = m.id;
    var who = m.from === 'visitor' ? visitor : (m.from === 'bot' ? 'Assistant (automatic)' : 'Support');
    thread.insertAdjacentHTML('beforeend', '<div class="bubble ' + (m.from === 'visitor' ? 'staff' : 'me') + (m.from === 'bot' ? ' is-bot' : '') + '"><div class="bubble-head">' + esc(who) + ' · ' + esc(m.time) + '</div><div class="bubble-body">' + esc(m.body) + '</div></div>');
    thread.scrollTop = thread.scrollHeight;
  }
  thread.scrollTop = thread.scrollHeight;
  setInterval(function () {
    if (document.hidden) return;
    fetch(thread.dataset.poll + '?after=' + last, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) { (d.messages || []).forEach(add); }).catch(function () {});
  }, 5000);
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(form); st.textContent = 'Sending…';
    fetch(form.action, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
      .then(function (r) { return r.json(); }).then(function (d) {
        if (d.message) { add(d.message); form.reset(); st.textContent = d.mailed ? 'Sent · emailed ✓' : 'Sent'; } else { st.textContent = 'Could not send'; }
      }).catch(function () { st.textContent = 'Network error'; });
  });
})();
</script>
@endpush
