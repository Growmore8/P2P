{{-- $orders, $admin (bool) --}}
<div class="table-wrap">
  <table>
    <thead><tr>
      <th>Order</th>@if ($admin ?? false)<th>Customer</th>@endif<th>Type</th><th>USDT</th><th>Fiat</th><th>Rate</th><th>Status</th><th>Date</th>
    </tr></thead>
    <tbody>
    @forelse ($orders as $o)
      @php $url = ($admin ?? false) ? route('admin.orders.show', $o) : route('orders.show', $o); @endphp
      <tr class="row-link" onclick="location.href='{{ $url }}'">
        <td><a href="{{ $url }}" class="mono"><b style="font-size:13.5px">{{ $o->order_no }}</b></a></td>
        @if ($admin ?? false)<td>{{ $o->user->name }}<span class="sub">{{ $o->user->phone }}</span></td>@endif
        <td><span class="badge {{ $o->side }}">{{ strtoupper($o->side) }}</span> <span class="sub">{{ $o->network }}</span></td>
        <td>{{ number_format($o->usdt_amount, 2) }}</td>
        <td>{{ $o->money($o->clientFiat()) }}</td>
        <td>{{ $o->money($o->rate) }}</td>
        <td><span class="badge {{ $o->statusClass() }}">{{ $o->statusLabel() }}</span></td>
        <td class="nowrap">{{ $o->created_at->format('d M, h:i A') }}</td>
      </tr>
    @empty
      <tr><td colspan="8" class="empty">No orders found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
