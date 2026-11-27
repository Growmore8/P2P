@extends('layouts.admin')
@section('title', 'Live Chat')
@section('heading', 'Live Chat')

@section('content')
<div class="tabs-line">
  @foreach (['open' => 'Open', 'closed' => 'Closed', 'all' => 'All'] as $k => $l)
    <a href="{{ route('admin.chats.index', ['status' => $k]) }}" class="{{ $status === $k ? 'active' : '' }}">{{ $l }}</a>
  @endforeach
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Name or email…">
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  @forelse ($chats as $c)
    @php $last = $c->messages->first(); @endphp
    <a href="{{ route('admin.chats.show', $c) }}" class="order-item chat-row {{ $c->unread_admin ? 'is-unread' : '' }}" style="margin-bottom:8px">
      <span style="display:flex;gap:12px;align-items:center;min-width:0">
        <span class="avatar-sm" style="flex-shrink:0">{{ strtoupper(mb_substr($c->displayName(), 0, 1)) }}</span>
        <span style="min-width:0"><b>{{ $c->displayName() }}</b> @if ($c->user_id)<span class="badge info">Client</span>@endif
          <span class="sub muted" style="display:block;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px">{{ $last ? ($last->sender === 'visitor' ? '' : ($last->sender === 'admin' ? 'You: ' : 'Bot: ')) . \Illuminate\Support\Str::limit($last->body, 90) : '' }}</span></span>
      </span>
      <span style="text-align:right;flex-shrink:0">
        <small class="muted">{{ $c->last_message_at?->diffForHumans() }}</small>
        @if ($c->unread_admin)<span class="count-pill" style="display:inline-block;margin-left:6px">{{ $c->unread_admin }}</span>@endif
      </span>
    </a>
  @empty
    <p class="empty">No chats yet. Messages from the website chat bubble appear here.</p>
  @endforelse
  {{ $chats->links() }}
</div>
@endsection
