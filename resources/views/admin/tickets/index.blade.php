@extends('layouts.admin')
@section('title', 'Support Tickets')
@section('heading', 'Support Tickets')

@section('content')
<div class="tabs-line">
  @foreach (['open' => 'Open (needs reply)', 'answered' => 'Answered', 'resolved' => 'Resolved', 'closed' => 'Closed', 'all' => 'All'] as $k => $l)
    <a href="{{ route('admin.tickets.index', array_merge(request()->except('page', 'status'), ['status' => $k])) }}" class="{{ $status === $k ? 'active' : '' }}">{{ $l }}@if ($k !== 'all') ({{ $counts[$k] ?? 0 }})@endif</a>
  @endforeach
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Ticket ID, subject, client, order no…">
    <select name="category" class="input" style="max-width:200px">
      <option value="">All categories</option>
      @foreach (\App\Models\Ticket::CATEGORIES as $k => [$label])<option value="{{ $k }}" @selected(request('category') === $k)>{{ $label }}</option>@endforeach
    </select>
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Ticket</th><th>Client</th><th>Category</th><th>Order</th><th>Status</th><th>Last activity</th></tr></thead>
    <tbody>
    @forelse ($tickets as $t)
      <tr class="row-link" onclick="location.href='{{ route('admin.tickets.show', $t) }}'">
        <td><b>{{ \Illuminate\Support\Str::limit($t->subject, 48) }}</b><span class="sub mono">{{ $t->ticket_no }} · {{ $t->messages_count }} msg</span></td>
        <td>{{ $t->user->name }}<span class="sub">{{ $t->user->email }}</span></td>
        <td>{{ $t->categoryIcon() }} {{ $t->categoryLabel() }}</td>
        <td class="mono">{{ $t->order?->order_no ?? '—' }}</td>
        <td><span class="badge {{ $t->statusClass() }}">{{ $t->statusLabel() }}</span></td>
        <td class="nowrap">{{ ($t->last_reply_at ?? $t->created_at)->diffForHumans() }}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">No tickets here.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $tickets->links() }}
</div>
@endsection
