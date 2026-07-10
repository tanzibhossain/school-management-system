@extends('layouts.admin')
@section('title', 'School settings')
@section('content')
  @include('admin.partials.page-header', ['title' => 'School settings', 'crumbs' => ['Setup', 'School settings']])

  <form method="POST" action="{{ route('admin.school.update') }}">
    @csrf @method('PUT')
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card mb-4"><div class="card-header">Profile</div><div class="card-body">
          <div class="row g-3">
            <div class="col-md-8"><label class="form-label">School name <span class="text-danger">*</span></label>
              <input name="name" class="form-control" value="{{ old('name', $school->name) }}" required></div>
            <div class="col-md-4"><label class="form-label">Established</label>
              <input type="date" name="established" class="form-control" value="{{ old('established', optional($school->established)->format('Y-m-d')) }}"></div>
            <div class="col-md-6"><label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="{{ old('email', $school->email) }}"></div>
            <div class="col-md-3"><label class="form-label">Code label</label>
              <input name="institution_code_label" class="form-control" value="{{ old('institution_code_label', $school->institution_code_label) }}" placeholder="e.g. EIIN"></div>
            <div class="col-md-3"><label class="form-label">Institution code</label>
              <input name="institution_code" class="form-control" value="{{ old('institution_code', $school->institution_code) }}"></div>
            <div class="col-12"><label class="form-label">Address</label>
              <textarea name="address" rows="2" class="form-control">{{ old('address', $school->address) }}</textarea></div>
          </div>
        </div></div>

        <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
          <span>Phone numbers</span>
          <button type="button" class="btn btn-sm btn-outline-primary" id="addPhone"><i class="bi bi-plus-lg"></i> Add</button>
        </div><div class="card-body">
          <div id="phoneRows" class="vstack gap-2">
            @php $phones = old('phones', $school->phones->map(fn($p)=>['phone'=>$p->phone,'label'=>$p->label,'is_primary'=>$p->is_primary])->all()); @endphp
            @forelse ($phones as $i => $p)
              <div class="input-group phone-row">
                <span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="primary_phone" value="{{ $i }}" title="Primary" {{ ($p['is_primary'] ?? false) ? 'checked' : '' }}></span>
                <input name="phones[{{ $i }}][phone]" class="form-control" placeholder="Phone" value="{{ $p['phone'] ?? '' }}">
                <input name="phones[{{ $i }}][label]" class="form-control" placeholder="Label (e.g. Office)" value="{{ $p['label'] ?? '' }}">
                <button type="button" class="btn btn-outline-danger rm-phone"><i class="bi bi-trash"></i></button>
              </div>
            @empty
            @endforelse
          </div>
          <div class="form-text mt-2">Select the radio to mark the primary number. Empty rows are ignored.</div>
        </div></div>
      </div>

      <div class="col-lg-5">
        <div class="card"><div class="card-header">Locale</div><div class="card-body">
          <div class="mb-3"><label class="form-label">Country code (ISO-2)</label>
            <input name="country_code" maxlength="2" class="form-control text-uppercase" value="{{ old('country_code', $school->country_code) }}" placeholder="e.g. BD"></div>
          <div class="mb-3"><label class="form-label">Currency (ISO-3) <span class="text-danger">*</span></label>
            <input name="currency" maxlength="3" class="form-control text-uppercase" value="{{ old('currency', $school->currency) }}" placeholder="e.g. BDT" required></div>
          <div class="mb-3"><label class="form-label">Timezone <span class="text-danger">*</span></label>
            <select name="timezone" class="form-select js-select" required>
              @foreach ($timezones as $tz)
                <option value="{{ $tz }}" @selected(old('timezone', $school->timezone) === $tz)>{{ $tz }}</option>
              @endforeach
            </select></div>
          <div class="mb-3"><label class="form-label">Locale <span class="text-danger">*</span></label>
            <input name="locale" class="form-control" value="{{ old('locale', $school->locale) }}" placeholder="e.g. en" required></div>
          <div><label class="form-label">Academic year pattern <span class="text-danger">*</span></label>
            <select name="academic_year_pattern" class="form-select" required>
              @foreach ($patterns as $val => $lbl)
                <option value="{{ $val }}" @selected(old('academic_year_pattern', $school->academic_year_pattern) === $val)>{{ $lbl }}</option>
              @endforeach
            </select></div>
        </div></div>
      </div>
    </div>

    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save"></i> Save settings</button></div>
  </form>

  @push('scripts')
    <script>
      (function () {
        var wrap = document.getElementById('phoneRows');
        var idx = {{ count($phones) }};
        document.getElementById('addPhone').addEventListener('click', function () {
          var row = document.createElement('div');
          row.className = 'input-group phone-row';
          row.innerHTML =
            '<span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="primary_phone" value="' + idx + '" title="Primary"></span>' +
            '<input name="phones[' + idx + '][phone]" class="form-control" placeholder="Phone">' +
            '<input name="phones[' + idx + '][label]" class="form-control" placeholder="Label (e.g. Office)">' +
            '<button type="button" class="btn btn-outline-danger rm-phone"><i class="bi bi-trash"></i></button>';
          wrap.appendChild(row); idx++;
        });
        wrap.addEventListener('click', function (e) {
          var btn = e.target.closest('.rm-phone'); if (btn) btn.closest('.phone-row').remove();
        });
      })();
    </script>
  @endpush
@endsection
