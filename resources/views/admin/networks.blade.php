@extends('layouts.admin')
@section('title', 'Networks & Wallets')
@section('heading', 'Networks & Wallets')

@section('content')
<div class="alert alert-info">For <b>sell</b> orders, customers send USDT to the deposit address you set here. A network without a deposit address cannot accept sell orders. The <b>fee</b> is deducted from USDT on <b>buy</b> orders.</div>

@foreach ($networks as $n)
  <form method="POST" action="{{ route('admin.networks.update', $n) }}" class="box form">
    @csrf @method('PUT')
    <div class="box-head" style="margin-bottom:4px">
      <h3>{{ $n->code }}</h3>
      <div class="actions">
        <label class="switch"><input type="checkbox" name="buy_enabled" value="1" @checked($n->buy_enabled)> Buy</label>
        <label class="switch"><input type="checkbox" name="sell_enabled" value="1" @checked($n->sell_enabled)> Sell</label>
      </div>
    </div>
    <div class="form-grid-3">
      <div><label>Display name</label><input class="input" name="name" value="{{ $n->name }}" required></div>
      <div><label>Fee on buy (USDT)</label><input class="input" type="number" step="0.01" name="fee" value="{{ $n->fee }}" required></div>
      <div><label>Sort</label><input class="input" type="number" name="sort" value="{{ $n->sort }}"></div>
    </div>
    <div class="form-grid">
      <div><label>Your USDT deposit address (for sell orders)</label><input class="input mono" name="deposit_address" value="{{ $n->deposit_address }}" placeholder="Not set — sell disabled"></div>
      <div><label>Address validation regex</label><input class="input mono" name="address_regex" value="{{ $n->address_regex }}"></div>
    </div>
    <div class="actions"><button class="btn btn-primary btn-sm">Save {{ $n->code }}</button></div>
  </form>
@endforeach

<form method="POST" action="{{ route('admin.networks.store') }}" class="box form">
  @csrf
  <h3>Add network</h3>
  <div class="form-grid-3">
    <div><label>Code</label><input class="input" name="code" placeholder="e.g. SOL" required></div>
    <div><label>Display name</label><input class="input" name="name" placeholder="Solana" required></div>
    <div><label>Fee on buy (USDT)</label><input class="input" type="number" step="0.01" name="fee" value="1" required></div>
  </div>
  <div class="form-grid">
    <div><label>Deposit address</label><input class="input mono" name="deposit_address"></div>
    <div><label>Address regex (optional)</label><input class="input mono" name="address_regex"></div>
  </div>
  <div class="actions">
    <label class="switch"><input type="checkbox" name="buy_enabled" value="1" checked> Buy</label>
    <label class="switch"><input type="checkbox" name="sell_enabled" value="1" checked> Sell</label>
    <button class="btn btn-primary btn-sm">Add network</button>
  </div>
</form>
@endsection
