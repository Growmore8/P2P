@extends('layouts.admin')
@section('title', 'Clients')
@section('heading', 'Clients')

@section('content')
<div class="tabs-line">
  @foreach (['' => 'All', 'none' => 'No KYC', 'pending' => 'KYC pending', 'approved' => 'Verified', 'rejected' => 'KYC rejected'] as $k => $l)
    <a href="{{ route('admin.users.index', array_filter(['kyc' => $k, 'q' => request('q')])) }}" class="{{ (string) request('kyc') === (string) $k ? 'active' : '' }}">{{ $l }}</a>
  @endforeach
  <a href="{{ route('admin.users.index', ['state' => 'locked']) }}" class="{{ request('state') === 'locked' ? 'active' : '' }}">🔒 Locked</a>
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
    <thead><tr><th>Client</th><th>Phone</th><th>KYC</th><th>Orders</th><th>Account</th><th>Joined</th></tr></thead>
    <tbody>
    @forelse ($users as $u)
      <tr class="row-link" onclick="location.href='{{ route('admin.users.show', $u) }}'">
        <td><b>{{ $u->name }}</b> @if ($u->is_admin)<span class="badge info">Admin</span>@endif @if (! $u->is_admin && ! $u->email_verified_at)<span class="badge warn">Email not verified</span>@endif<span class="sub"><span class="mono cid">{{ $u->client_id }}</span> · {{ $u->email }}</span></td>
        <td>{{ $u->phone }}<span class="sub">{{ $u->countryName() }}</span></td>
        <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad', 'pending' => 'warn'][$u->kyc_status] ?? '' }}">{{ $u->kyc_status }}</span></td>
        <td>{{ $u->orders_count }}</td>
        <td onclick="event.stopPropagation()">
          <form method="POST" action="{{ route('admin.users.block', $u) }}" data-confirm="{{ $u->isBlocked() ? 'Unlock ' . $u->name . '? They can log in again.' : 'Lock ' . $u->name . '? They will be logged out and cannot log in.' }}">
            @csrf
            <button class="acct-toggle {{ $u->isBlocked() ? 'is-locked' : '' }}" title="{{ $u->isBlocked() ? 'Click to unlock' : 'Click to lock' }}">{{ $u->isBlocked() ? '🔒 Locked' : '● Active' }}</button>
          </form>
        </td>
        <td>{{ $u->created_at->format('d M Y') }}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">No users found.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $users->links() }}
</div>
@endsection
