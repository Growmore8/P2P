@extends('layouts.admin')
@section('title', 'How clients pay you')
@section('heading', 'How clients pay you')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-modal-open="addModal">+ Add a way to pay</button>@parent @endsection

@section('content')
<p class="muted" style="margin:-4px 0 14px"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · The accounts clients send money to when they <b>buy</b> USDT.</p>
<div class="alert alert-info"><b>Deposit methods</b> are where clients PAY when they <b>buy</b> USDT. Each method follows its currency — INR methods are shown to Indian clients, LKR methods to Sri Lankan clients, and so on. Details are copied into each order, so editing a method doesn't change past orders.</div>
<div class="box">
  <div class="table-wrap"><table>
    <thead><tr><th>Method</th><th>Currency</th><th>Details</th><th>Limits</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($methods as $m)
      <tr>
        <td><b>{{ $m->title }}</b><span class="sub">{{ \App\Models\DepositMethod::TYPES[$m->type] ?? $m->type }}</span></td>
        <td><b class="mono">{{ $m->currency }}</b><span class="sub">{{ $m->countriesLabel() }}</span></td>
        <td class="mono">
          @if ($m->type === 'upi') {{ $m->upi_id }} @if ($m->qr_image)<span class="badge info">QR</span>@endif
          @elseif ($m->type === 'link')
            @if ($m->pay_url)<a href="{{ $m->pay_url }}" target="_blank" rel="noopener" class="green method-link">{{ preg_replace('#^https?://#', '', $m->pay_url) }} ↗</a>@else<span class="muted">No link set</span>@endif
          @elseif ($m->type === 'other')
            <span class="sub" style="font-family:inherit">{{ \Illuminate\Support\Str::limit($m->instructions, 80) ?: 'No instructions' }}</span>
          @else {{ $m->account_holder }}<span class="sub">{{ collect([$m->bank_name, $m->account_number, $m->ifsc])->filter()->join(' · ') }}</span>@endif
        </td>
        <td>{{ money($m->min_amount, $m->currency, 0) }} – {{ (float) $m->max_amount > 0 ? money($m->max_amount, $m->currency, 0) : 'no limit' }}</td>
        <td>
          <form method="POST" action="{{ route('admin.methods.toggle', $m) }}" class="inline">@csrf
            <button class="badge {{ $m->is_active ? 'ok' : 'bad' }}" style="border:none;cursor:pointer">{{ $m->is_active ? 'Active' : 'Disabled' }}</button>
          </form>
        </td>
        <td class="actions">
          <a href="{{ route('admin.methods.edit', $m) }}" class="btn btn-ghost btn-sm">Edit</a>
          <form method="POST" action="{{ route('admin.methods.destroy', $m) }}" data-confirm="Delete this method?">@csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">Delete</button></form>
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">No deposit methods yet.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>

{{-- "+ Add a way to pay" pop-up --}}
@php $addOpen = $errors->any() && old('_modal') === 'add'; @endphp
<div class="modal {{ $addOpen ? 'open' : '' }}" id="addModal" data-admin-modal aria-hidden="{{ $addOpen ? 'false' : 'true' }}">
  <div class="modal-box modal-form" role="dialog" aria-modal="true" aria-label="Add a way clients pay you">
    <div class="mf-head"><h3>Add a way clients pay you</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">×</button></div>
    <p class="muted" style="font-size:13px;margin:-6px 0 12px">Your account where clients send money when they buy USDT.</p>
    @include('admin.methods._form', ['method' => new \App\Models\DepositMethod(['type' => 'upi', 'currency' => 'INR', 'is_active' => true]), 'modal' => true])
  </div>
</div>
@endsection
