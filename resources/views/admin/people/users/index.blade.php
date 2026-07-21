@extends('layouts.admin')
@section('title', 'Users & roles')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Users & roles',
    'crumbs' => ['People', 'Users & roles'],
    'action' => ['label' => 'New user', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Role') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($users as $u)
          <tr>
            <td class="fw-semibold">{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>@foreach ($u->roles as $r)<span class="badge text-bg-primary">{{ $r->name }}</span> @endforeach</td>
            <td><span class="badge {{ $u->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $u->id }}">{{ __('Edit') }}</button>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roleModal{{ $u->id }}">{{ __('Role') }}</button>
              @if ($u->is_active && $u->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.deactivate', $u->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $u->name }}?')">
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

  {{-- Create --}}
  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.users.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New user') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-7"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="col-md-5"><label class="form-label">{{ __('Role') }} <span class="text-danger">*</span></label>
          <select name="role" class="form-select" required>
            @foreach ($roles as $r)<option value="{{ $r }}" @selected(old('role')===$r)>{{ $r }}</option>@endforeach
          </select></div>
        <div class="col-md-7"><label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
        <div class="col-md-5"><label class="form-label">{{ __('Phone') }}</label>
          <input name="phone" class="form-control" value="{{ old('phone') }}"></div>
        <div class="col-md-6"><label class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
          <input type="password" name="password" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
          <input type="password" name="password_confirmation" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Create') }}</button></div>
    </form>
  </div></div></div>

  {{-- Edit + Role per user --}}
  @foreach ($users as $u)
    <div class="modal fade" id="editModal{{ $u->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.users.update', $u->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ __('Edit user') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ $u->name }}" required></div>
          <div class="col-md-7"><label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ $u->email }}" required></div>
          <div class="col-md-5"><label class="form-label">{{ __('Phone') }}</label>
            <input name="phone" class="form-control" value="{{ $u->phone }}"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
      </form>
    </div></div></div>

    <div class="modal fade" id="roleModal{{ $u->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.users.change-role', $u->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Change role — {{ $u->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">{{ __('Role') }}</label>
          <select name="role" class="form-select">
            @foreach ($roles as $r)<option value="{{ $r }}" @selected($u->roles->pluck('name')->contains($r))>{{ $r }}</option>@endforeach
          </select>
          <div class="form-text">Changing a role revokes the user's API tokens; they must sign in again.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Update role') }}</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
