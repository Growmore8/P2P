@extends('layouts.admin')
@php $sideTitle = ['buy' => 'Buy Orders', 'sell' => 'Sell Orders'][request('side')] ?? 'All Orders'; @endphp
@section('title', $sideTitle)
@section('heading', $sideTitle)

@section('content')
@php $cur = request('status'); @endphp
<div class="tabs-line">
  <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}" class="{{ ! $cur ? 'active' : '' }}">All</a>
  @foreach (\App\Models\Order::LABELS as $k => $l)
    <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => $k])) }}" class="{{ $cur === $k ? 'active' : '' }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>
  @endforeach
</div>
<div class="box">
  <form class="searchbar" method="GET" style="margin-bottom:16px">
    @if ($cur)<input type="hidden" name="status" value="{{ $cur }}">@endif
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
