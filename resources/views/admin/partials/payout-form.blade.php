<form method="POST" action="{{ $m->exists ? route('admin.payout-methods.update', $m) : route('admin.payout-methods.store') }}" class="form">
  @csrf
  @if ($m->exists) @method('PUT') @endif
  <div class="form-grid-3">
    <div><label>Currency</label>
      <select class="input" name="currency" required>
        @foreach (\App\Models\Currency::orderBy('sort')->get() as $c)<option value="{{ $c->code }}" @selected($m->currency === $c->code)>{{ $c->code }} — {{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div><label>Type</label>
      <select class="input" name="type" required>
        @foreach (\App\Models\PayoutMethod::TYPES as $k => $l)<option value="{{ $k }}" @selected($m->type === $k)>{{ $l }}</option>@endforeach
      </select>
    </div>
    <div><label>Title shown to clients</label><input class="input" name="title" value="{{ $m->title }}" placeholder="e.g. Bank transfer (CEFTS)" required></div>
  </div>
  <div class="form-grid">
    <div><label>Short note (optional)</label><input class="input" name="instructions" value="{{ $m->instructions }}" placeholder="e.g. Account must be in your own name"></div>
    <div style="display:flex;gap:16px;align-items:end"><div style="flex:1"><label>Sort</label><input class="input" type="number" name="sort" value="{{ $m->sort ?? 0 }}"></div>
      <label class="switch" style="padding-bottom:12px"><input type="checkbox" name="is_active" value="1" @checked($m->is_active)> Active</label></div>
  </div>
  @include('partials.admin-countries', ['selected' => $m->countries, 'currency' => $m->currency])
  <button class="btn btn-primary btn-sm">{{ $m->exists ? 'Save' : 'Add receive method' }}</button>
</form>
