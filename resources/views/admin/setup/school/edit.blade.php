@extends('layouts.admin')
@section('title', 'School settings')
@section('content')
    @include('admin.partials.page-header', ['title' => 'School settings', 'crumbs' => ['Setup', 'School settings']])

    <form method="POST" action="{{ route('admin.school.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">School Information</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label">Name <span
                                        class="text-danger">*</span></label>
                                <input name="name" class="form-control" value="{{ old('name', $school->name) }}" required>
                            </div>
                            <div class="col-md-4"><label class="form-label">Established</label>
                                <input type="number" name="established" class="form-control" min="1800"
                                    max="{{ date('Y') }}"
                                    value="{{ old('established', optional($school->established)->format('Y')) }}"
                                    placeholder="e.g. 1942">
                            </div>
                            <div class="col-md-12"><label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $school->email) }}">
                            </div>
                            <div class="col-12"><label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control"
                                    value="{{ old('address', $school->address) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">School Codes</div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Up to three institution codes with custom labels (e.g. EIIN, School
                            code, Technical branch code). Leave a code blank to hide it from the site header.</p>
                        <div class="row g-3">
                            <div class="col-md-5"><label class="form-label">Field 1 Label</label>
                                <input name="institution_code_label" class="form-control"
                                    value="{{ old('institution_code_label', $school->institution_code_label) }}"
                                    placeholder="e.g. EIIN">
                            </div>
                            <div class="col-md-7"><label class="form-label">Field 1 Code</label>
                                <input name="institution_code" class="form-control"
                                    value="{{ old('institution_code', $school->institution_code) }}">
                            </div>
                            <div class="col-md-5"><label class="form-label">Field 2 Label</label>
                                <input name="school_code_label" class="form-control"
                                    value="{{ old('school_code_label', $school->school_code_label) }}"
                                    placeholder="e.g. School code">
                            </div>
                            <div class="col-md-7"><label class="form-label">Field 2 Code</label>
                                <input name="school_code" class="form-control"
                                    value="{{ old('school_code', $school->school_code) }}">
                            </div>
                            <div class="col-md-5"><label class="form-label">Field 3 Label</label>
                                <input name="technical_branch_code_label" class="form-control"
                                    value="{{ old('technical_branch_code_label', $school->technical_branch_code_label) }}"
                                    placeholder="e.g. Technical branch code">
                            </div>
                            <div class="col-md-7"><label class="form-label">Field 3 Code</label>
                                <input name="technical_branch_code" class="form-control"
                                    value="{{ old('technical_branch_code', $school->technical_branch_code) }}">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-5">

                <div class="card mb-4">
                    <div class="card-header">Locale</div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Country</label>
                            <select name="country_code" class="form-select js-select">
                                <option value="">— Select country —</option>
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}" @selected(old('country_code', $school->country_code) === $code)>
                                        {{ $name }} ({{ $code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency" class="form-select js-select" required>
                                @foreach ($currencies as $code => $name)
                                    <option value="{{ $code }}" @selected(old('currency', $school->currency) === $code)>
                                        {{ $code }} — {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Timezone <span class="text-danger">*</span></label>
                            <select name="timezone" class="form-select js-select" required>
                                @foreach ($timezones as $tz)
                                    <option value="{{ $tz }}" @selected(old('timezone', $school->timezone) === $tz)>{{ $tz }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Language <span class="text-danger">*</span></label>
                            <select name="locale" class="form-select js-select" required>
                                @foreach ($languages as $code => $name)
                                    <option value="{{ $code }}" @selected(old('locale', $school->locale) === $code)>{{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="form-label">Academic year pattern <span class="text-danger">*</span></label>
                            <select name="academic_year_pattern" class="form-select" required>
                                @foreach ($patterns as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('academic_year_pattern', $school->academic_year_pattern) === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Mobile Numbers</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addPhone"><i
                                class="bi bi-plus-lg"></i> Add</button>
                    </div>
                    <div class="card-body">
                        <div id="phoneRows" class="vstack gap-2">
                            @php $phones = old('phones', $school->phones->map(fn($p) => ['phone' => $p->phone, 'is_primary' => $p->is_primary, 'show_in_header' => $p->show_in_header])->all()); @endphp
                            @forelse ($phones as $i => $p)
                                <div class="input-group phone-row">
                                    <input name="phones[{{ $i }}][phone]" class="form-control" placeholder="Phone"
                                        value="{{ $p['phone'] ?? '' }}">
                                    <input type="hidden" name="phones[{{ $i }}][show_in_header]" value="0">
                                    <span class="input-group-text">
                                        <input class="form-check-input mt-0 me-1" type="checkbox"
                                            name="phones[{{ $i }}][show_in_header]" value="1" id="hdr{{ $i }}" {{ ($p['show_in_header'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="hdr{{ $i }}">Header</label>
                                    </span>
                                    <button type="button" class="btn btn-outline-danger rm-phone"><i
                                            class="bi bi-trash"></i></button>
                                </div>
                            @empty
                            @endforelse
                        </div>
                        <div class="form-text mt-2">Tick <strong>Top Bar</strong> to show a number
                            (clickable, tel:) in the site header's top bar.</div>
                    </div>
                </div>

            </div>
        </div>

        @php
            $logoUrl = \App\Support\Media::url($school->logo);
            $faviconUrl = \App\Support\Media::url($settings->favicon);
            $ogUrl = \App\Support\Media::url($settings->og_image);
        @endphp
        <div class="row g-4 mt-0">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Branding &amp; Appearance</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Logo</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="border rounded d-inline-flex align-items-center justify-content-center bg-light flex-shrink-0"
                                        style="width:48px;height:48px;overflow:hidden;">
                                        @if($logoUrl)<img src="{{ $logoUrl }}" alt="logo"
                                        style="max-width:100%;max-height:100%;">@else<i
                                            class="bi bi-image text-muted"></i>@endif
                                    </span>
                                    <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="form-text">PNG recommended, 512×512.</div>
                            </div>
                            <div class="col-md-6"><label class="form-label">Favicon</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="border rounded d-inline-flex align-items-center justify-content-center bg-light flex-shrink-0"
                                        style="width:48px;height:48px;overflow:hidden;">
                                        @if($faviconUrl)<img src="{{ $faviconUrl }}" alt="favicon"
                                        style="max-width:100%;max-height:100%;">@else<i
                                            class="bi bi-star text-muted"></i>@endif
                                    </span>
                                    <input type="file" name="favicon" accept="image/png,image/x-icon"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="form-text">PNG recommended, 512×512.</div>
                            </div>

                            <div class="col-sm-6"><label class="form-label">Primary Color</label>
                                <input type="color" name="primary_color" class="form-control form-control-color w-100"
                                    value="{{ old('primary_color', $settings->primary_color ?: '#1d4ed8') }}">
                            </div>
                            <div class="col-sm-6"><label class="form-label">Accent Color</label>
                                <input type="color" name="accent_color" class="form-control form-control-color w-100"
                                    value="{{ old('accent_color', $settings->accent_color ?: '#f59e0b') }}">
                            </div>
                            <div class="col-sm-6"><label class="form-label">Heading Color</label>
                                <input type="color" name="heading_color" class="form-control form-control-color w-100"
                                    value="{{ old('heading_color', $settings->heading_color ?: '#0f172a') }}">
                            </div>
                            <div class="col-md-6"><label class="form-label">Topbar Text Color</label>
                                <input type="color" name="topbar_text_color" class="form-control form-control-color w-100"
                                    value="{{ old('topbar_text_color', $settings->topbar_text_color ?: '#ffffff') }}">
                            </div>

                            <div class="col-md-6"><label class="form-label">Announcement ticker</label>
                                <select name="ticker_position" class="form-select">
                                    @foreach (['below_nav' => 'Show below the menu bar', 'above_nav' => 'Show above the menu bar', 'hidden' => 'Hidden'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected(old('ticker_position', $settings->ticker_position ?? 'below_nav') === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <div class="form-text mt-2">Primary color drives the top bar, brand text, nav, and buttons. The
                            ticker pauses on hover and hides automatically when there are no active notices. Header phone
                            numbers are set on the Mobile Numbers list above (tick “Header”).</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">SEO &amp; social share</div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Meta title</label>
                            <input name="meta_title" class="form-control"
                                value="{{ old('meta_title', $settings->meta_title) }}"
                                placeholder="Defaults to the site name">
                        </div>
                        <div class="mb-3"><label class="form-label">Meta description</label>
                            <textarea name="meta_description" rows="2" class="form-control"
                                placeholder="Short description for search engines">{{ old('meta_description', $settings->meta_description) }}</textarea>
                        </div>
                        <div class="mb-0"><label class="form-label">Social share image</label>
                            @if($ogUrl)
                                <div class="mb-1"><img src="{{ $ogUrl }}" alt="share image" class="img-fluid rounded"
                            style="max-height:90px;"></div>@endif
                            <input type="file" name="og_image" accept="image/*" class="form-control form-control-sm">
                            <div class="form-text">Featured image shown when the site is shared on social media (1200×630
                                recommended).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save"></i> Save settings</button></div>
    </form>

    @php $hours = $school->openingHours->keyBy('day_of_week');
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; @endphp
    <form method="POST" action="{{ route('admin.school.hours') }}" class="mt-4">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header">Opening hours <span class="text-muted small">(drives attendance working days)</span>
            </div>
            <div class="card-body">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th style="width:120px">Open</th>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayNames as $dow => $name)
                            @php $h = $hours[$dow] ?? null; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $name }}</td>
                                <td>
                                    <div class="form-check form-switch mb-0"><input type="hidden"
                                            name="days[{{ $dow }}][is_open]" value="0"><input class="form-check-input"
                                            type="checkbox" name="days[{{ $dow }}][is_open]" value="1" @checked($h ? $h->is_open : true)></div>
                                </td>
                                <td><input type="time" name="days[{{ $dow }}][open_time]" class="form-control form-control-sm"
                                        value="{{ $h && $h->open_time ? \Illuminate\Support\Str::of($h->open_time)->substr(0, 5) : '' }}">
                                </td>
                                <td><input type="time" name="days[{{ $dow }}][close_time]" class="form-control form-control-sm"
                                        value="{{ $h && $h->close_time ? \Illuminate\Support\Str::of($h->close_time)->substr(0, 5) : '' }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="text-end mt-2"><button class="btn btn-primary"><i class="bi bi-save"></i> Save hours</button>
                </div>
            </div>
        </div>
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
                        '<input type="hidden" name="phones[' + idx + '][show_in_header]" value="0">' +
                        '<span class="input-group-text"><input class="form-check-input mt-0 me-1" type="checkbox" name="phones[' + idx + '][show_in_header]" value="1"><label class="form-check-label small">Header</label></span>' +
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
