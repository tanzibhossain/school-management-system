@extends('layouts.admin')
@section('title', 'Library — books')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Books',
    'crumbs' => ['Library', 'Books'],
    'action' => ['label' => 'Add book', 'modal' => 'createModal'],
  ])

  @include('admin.modules.library._tabs', ['active' => 'books'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Copies</th><th>Available</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($books as $b)
          <tr>
            <td class="fw-semibold">{{ $b->title }}</td>
            <td>{{ $b->author ?? '—' }}</td>
            <td>{{ $b->category ?? '—' }}</td>
            <td>{{ $b->total_copies }}</td>
            <td><span class="badge {{ $b->available_copies > 0 ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $b->available_copies }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $b->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.library.books.deactivate', $b->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $b->title }}?')">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-danger">Deactivate</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.modules.library.books._form', ['mode' => 'create'])
  @foreach ($books as $b)
    @include('admin.modules.library.books._form', ['mode' => 'edit', 'b' => $b])
  @endforeach
@endsection
