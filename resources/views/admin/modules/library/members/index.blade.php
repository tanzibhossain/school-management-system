@extends('layouts.admin')
@section('title', __('Library — members'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Members',
    'crumbs' => ['Library', 'Members'],
    'action' => ['label' => 'Add member', 'modal' => 'createModal'],
  ])

  @include('admin.modules.library._tabs', ['active' => 'members'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Membership #</th><th>{{ __('Member') }}</th><th>{{ __('Type') }}</th><th>{{ __('Joined') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($members as $m)
          <tr>
            <td class="fw-semibold"><code>{{ $m->membership_number }}</code></td>
            <td>{{ $m->user?->name ?? '—' }}</td>
            <td class="text-capitalize">{{ $m->member_type }}</td>
            <td>{{ optional($m->joined_at)->format('d M Y') }}</td>
            <td><span class="badge {{ $m->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $m->id }}">{{ __('Edit') }}</button>
              @if ($m->is_active)
                <form method="POST" action="{{ route('admin.library.members.deactivate', $m->id) }}" class="d-inline" onsubmit="return confirm('Deactivate this member?')">
                  @csrf @method('PATCH')
                  <button class="btn btn-sm btn-outline-danger">{{ __('Deactivate') }}</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.modules.library.members._form', ['mode' => 'create'])
  @foreach ($members as $m)
    @include('admin.modules.library.members._form', ['mode' => 'edit', 'm' => $m])
  @endforeach
@endsection
