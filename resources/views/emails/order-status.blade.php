@extends('emails.layout')
@php
  $buy = $o->isBuy();
  $done = $o->status === \App\Models\Order::COMPLETED;
@endphp
@section('title', 'Order ' . $o->order_no)
@section('preheader', $done ? 'Your order ' . $o->order_no . ' is complete.' : 'Your order ' . $o->order_no . ' was not completed.')

@section('content')
  <p style="margin:0 0 6px;font-size:13px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:{{ $done ? '#16a34a' : '#b91c1c' }}">{{ $done ? 'Order completed' : 'Order rejected' }}</p>
  <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;font-weight:800;color:#0d1424">
    @if ($done)
      {{ $buy ? number_format((float) $o->usdt_amount, 2) . ' USDT sent to your wallet' : $o->money($o->payout_fiat) . ' sent to your account' }}
    @else
      Your order could not be completed
    @endif
  </h1>
  <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#5b6577">Hi {{ $o->user->name }},
    @if ($done)
      your {{ $buy ? 'buy' : 'sell' }} order <b style="color:#0d1424">{{ $o->order_no }}</b> is complete.
    @else
      your {{ $buy ? 'buy' : 'sell' }} order <b style="color:#0d1424">{{ $o->order_no }}</b> was rejected.
    @endif
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f6f8fc" style="background:#f6f8fc;border-radius:12px;margin:0 0 18px">
    <tr><td style="padding:14px 16px;font-size:14px;line-height:1.8;color:#0d1424">
      <b>Order:</b> {{ $o->order_no }}<br>
      <b>{{ $buy ? 'You paid' : 'You sold' }}:</b> {{ $buy ? $o->money($o->fiat_amount) : number_format((float) $o->usdt_amount, 2) . ' USDT' }}<br>
      <b>{{ $buy ? 'You receive' : 'You receive' }}:</b> {{ $buy ? number_format((float) $o->usdt_amount, 2) . ' USDT (' . $o->network . ')' : $o->money($o->payout_fiat) }}<br>
      @if ($done && $o->admin_reference)<b>{{ $buy ? 'Tx hash' : 'Payout ref.' }}:</b> <span style="font-family:monospace;word-break:break-all">{{ $o->admin_reference }}</span><br>@endif
      @if (! $done && $o->reject_reason)<b>Reason:</b> {{ $o->reject_reason }}<br>@endif
    </td></tr>
  </table>

  @if (! $done && $o->paid_at)
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#5b6577">If you have already paid, our team will contact you about a refund to the account you paid from. You can also reply in the order chat or raise an appeal.</p>
  @endif

  <p style="text-align:center;margin:22px 0 26px">
    <a href="{{ $url }}" style="display:inline-block;background:#1f56e0;color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:10px">View order</a>
  </p>
@endsection
