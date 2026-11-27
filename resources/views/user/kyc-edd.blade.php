@extends('layouts.app')
@section('title', 'Enhanced Due Diligence')
@section('back', route('account.limits'))
@section('heading', 'EDD verification')

@section('content')
@if ($rejected)
  <div class="alert alert-error">Your previous submission was rejected: {{ $rejected->reject_reason }}</div>
@endif
<p class="muted" style="font-size:14px;margin-bottom:14px">For higher limits we need to understand your source of funds. This is required by anti-money-laundering rules and is kept strictly confidential.</p>

<form method="POST" action="{{ route('kyc.edd.store') }}" enctype="multipart/form-data" class="form">
  @csrf
  <div class="box">
    <h3 style="margin-bottom:12px">1. About you</h3>
    <div class="form"><div><label>Occupation</label><input class="input" name="occupation" value="{{ old('occupation') }}" placeholder="e.g. Business owner, Software engineer" required></div>
    <div><label>Employer / business name (optional)</label><input class="input" name="employer" value="{{ old('employer') }}"></div>
    <div><label>Annual income ({{ $currency }})</label>
      <select class="input" name="annual_income" required>
        @foreach (['<5L' => 'Below 5 lakh', '5-10L' => '5 – 10 lakh', '10-25L' => '10 – 25 lakh', '25-50L' => '25 – 50 lakh', '50L+' => 'Above 50 lakh'] as $k => $l)
          <option value="{{ $k }}" @selected(old('annual_income') === $k)>{{ $l }}</option>
        @endforeach
      </select><span class="hint">Approximate, in lakh (100,000) of your local currency.</span></div></div>
  </div>

  <div class="box">
    <h3 style="margin-bottom:12px">2. Source of funds</h3>
    <div class="form"><div><label>Main source of the money you trade with</label>
      <select class="input" name="source_of_funds" required>
        @foreach (['Salary', 'Business income', 'Investments / trading', 'Savings', 'Other'] as $o)<option @selected(old('source_of_funds') === $o)>{{ $o }}</option>@endforeach
      </select></div>
    <div><label>Details (optional)</label><textarea class="input" name="source_details" rows="3" maxlength="500" placeholder="e.g. monthly salary from ABC Pvt Ltd">{{ old('source_details') }}</textarea></div>
    <div><label>Purpose of trading</label>
      <select class="input" name="purpose" required>
        @foreach (['Personal investment', 'Business payments', 'Family remittance', 'Other'] as $o)<option @selected(old('purpose') === $o)>{{ $o }}</option>@endforeach
      </select></div></div>
  </div>

  <div class="box">
    <h3 style="margin-bottom:12px">3. Documents</h3>
    <div class="form">
      @foreach (\App\Models\KycUpgrade::EDD_FILES as $key => $label)
        <div><label>{{ $label }}{{ $key === 'extra_doc' ? ' (optional)' : '' }}</label>
          <label class="o-upload"><input type="file" name="{{ $key }}" accept=".pdf,image/*" @required($key !== 'extra_doc') data-upload>
            <span class="o-upload-ico">⬆</span><span data-upload-label>Tap to upload <small>PDF or image · max 10 MB</small></span></label></div>
      @endforeach
    </div>
  </div>

  <label class="check"><input type="checkbox" name="declaration" value="1" required> I declare that the information and documents are true, the funds are from legitimate sources, and I consent to SRM Fintech Solutions verifying them.</label>
  <button class="btn btn-primary btn-block btn-lg">Submit for review</button>
</form>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-upload]').forEach(function (up) {
    up.addEventListener('change', function () {
      var l = up.closest('.o-upload').querySelector('[data-upload-label]');
      if (up.files[0]) { l.innerHTML = '✓ ' + up.files[0].name.replace(/[<>&"]/g, '') + ' <small>Tap to change</small>'; up.closest('.o-upload').classList.add('has-file'); }
    });
  });
</script>
@endpush
