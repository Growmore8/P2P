@extends('layouts.admin')
@section('title', $u->name)
@section('heading', $u->name)

@section('content')
<a href="{{ route('admin.users.index') }}" class="muted" style="font-size:14px">← Back to clients</a>
<div class="split" style="margin-top:14px">
  <div>
    <div class="box">
      <div class="box-head"><h3>Orders</h3><span class="muted">Completed: @forelse ($volume as $v){{ money($v->total, $v->currency) }} ({{ number_format($v->usdt, 2) }} USDT)@if (! $loop->last) · @endif @empty none @endforelse</span></div>
      @include('partials.order-rows', ['orders' => $orders, 'admin' => true])
    </div>
    <div class="box">
      <h3 style="margin-bottom:14px">KYC history</h3>
      @forelse ($u->kycSubmissions as $k)
        <a href="{{ route('admin.kyc.show', $k) }}" class="order-item" style="margin-bottom:8px">
          <span><b>{{ $k->full_name }}</b> · <span class="mono">{{ $k->pan_number ?? $k->id_number }}</span><span class="sub muted" style="display:block;font-size:12.5px">{{ $k->created_at->format('d M Y') }} @if ($k->reject_reason)· {{ $k->reject_reason }}@endif</span></span>
          <span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad'][$k->status] ?? 'warn' }}">{{ $k->status }}</span>
        </a>
      @empty
        <p class="muted">No KYC submitted.</p>
      @endforelse
    </div>
  </div>
  <div>
    <div class="box">
      <h3 style="margin-bottom:14px">Profile</h3>
      <dl class="kv" style="grid-template-columns:100px 1fr">
        <dt>Client ID</dt><dd class="mono"><b>{{ $u->client_id }}</b></dd>
        <dt>Email</dt><dd>{{ $u->email }}</dd>
        <dt>Phone</dt><dd>{{ $u->phone }}</dd>
        <dt>Country</dt><dd><img src="https://flagcdn.com/w20/{{ strtolower($u->country) }}.png" alt="" width="18" height="13" style="vertical-align:middle"> {{ $u->countryName() }} · {{ $u->currencyCode() }}</dd>
        <dt>KYC</dt><dd>{{ $u->kyc_status }}</dd>
        <dt>Status</dt><dd><span class="badge {{ $u->isBlocked() ? 'bad' : 'ok' }}">{{ $u->status }}</span></dd>
        <dt>Joined</dt><dd>{{ $u->created_at->format('d M Y') }}</dd>
        <dt>Last login</dt><dd>{{ $u->last_login_at?->diffForHumans() ?? '—' }}</dd>
      </dl>
      @unless ($u->is_admin)
        @unless ($u->isDeleted())<form method="POST" action="{{ route('admin.users.block', $u) }}" data-confirm="{{ $u->isBlocked() ? 'Activate this account? The client can log in again.' : 'Deactivate this account? The client will be logged out and cannot log in.' }}" style="margin-top:16px">
          @csrf <button class="btn {{ $u->isBlocked() ? 'btn-primary' : 'btn-danger' }} btn-block btn-sm">{{ $u->isBlocked() ? 'Activate account' : 'Deactivate account' }}</button>
        </form>@endunless
        @if ($u->isDeleted())
          <div class="alert alert-error" style="margin-top:10px;font-size:13px">This account was deleted. Orders and KYC are kept for records.</div>
          <form method="POST" action="{{ route('admin.users.restore', $u) }}" data-confirm="Restore this account? The client can log in again." style="margin-top:8px">
            @csrf <button class="btn btn-primary btn-block btn-sm">↺ Restore account</button>
          </form>
        @elseif ($u->orders()->withTrashed()->exists())
          <form method="POST" action="{{ route('admin.users.destroy', $u) }}" data-type-confirm="{{ $u->client_id }}" style="margin-top:10px"
                data-type-message="⚠ This client has {{ $u->orders()->withTrashed()->count() }} orders. Deleting removes the account and they can't log in. Their orders and KYC are KEPT for legal records.">
            @csrf @method('DELETE') <button class="btn btn-ghost btn-block btn-sm" style="color:#b91c1c">🗑 Delete account</button>
          </form>
          <p class="muted" style="font-size:12px;margin-top:6px">Has orders — deleting keeps their orders and KYC for records. You'll be asked to type the client ID.</p>
        @else
          <form method="POST" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Delete this account permanently? KYC documents are deleted too. This cannot be undone." style="margin-top:10px">
            @csrf @method('DELETE') <button class="btn btn-ghost btn-block btn-sm" style="color:#b91c1c">🗑 Delete account permanently</button>
          </form>
        @endif
      @endunless
    </div>
    <div class="box">
      <h3 style="margin-bottom:14px">Payout accounts</h3>
      @forelse ($u->bankAccounts as $a)
        <p style="margin-bottom:8px">{{ $a->label() }}<span class="sub muted" style="display:block;font-size:12.5px">{{ $a->holder_name }} · {{ $a->currency }} @if ($a->ifsc)· {{ $a->ifsc }}@endif</span></p>
      @empty
        <p class="muted">None.</p>
      @endforelse
    </div>
    <div class="box">
      <div class="box-head"><h3>Consents &amp; agreements</h3>
        <span class="badge {{ $u->hasCurrentConsent() ? 'ok' : 'warn' }}">{{ $u->hasCurrentConsent() ? 'Current v' . \App\Models\Consent::currentVersion() : 'Needs re-accept' }}</span></div>
      @forelse ($u->consents->take(15) as $c)
        <p style="margin-bottom:8px;font-size:14px"><b>{{ $c->label() }}</b> <span class="mono muted">v{{ $c->version }}</span>@if ($c->context) · <span class="mono">{{ $c->context }}</span>@endif
          <span class="sub muted" style="display:block;font-size:12px">{{ $c->created_at->format('d M Y, h:i A') }} · IP {{ $c->ip }}</span></p>
      @empty
        <p class="muted">No consents recorded.</p>
      @endforelse
      @if ($u->consents->count() > 15)<a class="green" style="font-size:13px" href="{{ route('admin.history', ['tab' => 'consents', 'q' => $u->email]) }}">View all {{ $u->consents->count() }} →</a>@endif
    </div>
  </div>
</div>
@endsection
