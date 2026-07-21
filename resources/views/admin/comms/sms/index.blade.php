@extends('layouts.admin')
@section('title', __('SMS'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'SMS',
    'crumbs' => ['Comms', 'SMS'],
    'action' => ['label' => 'Compose', 'modal' => 'composeModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>#</th><th>{{ __('Scope') }}</th><th>{{ __('Message') }}</th><th>{{ __('Recipients') }}</th><th>{{ __('Status') }}</th><th>{{ __('When') }}</th><th class="text-end" data-orderable="false"></th></tr></thead>
      <tbody>
        @foreach ($batches as $b)
          @php $m = ['queued'=>'secondary','processing'=>'info','completed'=>'success','failed'=>'danger']; @endphp
          <tr>
            <td>{{ $b->id }}</td>
            <td class="text-capitalize">{{ $b->scope }}</td>
            <td class="text-truncate" style="max-width:280px">{{ $b->message_body }}</td>
            <td>{{ $b->total_count }}</td>
            <td><span class="badge text-bg-{{ $m[$b->status] ?? 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
            <td class="small">{{ $b->created_at?->format('d M Y H:i') }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sms.show', $b->id) }}">{{ __('Open') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="composeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.sms.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Compose SMS') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-4"><label class="form-label">{{ __('Send To') }} <span class="text-danger">*</span></label>
          <select name="scope" id="smsScope" class="form-select" required>
            <option value="all">{{ __('All Active Students') }}</option>
            <option value="class">{{ __('A Class') }}</option>
          </select></div>
        <div class="col-md-4 sms-class d-none"><label class="form-label">{{ __('Class') }}</label>
          <select name="class_id" id="smsClass" class="form-select">
            <option value="">— select —</option>
            @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select></div>
        <div class="col-md-4 sms-class d-none"><label class="form-label">{{ __('Section') }} <span class="text-muted small">(optional)</span></label>
          <select name="section_id" id="smsSection" class="form-select" data-sel=""><option value="">{{ __('All Sections') }}</option></select></div>
        <div class="col-12"><label class="form-label">{{ __('Message') }} <span class="text-danger">*</span></label>
          <textarea name="body" id="smsBody" rows="4" class="form-control" maxlength="1000" required></textarea>
          <div class="form-text"><span id="smsChars">0</span> {{ __('Characters') }}</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Send') }}</button></div>
    </form>
  </div></div></div>

  @push('scripts')
    <script>
      var SECTIONS = @json($sections);
      var scope = document.getElementById('smsScope');
      var clsWrap = document.querySelectorAll('.sms-class');
      var cls = document.getElementById('smsClass');
      var sec = document.getElementById('smsSection');
      scope.addEventListener('change', function () {
        clsWrap.forEach(function (el) { el.classList.toggle('d-none', scope.value !== 'class'); });
      });
      cls.addEventListener('change', function () {
        var cid = parseInt(cls.value, 10);
        sec.innerHTML = '<option value="">All sections</option>';
        SECTIONS.filter(function (s) { return s.class_id === cid; }).forEach(function (s) {
          var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; sec.appendChild(o);
        });
      });
      var body = document.getElementById('smsBody'), chars = document.getElementById('smsChars');
      body.addEventListener('input', function () { chars.textContent = body.value.length; });
    </script>
  @endpush
@endsection
