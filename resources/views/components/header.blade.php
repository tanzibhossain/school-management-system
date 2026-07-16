{{-- Modern Header Component --}}
@props([
    'user' => null,
    'notifications' => [],
    'searchable' => true,
    'quickActions' => [],
    'class' => '',
])

@php
    $headerId = 'header-' . uniqid();
@endphp

<header
    id="{{ $headerId }}"
    class="page-head border-bottom sticky-top {{ $class }}"
    role="banner"
>
    <div class="container-fluid px-3 px-lg-4 h-100">
        <div class="d-flex align-items-center justify-content-between h-100 gap-3">
            <!-- Mobile Sidebar Toggle -->
            <button
                class="btn btn-ghost btn-icon d-lg-none"
                type="button"
                aria-label="Toggle navigation"
                aria-expanded="false"
                aria-controls="sidebar-main"
                onclick="document.dispatchEvent(new CustomEvent('sidebar:toggle', { detail: { show: true } }))"
            >
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <!-- Global Search -->
            @if($searchable)
                {{-- Opens the command palette (the real fuzzy search). ⌘K / Ctrl+K also opens it. --}}
                <button
                    type="button"
                    class="header-search-trigger d-flex align-items-center ms-auto"
                    style="width: 280px; max-width: 44vw; gap: .55rem; height: 38px; padding: 0 .75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #64748b; cursor: text; text-align: left;"
                    onclick="document.dispatchEvent(new CustomEvent('command-palette:open'))"
                    aria-label="Search (Ctrl or Cmd + K)"
                >
                    <i class="bi bi-search" style="font-size: .95rem;" aria-hidden="true"></i>
                    <span style="flex: 1 1 auto; font-size: .9rem;">Search…</span>
                    <kbd class="js-shortcut-hint" style="font-size: .7rem; background: #e2e8f0; color: #475569; border-radius: 4px; padding: .1rem .4rem; font-family: monospace; white-space: nowrap;">Ctrl K</kbd>
                </button>
            @endif

            <!-- Right Actions -->
            <div class="d-flex align-items-center gap-1 gap-lg-2 ms-auto">
                <!-- Notifications -->
                <div class="dropdown position-relative">
                    <button
                        class="btn btn-ghost btn-icon position-relative"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifications"
                    >
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        @if(count($notifications) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ count($notifications) > 9 ? '9+' : count($notifications) }}
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 360px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            @if(count($notifications) > 0)
                                <a href="#" class="text-xs text-primary">Mark all read</a>
                            @endif
                        </li>
                        @if(empty($notifications))
                            <li><span class="dropdown-item text-muted text-center py-4">No new notifications</span></li>
                        @else
                            @foreach($notifications as $notification)
                                <li>
                                    <a class="dropdown-item d-flex align-items-start gap-3 py-3" href="{{ $notification['url'] ?? '#' }}">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-light text-primary d-flex align-items-center justify-content-center">
                                            <i class="bi {{ $notification['icon'] ?? 'bi-bell' }}" aria-hidden="true"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <p class="mb-1 fw-medium text-sm">{{ $notification['title'] }}</p>
                                            <p class="mb-0 text-xs text-muted">{{ $notification['time'] }}</p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center text-primary" href="#">View all notifications</a></li>
                        @endif
                    </ul>
                </div>

                <!-- Quick Actions -->
                @if(!empty($quickActions))
                    <div class="dropdown d-lg-none">
                        <button class="btn btn-ghost btn-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Quick actions">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            @foreach($quickActions as $action)
                                <li><a class="dropdown-item" href="{{ $action['url'] }}"><i class="bi {{ $action['icon'] }} me-2"></i>{{ $action['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- User Menu -->
                <div class="dropdown">
                    <button
                        class="btn btn-ghost d-flex align-items-center gap-2 py-1 px-2"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="User menu"
                    >
                        <div class="avatar avatar-sm">
                            @if($user['avatar'] ?? false)
                                <img src="{{ $user['avatar'] }}" alt="" />
                            @else
                                <span class="text-primary fw-semibold">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                            @endif
                        </div>
                        <span class="d-none d-md-inline-block fw-medium text-truncate" style="max-width: 120px;">{{ $user['name'] ?? 'User' }}</span>
                        <i class="bi bi-chevron-down d-none d-md-inline-block text-muted" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 220px;">
                        <li class="dropdown-header px-3 py-2">
                            <div class="fw-medium">{{ $user['name'] }}</div>
                            <div class="text-xs text-muted">{{ $user['email'] ?? '' }}</div>
                            @if(isset($user['role']))
                                <span class="badge badge-sm badge-neutral mt-1">{{ $user['role'] }}</span>
                            @endif
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger w-100 text-start">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // The header search box is a trigger for the command palette, which owns
            // the ⌘K / Ctrl+K shortcut and the fuzzy search (see command-palette component).

            // OS-aware shortcut hint: ⌘K on Mac, Ctrl K elsewhere. Detect from the
            // browser so the label matches the user's actual keyboard.
            var isMac = /Mac|iPhone|iPad|iPod/i.test(navigator.platform || navigator.userAgent || '');
            document.querySelectorAll('.js-shortcut-hint').forEach(function(el) {
                el.textContent = isMac ? '⌘K' : 'Ctrl K';
            });

            // Notification dropdown click outside handling
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        var toggle = menu.previousElementSibling;
                        if (toggle && toggle.classList.contains('dropdown-toggle')) {
                            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                        }
                    });
                }
            });
        });
    </script>
    @endpush