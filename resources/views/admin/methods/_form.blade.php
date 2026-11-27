{{-- Deposit method form. Used on its own page (edit / pre-filled add) and in the "+ Add a way to pay" pop-up ($modal = true). --}}
@php $cur = old('currency', $method->currency ?? 'INR'); @endphp
<form method="POST" enctype="multipart/form-data" class="{{ ($modal ?? false) ? 'form' : 'box form' }}" @unless ($modal ?? false) style="margin-top:14px;max-width:860px" @endunless
      action="{{ $method->exists ? route('admin.methods.update', $method) : route('admin.methods.store') }}">
  @csrf
  @if ($method->exists) @method('PUT') @endif
  @if ($modal ?? false)<input type="hidden" name="_modal" value="add">@endif
  @php
    $typeCards = ['upi' => ['📱', 'UPI', 'GPay, PhonePe, Paytm — QR made automatically'], 'bank' => ['🏦', 'Bank transfer', 'Account number + IFSC / SWIFT'], 'wallet' => ['👛', 'Mobile wallet', 'eZ Cash, M-Pesa, bKash…'], 'link' => ['🔗', 'Payment link', 'Razorpay.me or similar link'], 'other' => ['✏️', 'Other', 'Write your own instructions']];
    $curType = old('type', $method->type ?: 'upi');
  @endphp
  <div class="mf-grid">
    <div><label>Currency</label>
      <select name="currency" class="input" required>
        @foreach (\App\Models\Currency::orderBy('sort')->get() as $c)<option value="{{ $c->code }}" @selected($cur === $c->code)>{{ $c->code }} — {{ $c->name }}{{ $c->is_active ? '' : ' (inactive)' }}</option>@endforeach
      </select>
    </div>
    <div class="span-2"><label>Display title</label><input class="input" name="title" value="{{ old('title', $method->title) }}" placeholder="e.g. UPI (GPay / PhonePe) · Bank transfer (CEFTS) · eZ Cash" required></div>
  </div>

  <label class="mf-label">How will clients pay?</label>
  <select name="type" class="input" data-type-toggle data-type-select style="display:none">
    @foreach (\App\Models\DepositMethod::TYPES as $k => $l)<option value="{{ $k }}" @selected($curType === $k)>{{ $l }}</option>@endforeach
  </select>
  <div class="type-cards">
    @foreach ($typeCards as $k => [$ico, $l, $d])
      <button type="button" class="type-card {{ $curType === $k ? 'on' : '' }}" data-pick-type="{{ $k }}" title="{{ $d }}"><span class="tc-ico">{{ $ico }}</span><b>{{ $l }}</b><small>{{ $d }}</small></button>
    @endforeach
  </div>
  @unless ($method->exists)@include('admin.partials.method-presets', ['list' => 'pay'])@endunless

  <div class="mf-grid two">
    <div data-type="link" class="span-2"><label>Payment link (https://…)</label><input class="input" name="pay_url" value="{{ old('pay_url', $method->pay_url) }}" placeholder="https://razorpay.me/@yourbusiness">
      <span class="hint">Clients pay the exact amount on this link, then paste the payment ID (pay_…).</span></div>
    <div data-type="upi"><label>UPI ID</label><input class="input" name="upi_id" value="{{ old('upi_id', $method->upi_id) }}" placeholder="business@okbank"></div>
    <div data-type="upi wallet"><label>QR code image <small>optional</small></label><input class="input" type="file" name="qr" accept="image/*">
      @if ($method->qr_image)<img src="{{ route('files.qr', $method) }}" alt="QR" style="width:90px;margin-top:6px;border-radius:8px;background:#fff;padding:5px">@endif
    </div>
    <div data-type="bank wallet"><label>Account / wallet holder</label><input class="input" name="account_holder" value="{{ old('account_holder', $method->account_holder) }}"></div>
    <div data-type="bank wallet"><label><span data-type="bank">Bank name</span><span data-type="wallet">Wallet provider</span></label><input class="input" name="bank_name" value="{{ old('bank_name', $method->bank_name) }}"></div>
    <div data-type="bank wallet"><label><span data-type="bank">Account number / IBAN</span><span data-type="wallet">Wallet number</span></label><input class="input" name="account_number" value="{{ old('account_number', $method->account_number) }}"></div>
    <div data-type="other" class="span-2"><label>How should clients pay? <small>shown on the order</small></label><textarea name="instructions" class="input" rows="2" placeholder="e.g. Pay at our office — details below">{{ old('instructions', $method->instructions) }}</textarea></div>
    <div data-type="bank"><label>IFSC / SWIFT / branch code</label><input class="input" name="ifsc" value="{{ old('ifsc', $method->ifsc) }}" style="text-transform:uppercase"></div>
  </div>

  <div class="mf-grid">
    <div><label>Min amount <small>0 = none</small></label><input class="input" type="number" step="0.01" name="min_amount" value="{{ old('min_amount', $method->min_amount ?? 0) }}"></div>
    <div><label>Max amount <small>0 = no limit</small></label><input class="input" type="number" step="0.01" name="max_amount" value="{{ old('max_amount', $method->max_amount ?? 0) }}"></div>
    <div class="mf-switch"><label class="switch cur-switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $method->is_active))> Active</label></div>
  </div>


  <div class="mf-foot">
    @if ($modal ?? false)<button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>@endif
    <button class="btn btn-primary">Save</button>
  </div>
</form>
<script>
document.querySelectorAll('[data-pick-type]').forEach(function (b) {
  b.addEventListener('click', function () {
    var sel = document.querySelector('[data-type-select]');
    sel.value = b.dataset.pickType;
    sel.dispatchEvent(new Event('change'));
    document.querySelectorAll('[data-pick-type]').forEach(function (x) { x.classList.toggle('on', x === b); });
  });
});
</script>
