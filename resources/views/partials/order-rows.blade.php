{{-- $orders, $admin (bool) --}}
<div class="table-wrap">
  <table>
    <thead><tr>
      <th>Order</th>@if ($admin ?? false)<th>Customer</th>@endif<th>Type</th><th>USDT</th><th>Fiat</th><th>Rate</th><th>Status</th>@if ($admin ?? false)<th>Review</th>@endif<th>Date</th>@if (($admin ?? false) && auth()->user()?->is_admin)<th></th>@endif
    </tr></thead>
    <tbody>
    @forelse ($orders as $o)
      @php $url = ($admin ?? false) ? route('admin.orders.show', $o) : route('orders.show', $o); @endphp
      <tr class="row-link" onclick="location.href='{{ $url }}'">
        <td><a href="{{ $url }}" class="mono"><b style="font-size:13.5px">{{ $o->order_no }}</b></a></td>
        @if ($admin ?? false)<td>{{ $o->user->name }}<span class="sub">{{ auth()->user()?->is_admin ? $o->user->phone : $o->user->maskedPhone() }}</span></td>@endif
        <td><span class="badge {{ $o->side }}">{{ strtoupper($o->side) }}</span> <span class="sub">{{ $o->network }}</span></td>
        <td>{{ number_format($o->usdt_amount, 2) }}</td>
        <td>{{ $o->money($o->clientFiat()) }}</td>
        <td>{{ $o->money($o->rate) }}</td>
        <td><span class="badge {{ $o->statusClass() }}">{{ $o->statusLabel() }}</span></td>
        @if ($admin ?? false)
          <td>
            @if ($o->review_status === 'received')<span class="badge ok" title="{{ $o->reviewer?->name }}">✓ Reviewed</span>
            @elseif ($o->review_status === 'not_received')<span class="badge bad" title="{{ $o->review_note }}">✕ Not received</span>
            @elseif ($o->status === 'payment_submitted')<span class="badge warn">⏳ Waiting</span>
            @else<span class="muted">—</span>@endif
          </td>
        @endif
        <td class="nowrap">{{ $o->created_at->format('d M, h:i A') }}</td>
        @if (($admin ?? false) && auth()->user()?->is_admin)
          <td onclick="event.stopPropagation()" class="t-right">
            @if ($o->trashed())
              <form method="POST" action="{{ route('admin.orders.restore', $o) }}" data-confirm="Restore order {{ $o->order_no }}? It will show in the lists again." data-ok-text="Restore">
                @csrf <button class="wl-btn edit" title="Restore" aria-label="Restore order">↺</button></form>
            @elseif (! $o->isOpen())
              @php $paid = $o->status === 'completed' || $o->paid_at; @endphp
              <form method="POST" action="{{ route('admin.orders.destroy', $o) }}"
                    @if ($paid) data-type-confirm="{{ $o->order_no }}" data-type-message="⚠ This order is {{ strtolower($o->statusLabel()) }} ({{ $o->money($o->fiat_amount) }}). Deleting hides it from all lists, but it is KEPT in the records and can be restored."
                    @else data-confirm="Delete order {{ $o->order_no }} ({{ strtolower($o->statusLabel()) }})? It is hidden from all lists and can be restored." @endif>
                @csrf @method('DELETE') <button class="wl-btn del" title="Delete order" aria-label="Delete order"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button></form>
            @else
              <span class="wl-btn del is-off" title="In progress — complete or reject it first"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></span>
            @endif
          </td>
        @endif
      </tr>
    @empty
      <tr><td colspan="10" class="empty">No orders found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
