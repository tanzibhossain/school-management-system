{{-- Empty State Component --}}
@props([
    'type' => 'default', // default, no-data, no-results, error, success, warning, offline, loading, permissions
    'title' => null,
    'message' => null,
    'icon' => null,
    'action' => null,
    'actionLabel' => null,
    'actionVariant' => 'primary',
    'secondaryAction' => null,
    'secondaryActionLabel' => null,
    'secondaryActionVariant' => 'secondary',
    'illustration' => true,
    'size' => 'md', // sm, md, lg, xl
    'class' => '',
    'style' => '',
])

@php
    $types = [
        'default' => [
            'icon' => 'inbox',
            'title' => 'No content available',
            'message' => 'There\'s nothing here yet.',
            'svg' => 'inbox',
        ],
        'no-data' => [
            'icon' => 'inbox',
            'title' => 'No data found',
            'message' => 'We couldn\'t find any records matching your criteria.',
            'svg' => 'database',
        ],
        'no-results' => [
            'icon' => 'search',
            'title' => 'No results found',
            'message' => 'Try adjusting your search or filter criteria.',
            'svg' => 'search',
        ],
        'error' => [
            'icon' => 'exclamation-triangle',
            'title' => 'Something went wrong',
            'message' => 'An unexpected error occurred. Please try again.',
            'svg' => 'alert-circle',
        ],
        'success' => [
            'icon' => 'check-circle',
            'title' => 'All done!',
            'message' => 'There are no pending items at the moment.',
            'svg' => 'check-circle',
        ],
        'warning' => [
            'icon' => 'exclamation-triangle',
            'title' => 'Attention required',
            'message' => 'There are items that need your attention.',
            'svg' => 'alert-triangle',
        ],
        'offline' => [
            'icon' => 'wifi-off',
            'title' => 'You\'re offline',
            'message' => 'Check your connection and try again.',
            'svg' => 'wifi-off',
        ],
        'loading' => [
            'icon' => 'hourglass',
            'title' => 'Loading...',
            'message' => 'Please wait while we fetch the data.',
            'svg' => 'hourglass',
        ],
        'permissions' => [
            'icon' => 'lock',
            'title' => 'Access denied',
            'message' => 'You don\'t have permission to view this content.',
            'svg' => 'lock',
        ],
        'empty-cart' => [
            'icon' => 'cart',
            'title' => 'Your cart is empty',
            'message' => 'Looks like you haven\'t added anything yet.',
            'svg' => 'cart',
        ],
        'empty-inbox' => [
            'icon' => 'inbox',
            'title' => 'Inbox is empty',
            'message' => 'You\'re all caught up! No new messages.',
            'svg' => 'inbox',
        ],
        'empty-calendar' => [
            'icon' => 'calendar',
            'title' => 'No events scheduled',
            'message' => 'Enjoy your free time or add a new event.',
            'svg' => 'calendar',
        ],
        'empty-folder' => [
            'icon' => 'folder',
            'title' => 'Folder is empty',
            'message' => 'Drag files here or click to upload.',
            'svg' => 'folder',
        ],
    ];

    $config = $types[$type] ?? $types['default'];

    // Override with props if provided
    $title = $title ?? $config['title'];
    $message = $message ?? $config['message'];
    $icon = $icon ?? $config['icon'];
    $svgName = $config['svg'];

    $sizeClasses = [
        'sm' => 'py-3 px-4',
        'md' => 'py-5 px-6',
        'lg' => 'py-8 px-8',
        'xl' => 'py-12 px-10',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    $iconSizes = [
        'sm' => 'w-12 h-12',
        'md' => 'w-16 h-16',
        'lg' => 'w-24 h-24',
        'xl' => 'w-32 h-32',
    ];
    $iconSizeClass = $iconSizes[$size] ?? $iconSizes['md'];

    $titleSizes = [
        'sm' => 'text-lg',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
    ];
    $titleSizeClass = $titleSizes[$size] ?? $titleSizes['md'];

    $messageSizes = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
        'xl' => 'text-xl',
    ];
    $messageSizeClass = $messageSizes[$size] ?? $messageSizes['md'];

    $emptyId = 'empty-' . uniqid();
@endphp

<div
    id="{{ $emptyId }}"
    class="empty-state text-center {{ $class }}"
    style="{{ $style }}"
    role="status"
    aria-live="polite"
>
    @if($illustration)
        <div class="empty-illustration mb-4" aria-hidden="true">
            @if($type === 'loading')
                <div class="spinner-border {{ $iconSizeClass.replace('w-', '').replace('h-', '') }} text-primary" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                </div>
            @else
                <div class="empty-icon {{ $iconSizeClass }} mx-auto text-slate-300 dark:text-slate-600" aria-hidden="true">
                    @include('components.empty-state.icons.' . $svgName)
                </div>
            @endif
        </div>
    @endif

    @if($title)
        <h3 class="empty-title {{ $titleSizeClass }} fw-semibold text-slate-900 mb-2">{{ $title }}</h3>
    @endif

    @if($message)
        <p class="empty-message {{ $messageSizeClass }} text-slate-500 mb-4">{{ $message }}</p>
    @endif

    @if($action || $secondaryAction)
        <div class="empty-actions d-flex flex-wrap justify-content-center gap-2 mt-4">
            @if($secondaryAction)
                <x-button
                    variant="{{ $secondaryActionVariant }}"
                    size="sm"
                    :href="$secondaryAction"
                    :class="$secondaryAction['class'] ?? ''"
                >
                    {{ $secondaryActionLabel }}
                </x-button>
            @endif
            @if($action)
                <x-button
                    variant="{{ $actionVariant }}"
                    size="md"
                    icon="{{ $action['icon'] ?? '' }}"
                    icon-position="left"
                    :href="$action"
                    :class="$action['class'] ?? ''"
                    @if(isset($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif
                >
                    {{ $actionLabel }}
                </x-button>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide empty state when content is loaded dynamically
    document.addEventListener('contentLoaded', function(e) {
        const emptyState = document.getElementById('{{ $emptyId }}');
        if (emptyState && e.target.contains(emptyState)) {
            emptyState.style.display = 'none';
        }
    });
});
</script>
@endpush