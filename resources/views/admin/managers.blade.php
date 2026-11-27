@extends('layouts.admin')
@section('title', 'Managers')
@section('heading', 'Managers')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-modal-open="addModal">+ Add manager</button>@parent @endsection

@section('content')
<p class="muted cur-intro">Managers can only see buy and sell orders. They check whether a client's payment (or USDT) has arrived and mark the order <b>Reviewed</b> — then you approve it. They can't change settings, rates, KYC or clients.</p>

@php
  $addOpen = $errors->any() && old('_modal') === 'add';
  $pwOpen = $errors->has('password') && old('_modal') && old('_modal') !== 'add' ? old('_modal') : null;
  $lock = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
  $unlock = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>';
  $key = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/></svg>';
  $trash = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
@endphp

{{-- ===== Add manager pop-up ===== --}}
<div class="modal {{ $addOpen ? 'open' : '' }}" id="addModal" data-admin-modal aria-hidden="{{ $addOpen ? 'false' : 'true' }}">
  <form method="POST" action="{{ route('admin.managers.store') }}" class="modal-box form wl-modal" role="dialog" aria-modal="true" aria-label="Add manager" autocomplete="off">
    @csrf
    <input type="hidden" name="_modal" value="add">
    <h3>Add manager</h3>
    <input class="input" name="name" value="{{ $addOpen ? old('name') : '' }}" placeholder="Full name" required>
    <input class="input" type="email" name="email" value="{{ $addOpen ? old('email') : '' }}" placeholder="Email (used to log in)" required>
    <input class="input" name="phone" value="{{ $addOpen ? old('phone') : '' }}" placeholder="Phone, e.g. +91 98765 43210" required>
    <input class="input" type="password" name="password" placeholder="Password (min 8, letters + numbers)" required autocomplete="new-password">
    <p class="addr-check">They log in on the normal login page with this email and password.</p>
    <div class="wl-modal-actions">
      <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
      <button class="btn btn-save">Save</button>
    </div>
  </form>
</div>

<div class="box wl-box">
  <div class="wl-head"><b>Managers</b> <span class="muted">— {{ $managers->where('status', 'active')->count() }} active of {{ $managers->count() }}</span></div>

  @forelse ($managers as $m)
    @php $active = ! $m->isBlocked(); @endphp
    <div class="wl-item {{ $active ? '' : 'is-off' }}" id="manager-{{ $m->id }}">
      <div class="wl-row">
        <span class="mgr-av">{{ strtoupper(mb_substr($m->name, 0, 1)) }}@if ($m->isOnline())<i class="mgr-online" title="Online now"></i>@endif</span>
        <div class="wl-main">
          <b>{{ $m->name }} <span class="muted wl-name">· {{ $m->email }}</span></b>
          <span class="wl-addr">
            Last seen <b>{{ $m->isOnline() ? 'online now' : ($m->last_seen_at?->diffForHumans() ?? 'never') }}</b>
            · Last login {{ $m->last_login_at?->format('d M Y, h:i A') ?? '—' }}
            · IP <span class="mono">{{ $m->last_login_ip ?? '—' }}</span>
            · {{ (int) ($reviews[$m->id] ?? 0) }} {{ \Illuminate\Support\Str::plural('review', (int) ($reviews[$m->id] ?? 0)) }}
          </span>
        </div>
        <span class="wl-status {{ $active ? 'ok' : 'off' }}"><i class="wl-dot"></i>{{ $active ? 'Active' : 'Inactive' }}</span>
        <form method="POST" action="{{ route('admin.managers.toggle', $m) }}" data-confirm="{{ $active ? 'Deactivate ' . $m->name . '? They will be logged out and cannot log in.' : 'Activate ' . $m->name . '? They can log in again.' }}">
          @csrf
          <button class="wl-btn {{ $active ? 'del' : 'edit' }}" title="{{ $active ? 'Deactivate' : 'Activate' }}" aria-label="{{ $active ? 'Deactivate' : 'Activate' }}">{!! $active ? $lock : $unlock !!}</button>
        </form>
        <button type="button" class="wl-btn edit" data-modal-open="pw{{ $m->id }}" title="Set a new password" aria-label="Set a new password">{!! $key !!}</button>
        <form method="POST" action="{{ route('admin.managers.destroy', $m) }}" data-confirm="Remove manager {{ $m->name }}? Their past reviews stay on the orders.">
          @csrf @method('DELETE')
          <button class="wl-btn del" title="Remove" aria-label="Remove manager">{!! $trash !!}</button>
        </form>
      </div>
    </div>

    {{-- New password pop-up --}}
    <div class="modal {{ $pwOpen == $m->id ? 'open' : '' }}" id="pw{{ $m->id }}" data-admin-modal aria-hidden="true">
      <form method="POST" action="{{ route('admin.managers.password', $m) }}" class="modal-box form wl-modal" role="dialog" aria-modal="true" aria-label="New password for {{ $m->name }}">
        @csrf @method('PUT')
        <input type="hidden" name="_modal" value="{{ $m->id }}">
        <h3>New password for {{ $m->name }}</h3>
        <input class="input" type="password" name="password" placeholder="New password (min 8, letters + numbers)" required autocomplete="new-password">
        <p class="addr-check">They'll be logged out and must use the new password.</p>
        <div class="wl-modal-actions">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
          <button class="btn btn-save">Save</button>
        </div>
      </form>
    </div>
  @empty
    <p class="muted" style="text-align:center;padding:20px 0">No managers yet — tap <b>+ Add manager</b>.</p>
  @endforelse
</div>
@endsection
