<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin') · School Management</title>
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="{{ asset('css/admin-design-tokens.css') }}" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
  {{--
    Minimal "app shell" layout — no admin sidebar/topbar/content padding.
    Used by full-screen tools that build their own chrome (currently just
    the Website page builder — see admin/website/pages/edit.blade.php and
    docs/modules/28-elementor-block-editor-plan.md). The page fills the
    viewport exactly; it, not this layout, owns scrolling for its own panes.
  --}}
  <style>
    :root {
      --bs-primary: #4f46e5;
      --bs-primary-rgb: 79, 70, 229;
      --bs-link-color: #4f46e5;
      --bs-link-color-rgb: 79, 70, 229;
      --bs-link-hover-color: #4338ca;
    }
    html, body { height: 100%; margin: 0; overflow: hidden; }
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f1f3f5; }
    .btn-primary {
      --bs-btn-bg: #4f46e5; --bs-btn-border-color: #4f46e5;
      --bs-btn-hover-bg: #4338ca; --bs-btn-hover-border-color: #4338ca;
      --bs-btn-active-bg: #3730a3; --bs-btn-active-border-color: #3730a3;
    }
    .text-primary { color: #4f46e5 !important; }
    .form-control:focus, .form-select:focus { border-color: #a5b4fc; box-shadow: 0 0 0 .2rem rgba(79, 70, 229, .2); }
  </style>
  @stack('styles')
</head>
<body>
  @yield('content')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>
  @stack('scripts')
</body>
</html>
