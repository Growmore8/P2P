@extends('layouts.admin')
@section('title', 'History')
@section('heading', 'History')

@section('content')
<div class="tabs-line">
  <a href="{{ route('admin.history', ['tab' => 'admin']) }}" class="{{ $tab === 'admin' ? 'active' : '' }}">Admin actions</a>
  <a href="{{ route('admin.history', ['tab' => 'orders']) }}" class="{{ $tab === 'orders' ? 'active' : '' }}">Order status changes</a>
  <a href="{{ route('admin.history', ['tab' => 'consents']) }}" class="{{ $tab === 'consents' ? 'active' : '' }}">Client consents</a>
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="{{ $tab === 'orders' ? 'Order no or note…' : ($tab === 'consents' ? 'Client name or email…' : 'Search description…') }}">
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  <div class="table-wrap"><table>
    @if ($tab === 'orders')
      <thead><tr><th>Time</th><th>Order</th><th>Status</th><th>Note</th><th>By</th></tr></thead>
      <tbody>
      @forelse ($items as $l)
        <tr>
          <td class="nowrap">{{ $l->created_at->format('d M Y, h:i A') }}</td>
          <td>@if ($l->order)<a class="mono green" href="{{ route('admin.orders.show', $l->order) }}">{{ $l->order->order_no }}</a> <span class="badge {{ $l->order->side }}">{{ strtoupper($l->order->side) }}</span>@endif</td>
          <td>{{ \App\Models\Order::LABELS[$l->status] ?? $l->status }}</td>
          <td>{{ $l->note }}</td>
          <td>{{ $l->actor?->name ?? 'System' }}@if ($l->actor?->is_admin) <span class="badge info">Admin</span>@endif</td>
        </tr>
      @empty <tr><td colspan="5" class="empty">Nothing yet.</td></tr> @endforelse
      </tbody>
    @elseif ($tab === 'consents')
      <thead><tr><th>Time</th><th>Client</th><th>Agreement</th><th>Version</th><th>Context</th><th>IP</th></tr></thead>
      <tbody>
      @forelse ($items as $c)
        <tr>
          <td class="nowrap">{{ $c->created_at->format('d M Y, h:i A') }}</td>
          <td>@if ($c->user)<a class="green" href="{{ route('admin.users.show', $c->user) }}">{{ $c->user->name }}</a><span class="sub">{{ $c->user->email }}</span>@endif</td>
          <td>{{ $c->label() }}</td>
          <td class="mono">{{ $c->version }}</td>
          <td class="mono">{{ $c->context }}</td>
          <td class="mono" title="{{ $c->user_agent }}">{{ $c->ip }}</td>
        </tr>
      @empty <tr><td colspan="6" class="empty">Nothing yet.</td></tr> @endforelse
      </tbody>
    @else
      <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
      <tbody>
      @forelse ($items as $a)
        <tr>
          <td class="nowrap">{{ $a->created_at->format('d M Y, h:i A') }}</td>
          <td>{{ $a->actor?->name ?? '—' }}</td>
          <td><span class="badge info">{{ $a->action }}</span></td>
          <td>{{ $a->description }}</td>
          <td class="mono">{{ $a->ip }}</td>
        </tr>
      @empty <tr><td colspan="5" class="empty">No admin actions recorded yet.</td></tr> @endforelse
      </tbody>
    @endif
  </table></div>
  {{ $items->links() }}
</div>
@endsection
