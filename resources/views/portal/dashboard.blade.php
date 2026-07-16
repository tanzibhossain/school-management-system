<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Family Portal · {{ optional(\App\Modules\School\Models\School::first())->name ?? 'School' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>* { font-family: 'Inter', system-ui, sans-serif; } body { background:#f5f7fb; }</style>
</head>
<body>
  <div class="container" style="max-width:640px; padding-top:6rem;">
    <div class="text-center mb-4">
      <span style="width:64px;height:64px;border-radius:16px;background:#eef2ff;color:#4f46e5;display:inline-flex;align-items:center;justify-content:center;font-size:2rem;"><i class="bi bi-people-fill"></i></span>
      <h1 class="h3 mt-3 mb-1">Welcome, {{ auth()->user()->name }}</h1>
      <p class="text-muted">Family portal · {{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'member') }}</p>
    </div>
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
      <div class="card-body text-center p-5">
        <i class="bi bi-cone-striped text-warning" style="font-size:2rem;"></i>
        <h5 class="mt-3">Your portal is coming soon</h5>
        <p class="text-muted mb-4">Attendance, results, fees, routine and notices for your account will appear here shortly.</p>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Sign out</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
