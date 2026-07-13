@extends('layouts.admin')
@section('title', 'Routine setup')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Routine setup', 'crumbs' => ['Setup', 'Routine setup']])

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.routine.index') }}">Class routine</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.routine-setup.index') }}">Periods &amp; rooms</a></li>
  </ul>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card"><div class="card-header">Periods</div><div class="card-body">
        <form method="POST" action="{{ route('admin.routine-setup.periods.store') }}" class="row g-2 align-items-end mb-3">
          @csrf
          <div class="col-5"><label class="form-label small text-muted mb-1">Name</label><input name="name" class="form-control form-control-sm" placeholder="Period 1" required></div>
          <div class="col-3"><label class="form-label small text-muted mb-1">From</label><input type="time" name="start_time" class="form-control form-control-sm" required></div>
          <div class="col-3"><label class="form-label small text-muted mb-1">To</label><input type="time" name="end_time" class="form-control form-control-sm" required></div>
          <div class="col-1"><button class="btn btn-sm btn-primary">+</button></div>
        </form>
        <table class="table table-sm align-middle mb-0">
          <tbody>
            @forelse ($periods as $p)
              <tr>
                <td class="fw-semibold">{{ $p->name }}</td>
                <td class="small text-muted">{{ \Illuminate\Support\Str::of($p->start_time)->substr(0,5) }}–{{ \Illuminate\Support\Str::of($p->end_time)->substr(0,5) }}</td>
                <td class="text-end"><form method="POST" action="{{ route('admin.routine-setup.periods.destroy', $p->id) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">✕</button></form></td>
              </tr>
            @empty
              <tr><td class="text-muted">No periods yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card"><div class="card-header">Rooms</div><div class="card-body">
        <form method="POST" action="{{ route('admin.routine-setup.rooms.store') }}" class="row g-2 align-items-end mb-3">
          @csrf
          <div class="col-7"><label class="form-label small text-muted mb-1">Name</label><input name="name" class="form-control form-control-sm" placeholder="Room 101" required></div>
          <div class="col-4"><label class="form-label small text-muted mb-1">Capacity</label><input type="number" min="1" name="capacity" class="form-control form-control-sm"></div>
          <div class="col-1"><button class="btn btn-sm btn-primary">+</button></div>
        </form>
        <table class="table table-sm align-middle mb-0">
          <tbody>
            @forelse ($rooms as $r)
              <tr>
                <td class="fw-semibold">{{ $r->name }}</td>
                <td class="small text-muted">{{ $r->capacity ? $r->capacity . ' seats' : '—' }}</td>
                <td class="text-end"><form method="POST" action="{{ route('admin.routine-setup.rooms.destroy', $r->id) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">✕</button></form></td>
              </tr>
            @empty
              <tr><td class="text-muted">No rooms yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div>
    </div>
  </div>
@endsection
