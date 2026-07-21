@extends('layouts.admin')
@section('title', __('Translations') . ' — ' . $language->native_name)
@section('content')
  @include('admin.partials.page-header', [
    'title' => __('Translations') . ' — ' . $language->flag . ' ' . $language->native_name,
    'crumbs' => [__('Settings'), __('Languages'), $language->name],
  ])

  <div class="card">
    <div class="card-header">
      <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
          <input name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="{{ __('Search text…') }}">
        </div>
        <div class="col-auto form-check ms-2">
          <input class="form-check-input" type="checkbox" name="missing" value="1" id="missing" @checked($missingOnly) onchange="this.form.submit()">
          <label class="form-check-label small" for="missing">{{ __('Untranslated only') }}</label>
        </div>
        <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">{{ __('Search') }}</button></div>
        <div class="col text-end">
          <a href="{{ route('admin.languages.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> {{ __('Back to languages') }}</a>
        </div>
      </form>
    </div>

    <form method="POST" action="{{ route('admin.languages.translations.save', $language->code) }}">
      @csrf @method('PUT')
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr>
            <th style="width:50%">{{ __('English (source)') }}</th>
            <th>{{ $language->native_name }}</th>
          </tr></thead>
          <tbody>
            @forelse ($rows as $row)
              <tr>
                <td class="small">{{ $row->key }}</td>
                <td>
                  <input name="t[{{ $row->id }}]" value="{{ old("t.{$row->id}", $row->value) }}"
                         class="form-control form-control-sm {{ $row->value === null ? 'border-warning' : '' }}"
                         @if($language->is_rtl) dir="rtl" @endif>
                </td>
              </tr>
            @empty
              <tr><td colspan="2" class="text-center text-muted py-4">
                {{ __('No strings found — run "Scan for new strings" on the Languages page.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div>{{ $rows->links() }}</div>
        <button class="btn btn-primary btn-sm"><i class="bi bi-save"></i> {{ __('Save translations') }}</button>
      </div>
    </form>
  </div>
@endsection
