@extends('layouts.admin')
@section('title', 'Clients')
@section('heading', 'Clients')

@section('content')
<div class="tabs-line">
  @foreach (['' => 'All', 'none' => 'No KYC', 'pending' => 'KYC pending', 'approved' => 'Verified', 'rejected' => 'KYC rejected'] as $k => $l)
    <a href="{{ route('admin.users.index', array_filter(['kyc' => $k, 'q' => request('q')])) }}" class="{{ (string) request('kyc') === (string) $k ? 'active' : '' }}">{{ $l }}</a>
  @endforeach
  <a href="{{ route('admin.users.index', ['state' => 'locked']) }}" class="{{ request('state') === 'locked' ? 'active' : '' }}">Inactive</a>
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    @if (request('kyc'))<input type="hidden" name="kyc" value="{{ request('kyc') }}">@endif
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, email, phone or client ID…">
    <select class="input" name="country" style="max-width:200px" onchange="this.form.submit()">
      <option value="">All countries</option>
      @foreach (config('countries') as $code => [$cname]) <option value="{{ $code }}" @selected(request('country') === $code)>{{ $cname }}</option> @endforeach
    </select>
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Client</th><th>Phone</th><th>KYC</th><th>Orders</th><th>Account</th><th>Joined</th><th class="t-right">Actions</th></tr></thead>
    <tbody>
    @forelse ($users as $u)
      <tr class="row-link" onclick="location.href='{{ route('admin.users.show', $u) }}'">
        <td><b>{{ $u->name }}</b> @if ($u->is_admin)<span class="badge info">Admin</span>@endif @if (! $u->is_admin && ! $u->email_verified_at)<span class="badge warn">Email not verified</span>@endif<span class="sub"><span class="mono cid">{{ $u->client_id }}</span> · {{ $u->email }}</span></td>
        <td>{{ $u->phone }}<span class="sub">{{ $u->countryName() }}</span></td>
        <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad', 'pending' => 'warn'][$u->kyc_status] ?? '' }}">{{ $u->kyc_status }}</span></td>
        <td>{{ $u->orders_count }}</td>
        <td><span class="badge {{ $u->isBlocked() ? 'bad' : 'ok' }}">{{ $u->isBlocked() ? 'Inactive' : 'Active' }}</span></td>
        <td>{{ $u->created_at->format('d M Y') }}</td>
        <td class="t-right" onclick="event.stopPropagation()">
          <div class="icon-actions">
            <a href="{{ route('admin.users.show', $u) }}" class="icon-btn" title="View client" aria-label="View client">
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
            <form method="POST" action="{{ route('admin.users.block', $u) }}" data-confirm="{{ $u->isBlocked() ? 'Activate ' . $u->name . '? They can log in again.' : 'Deactivate ' . $u->name . '? They will be logged out and cannot log in.' }}">
              @csrf
              @if ($u->isBlocked())
                <button class="icon-btn ok" title="Activate account" aria-label="Activate account">
                  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg></button>
              @else
                <button class="icon-btn bad" title="Deactivate account" aria-label="Deactivate account">
                  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
              @endif
            </form>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="7" class="empty">No users found.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $users->links() }}
</div>
@endsection
