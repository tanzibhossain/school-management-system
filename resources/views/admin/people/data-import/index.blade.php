@extends('layouts.admin')
@section('title', __('Data Import'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Data import'),
    'crumbs' => [__('People'), __('Data import')],
    'action' => ['label' => __('Import file'), 'modal' => 'uploadModal'],
  ])

  @php $m = ['queued'=>'secondary','processing'=>'info','completed'=>'success','failed'=>'danger']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>#</th><th>{{ __('Type') }}</th><th>{{ __('File') }}</th><th>{{ __('Rows') }}</th><th>{{ __('Imported') }}</th><th>{{ __('Skipped') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false"></th></tr></thead>
      <tbody>
        @foreach ($batches as $b)
          <tr>
            <td>{{ $b->id }}</td>
            <td class="text-capitalize">{{ $b->type }}</td>
            <td class="small">{{ $b->original_filename }}</td>
            <td>{{ $b->total_rows }}</td>
            <td><span class="badge text-bg-success">{{ $b->success_count }}</span></td>
            <td>@if ($b->skipped_count)<span class="badge text-bg-warning">{{ $b->skipped_count }}</span>@else 0 @endif</td>
            <td><span class="badge text-bg-{{ $m[$b->status] ?? 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.data-import.show', $b->id) }}">{{ __('Open') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="uploadModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.data-import.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Import File') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-5"><label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
          <select name="type" class="form-select" required>
            <option value="student">{{ __('Students') }}</option><option value="staff">{{ __('Staff') }}</option>
          </select></div>
        <div class="col-md-7"><label class="form-label">{{ __('File') }} <span class="text-danger">*</span></label>
          <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required></div>
        <div class="col-12"><div class="alert alert-info py-2 mb-0 small">
          Accepts .xlsx / .xls / .csv (max 10 MB). Students need columns: admission_number, name, gender, dob,
          blood_group, class_name, section_name, academic_year, roll_number, guardian_name, guardian_phone,
          guardian_relation. Existing classes/sections/years are matched by name; bad rows are skipped and listed.
        </div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Import') }}</button></div>
    </form>
  </div></div></div>
@endsection
