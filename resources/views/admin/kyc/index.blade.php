@extends('layouts.admin')
@section('title', 'KYC Review')
@section('heading', 'KYC Review')

@section('content')
<div class="tabs-line">
  @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $l)
    <a href="{{ route('admin.kyc.index', ['status' => $k]) }}" class="{{ $status === $k ? 'active' : '' }}">{{ $l }}</a>
  @endforeach
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, PAN / ID no., email, phone…">
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>ID</th><th>Contact</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $k)
      <tr class="row-link" onclick="location.href='{{ route('admin.kyc.show', $k) }}'">
        <td><b>{{ $k->full_name }}</b><span class="sub">{{ $k->city }}, {{ $k->state }}</span></td>
        <td class="mono">{{ $k->pan_number ?? $k->id_number }}<span class="sub">{{ $k->idTypeLabel() }} · {{ $k->countryName() }}</span></td>
        <td>{{ $k->user->phone }}<span class="sub">{{ $k->user->email }}</span></td>
        <td>{{ $k->created_at->format('d M, h:i A') }}</td>
        <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad'][$k->status] ?? 'warn' }}">{{ ucfirst($k->status) }}</span></td>
        <td><a href="{{ route('admin.kyc.show', $k) }}" class="btn btn-ghost btn-sm">Open</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">Nothing here.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $items->links() }}
</div>
@endsection
