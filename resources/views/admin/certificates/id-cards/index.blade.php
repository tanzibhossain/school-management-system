@extends('layouts.admin')
@section('title', 'ID cards')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'ID card batches',
    'crumbs' => ['Certificates', 'ID cards'],
    'action' => ['label' => 'Generate batch', 'modal' => 'genModal'],
  ])
  @include('admin.certificates._tabs', ['active' => 'id-cards'])

  @php $m = ['queued'=>'secondary','processing'=>'info','completed'=>'success','failed'=>'danger']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>#</th><th>Type</th><th>Template</th><th>Scope</th><th>Cards</th><th>Files</th><th>Status</th><th class="text-end" data-orderable="false"></th></tr></thead>
      <tbody>
        @foreach ($batches as $b)
          <tr>
            <td>{{ $b->id }}</td>
            <td class="text-capitalize">{{ $b->type }}</td>
            <td>{{ $b->template?->name ?? '—' }}</td>
            <td class="text-capitalize">{{ $b->scope }}</td>
            <td>{{ $b->total_count }}</td>
            <td>{{ $b->files_count }}</td>
            <td><span class="badge text-bg-{{ $m[$b->status] ?? 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.id-cards.show', $b->id) }}">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="genModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.id-cards.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">Generate ID card batch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-6"><label class="form-label">Type <span class="text-danger">*</span></label>
          <select name="type" class="form-select" required>
            <option value="student">Student</option><option value="staff">Staff</option>
          </select></div>
        <div class="col-md-6"><label class="form-label">Template <span class="text-danger">*</span></label>
          <select name="template_id" class="form-select" required>
            <option value="">— select —</option>
            @foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->type }})</option>@endforeach
          </select>
          @if ($templates->isEmpty())<div class="form-text text-danger">No ID templates — add one under ID templates first.</div>@endif
        </div>
        <div class="col-md-4"><label class="form-label">Scope <span class="text-danger">*</span></label>
          <select name="scope" id="idScope" class="form-select" required>
            <option value="class">A class</option><option value="all">All</option>
          </select></div>
        <div class="col-md-4 id-cls"><label class="form-label">Class</label>
          <select name="class_id" id="idClass" class="form-select">
            <option value="">— select —</option>
            @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select></div>
        <div class="col-md-4 id-cls"><label class="form-label">Section</label>
          <select name="section_id" id="idSection" class="form-select"><option value="">All sections</option></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Generate</button></div>
    </form>
  </div></div></div>

  @push('scripts')
    <script>
      var SECTIONS = @json($sections);
      var scope = document.getElementById('idScope');
      var cls = document.getElementById('idClass'); var sec = document.getElementById('idSection');
      scope.addEventListener('change', function () {
        document.querySelectorAll('.id-cls').forEach(function (el) { el.classList.toggle('d-none', scope.value !== 'class'); });
      });
      cls.addEventListener('change', function () {
        var cid = parseInt(cls.value, 10);
        sec.innerHTML = '<option value="">All sections</option>';
        SECTIONS.filter(function (s) { return s.class_id === cid; }).forEach(function (s) {
          var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; sec.appendChild(o);
        });
      });
    </script>
  @endpush
@endsection
