@extends('layouts.admin')
@section('title', 'KYC Verification')
@section('heading', 'KYC Verification')

@section('content')
<div class="tabs-line" style="margin-bottom:10px">
  @foreach ([1 => 'Level 1 · Identity', 2 => 'Level 2 · Video', 3 => 'Level 3 · EDD'] as $l => $label)
    <a href="{{ route('admin.kyc.index', ['level' => $l, 'status' => $status]) }}" class="{{ $level === $l ? 'active' : '' }}">{{ $label }}@if ($pending[$l]) <span class="count-pill">{{ $pending[$l] }}</span>@endif</a>
  @endforeach
</div>
<div class="tabs-line">
  @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $l)
    <a href="{{ route('admin.kyc.index', ['level' => $level, 'status' => $k]) }}" class="{{ $status === $k ? 'active' : '' }}" style="font-size:12.5px;padding:6px 12px">{{ $l }}</a>
  @endforeach
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    <input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="level" value="{{ $level }}">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="{{ $level === 1 ? 'Name, PAN / ID no., email, phone…' : 'Client name, email, phone…' }}">
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  <div class="table-wrap"><table>
    @if ($level === 1)
      <thead><tr><th>Name</th><th>ID</th><th>Contact</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse ($items as $k)
        <tr class="row-link" onclick="location.href='{{ route('admin.kyc.show', $k) }}'">
          <td><b>{{ $k->full_name }}</b><span class="sub">{{ $k->city }}{{ $k->state ? ', ' . $k->state : '' }}</span></td>
          <td class="mono">{{ $k->pan_number ?? $k->id_number }}<span class="sub">{{ $k->idTypeLabel() }} · {{ $k->countryName() }}</span></td>
          <td>{{ $k->user->phone }}<span class="sub">{{ $k->user->email }}</span></td>
          <td class="nowrap">{{ $k->created_at->format('d M, h:i A') }}</td>
          <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad'][$k->status] ?? 'warn' }}">{{ ucfirst($k->status) }}</span></td>
          <td><a href="{{ route('admin.kyc.show', $k) }}" class="btn btn-ghost btn-sm">Open</a></td>
        </tr>
      @empty <tr><td colspan="6" class="empty">Nothing here.</td></tr> @endforelse
      </tbody>
    @else
      <thead><tr><th>Client</th><th>Identity</th><th>{{ $level === 2 ? 'Video' : 'Source of funds' }}</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse ($items as $up)
        <tr class="row-link" onclick="location.href='{{ route('admin.kyc.upgrade.show', $up) }}'">
          <td><b>{{ $up->user->kyc?->full_name ?? $up->user->name }}</b><span class="sub">{{ $up->user->email }}</span></td>
          <td><span class="badge {{ $up->user->kyc_status === 'approved' ? 'ok' : 'warn' }}">L1 {{ $up->user->kyc_status }}</span></td>
          <td>@if ($level === 2)🎥 {{ strtoupper($up->details['lang'] ?? 'en') }} · {{ $up->details['size_mb'] ?? '?' }} MB @else {{ $up->details['source_of_funds'] ?? '—' }}<span class="sub">{{ $up->details['occupation'] ?? '' }}</span>@endif</td>
          <td class="nowrap">{{ $up->created_at->format('d M, h:i A') }}</td>
          <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad'][$up->status] ?? 'warn' }}">{{ ucfirst($up->status) }}</span></td>
          <td><a href="{{ route('admin.kyc.upgrade.show', $up) }}" class="btn btn-ghost btn-sm">Open</a></td>
        </tr>
      @empty <tr><td colspan="6" class="empty">Nothing here.</td></tr> @endforelse
      </tbody>
    @endif
  </table></div>
  {{ $items->links() }}
</div>
@endsection
