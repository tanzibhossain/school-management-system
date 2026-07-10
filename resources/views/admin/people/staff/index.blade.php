@extends('layouts.admin')
@section('title', 'Staff')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Staff',
    'crumbs' => ['People', 'Staff'],
    'action' => ['label' => 'Hire staff', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Employee ID</th><th>Name</th><th>Designation</th><th>Department</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($staff as $s)
          <tr>
            <td><code>{{ $s->employee_id }}</code></td>
            <td class="fw-semibold">{{ $s->name }}</td>
            <td>{{ $s->designation?->name ?? '—' }}</td>
            <td>{{ $s->department?->name ?? '—' }}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $s->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.staff.deactivate', $s->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $s->name }}?')">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-danger">Deactivate</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @php
    $selOptions = function ($list, $selected = null) {
      $out = '<option value="">— none —</option>';
      foreach ($list as $o) { $out .= '<option value="'.$o->id.'"'.(((int)$selected===(int)$o->id)?' selected':'').'>'.e($o->name).'</option>'; }
      return $out;
    };
    $genderOptions = function ($selected = null) {
      $out = '<option value="">—</option>';
      foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l) { $out .= '<option value="'.$v.'"'.($selected===$v?' selected':'').'>'.$l.'</option>'; }
      return $out;
    };
  @endphp

  @include('admin.people.staff._form', ['mode' => 'create'])
  @foreach ($staff as $s)
    @include('admin.people.staff._form', ['mode' => 'edit', 's' => $s])
  @endforeach
@endsection
