@extends('layouts.admin')
@php $sideTitle = ['buy' => 'Buy Orders', 'sell' => 'Sell Orders'][request('side')] ?? 'All Orders'; @endphp
@section('title', $sideTitle)
@section('heading', $sideTitle)

@section('content')
@php $cur = request('status'); $rv = request('review'); @endphp
<div class="tabs-line">
  <a href="{{ route('admin.orders.index', array_merge(request()->except('status', 'review', 'page'), ['review' => 'waiting'])) }}" class="{{ $rv === 'waiting' ? 'active' : '' }}">⏳ Waiting for review ({{ $counts['review_waiting'] ?? 0 }})</a>
  <a href="{{ route('admin.orders.index', array_merge(request()->except('status', 'review', 'page'), ['review' => 'reviewed'])) }}" class="{{ $rv === 'reviewed' ? 'active' : '' }}">✓ Reviewed — ready to approve ({{ $counts['review_done'] ?? 0 }})</a>
  <a href="{{ route('admin.orders.index', request()->except('status', 'review', 'state', 'page')) }}" class="{{ ! $cur && ! $rv && request('state') !== 'deleted' ? 'active' : '' }}">All</a>
  @foreach (\App\Models\Order::LABELS as $k => $l)
    <a href="{{ route('admin.orders.index', array_merge(request()->except('page', 'review'), ['status' => $k])) }}" class="{{ $cur === $k ? 'active' : '' }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>
  @endforeach
  @if (auth()->user()->is_admin)<a href="{{ route('admin.orders.index', ['state' => 'deleted']) }}" class="{{ request('state') === 'deleted' ? 'active' : '' }}">🗑 Deleted ({{ $counts['deleted'] ?? 0 }})</a>@endif
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    @if ($cur)<input type="hidden" name="status" value="{{ $cur }}">@endif
    @if ($rv)<input type="hidden" name="review" value="{{ $rv }}">@endif
    @if (request('state'))<input type="hidden" name="state" value="{{ request('state') }}">@endif
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Order no, UTR, wallet, name, phone…">
    <select name="side" class="input" style="max-width:140px">
      <option value="">Buy & Sell</option>
      <option value="buy" @selected(request('side') === 'buy')>Buy</option>
      <option value="sell" @selected(request('side') === 'sell')>Sell</option>
    </select>
    <select name="currency" class="input" style="max-width:140px">
      <option value="">All currencies</option>
      @foreach (\App\Models\Currency::orderBy('sort')->pluck('code') as $code)<option @selected(request('currency') === $code)>{{ $code }}</option>@endforeach
    </select>
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
  @include('partials.order-rows', ['orders' => $orders, 'admin' => true])
  {{ $orders->links() }}
</div>
@endsection
