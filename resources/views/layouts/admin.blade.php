<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin') · School Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    body { background:#f6f8fb; }
    .sidebar { width:240px; min-height:100vh; }
    .sidebar .nav-link { color:#495057; border-radius:.5rem; padding:.4rem .75rem; }
    .sidebar .nav-link.active { background:#e7f0ff; color:#1d4ed8; font-weight:600; }
    .content { margin-left:240px; }
    @media (max-width: 768px){ .sidebar{ display:none; } .content{ margin-left:0; } }
  </style>
</head>
<body>
  <nav class="sidebar bg-white border-end position-fixed p-3">
    <div class="fw-bold text-primary fs-5 mb-3 px-2">School Admin</div>
    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
      <li class="nav-item"><div class="px-2 pt-3 pb-1 text-uppercase small text-muted">Setup</div></li>
      <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}">Classes</a></li>
    </ul>
  </nav>

  <div class="content">
    <nav class="navbar bg-white border-bottom px-4 py-2">
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-sm btn-outline-secondary">Sign out</button>
        </form>
      </div>
    </nav>

    <main class="p-4">
      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif
      @yield('content')
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
  @stack('scripts')
</body>
</html>
