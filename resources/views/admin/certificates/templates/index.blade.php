@extends('layouts.admin')
@section('title', 'Testimonial templates')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Testimonial templates',
    'crumbs' => ['Certificates', 'Templates'],
    'action' => ['label' => 'New template', 'modal' => 'createModal'],
  ])
  @include('admin.certificates._tabs', ['active' => 'templates'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Name</th><th>Signatory</th><th>Default</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($templates as $t)
          <tr>
            <td class="fw-semibold">{{ $t->name }}</td>
            <td>{{ $t->signatory_name ?? '—' }}{{ $t->signatory_designation ? ', ' . $t->signatory_designation : '' }}</td>
            <td>{!! $t->is_default ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $t->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.cert-templates.destroy', $t->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $t->name }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.certificates.templates._form', ['mode' => 'create'])
  @foreach ($templates as $t)
    @include('admin.certificates.templates._form', ['mode' => 'edit', 't' => $t])
  @endforeach
@endsection
