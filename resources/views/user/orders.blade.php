@extends('layouts.app')
@section('title', 'Order History')
@section('back', route('account'))
@section('heading', 'Order History')

@section('content')
@php $cur = request('status'); @endphp
<div class="tabs-line" style="flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px">
  <a href="{{ route('orders.index') }}" class="{{ ! $cur ? 'active' : '' }}">All</a>
  @foreach (['pending_payment' => 'Pending', 'payment_submitted' => 'Verifying', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $k => $l)
    <a href="{{ route('orders.index', ['status' => $k]) }}" class="{{ $cur === $k ? 'active' : '' }}" style="white-space:nowrap">{{ $l }}</a>
  @endforeach
</div>

@forelse ($orders as $o)
  <a href="{{ route('orders.show', $o) }}" class="lrow">
    <span class="pr-ico" style="width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:var(--card);font-weight:800;color:{{ $o->isBuy() ? 'var(--brand)' : 'var(--red)' }}">{{ $o->isBuy() ? '⇣' : '⇡' }}</span>
    <div class="lmain">
      <b>{{ $o->isBuy() ? 'Bought' : 'Sold' }} {{ number_format($o->usdt_amount, 2) }} USDT</b>
      <small>{{ $o->created_at->format('d M, h:i A') }} · {{ $o->network }}</small>
    </div>
    <div class="lend">{{ $o->money($o->clientFiat()) }}
      <small><span class="badge {{ $o->statusClass() }}">{{ $o->statusLabel() }}</span></small>
    </div>
  </a>
@empty
  <div class="empty-card">
    <div class="hero-icon">🕘</div>
    <b>No orders yet</b>
    <p>Your buy and sell orders will appear here.</p>
  </div>
  <div class="stick-bottom"><a href="{{ route('trade') }}" class="btn btn-primary btn-block btn-lg">Buy USDT</a></div>
@endforelse

{{ $orders->links() }}
@endsection
