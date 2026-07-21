@extends('layouts.admin')
@section('title', __('Languages'))
@section('content')
  @include('admin.partials.page-header', ['title' => __('Languages'), 'crumbs' => [__('Settings'), __('Languages')]])

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>{{ __('Site Languages') }}</span>
          <form method="POST" action="{{ route('admin.languages.scan') }}">
            @csrf
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> {{ __('Scan For New Strings') }}</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr>
              <th>{{ __('Language') }}</th><th>{{ __('Code') }}</th><th class="text-center">{{ __('Translated') }}</th>
              <th class="text-center">{{ __('Active') }}</th><th class="text-center">{{ __('Default') }}</th><th class="text-end">{{ __('Actions') }}</th>
            </tr></thead>
            <tbody>
              @foreach ($languages as $lang)
                @php $c = $counts[$lang->code] ?? null; @endphp
                <tr>
                  <td>{{ $lang->flag }} <span class="fw-medium">{{ $lang->native_name }}</span>
                    <span class="text-muted small">({{ $lang->name }})</span>
                    @if($lang->is_rtl)<span class="badge text-bg-secondary ms-1">RTL</span>@endif</td>
                  <td><code>{{ $lang->code }}</code></td>
                  <td class="text-center">
                    @if($lang->code === 'en')<span class="text-muted small">{{ __('Source') }}</span>
                    @elseif($c)<span class="small">{{ $c->done }}/{{ $c->total }}</span>
                    @else <span class="text-muted small">—</span>@endif
                  </td>
                  <td class="text-center">
                    <form method="POST" action="{{ route('admin.languages.update', $lang->id) }}">
                      @csrf @method('PUT')
                      <input type="hidden" name="is_active" value="{{ $lang->is_active ? 0 : 1 }}">
                      <button class="btn btn-sm btn-link p-0" @disabled($lang->is_default)>
                        <i class="bi {{ $lang->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }} fs-5"></i>
                      </button>
                    </form>
                  </td>
                  <td class="text-center">
                    @if($lang->is_default)<i class="bi bi-check-circle-fill text-primary"></i>
                    @else
                      <form method="POST" action="{{ route('admin.languages.default', $lang->id) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary py-0">{{ __('Make Default') }}</button>
                      </form>
                    @endif
                  </td>
                  <td class="text-end">
                    @if($lang->code !== 'en')
                      <a href="{{ route('admin.languages.translations', $lang->code) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-translate"></i> {{ __('Edit Translations') }}</a>
                      @unless($lang->is_default)
                        <form method="POST" action="{{ route('admin.languages.destroy', $lang->id) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Remove This Language And Its Translations?') }}')">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      @endunless
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">{{ __('Add A Language') }}</div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.languages.store') }}" class="row g-2">
            @csrf
            <div class="col-6"><label class="form-label small">{{ __('Code') }}</label>
              <input name="code" class="form-control form-control-sm" placeholder="{{ __('Bn') }}" required></div>
            <div class="col-6"><label class="form-label small">{{ __('Flag (Emoji)') }}</label>
              <input name="flag" class="form-control form-control-sm" placeholder="🇧🇩"></div>
            <div class="col-12"><label class="form-label small">{{ __('Name (English)') }}</label>
              <input name="name" class="form-control form-control-sm" placeholder="{{ __('Bangla') }}" required></div>
            <div class="col-12"><label class="form-label small">{{ __('Native Name') }}</label>
              <input name="native_name" class="form-control form-control-sm" placeholder="বাংলা" required></div>
            <div class="col-12 form-check ms-2">
              <input type="hidden" name="is_rtl" value="0">
              <input class="form-check-input" type="checkbox" name="is_rtl" value="1" id="rtl">
              <label class="form-check-label small" for="rtl">{{ __('Right-to-left Script') }}</label>
            </div>
            <div class="col-12"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> {{ __('Add Language') }}</button></div>
          </form>
          <div class="form-text mt-2">{{ __('After Adding A Language, Run "Scan For New Strings", Then Edit Its Translations.') }}</div>
        </div>
      </div>
    </div>
  </div>
@endsection
