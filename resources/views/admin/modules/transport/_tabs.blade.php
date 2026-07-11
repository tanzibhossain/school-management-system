<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'routes' ? 'active' : '' }}" href="{{ route('admin.transport.routes.index') }}">Routes</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'vehicles' ? 'active' : '' }}" href="{{ route('admin.transport.vehicles.index') }}">Vehicles</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'drivers' ? 'active' : '' }}" href="{{ route('admin.transport.drivers.index') }}">Drivers</a></li>
</ul>
