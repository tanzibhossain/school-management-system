{{-- Detail/Show Page Layout Component --}}
@props([
    'entity' => null,
    'tabs' => ['overview', 'timeline', 'documents', 'settings'],
    'activeTab' => 'overview',
    'actions' => [],
    'breadcrumbs' => [],
    'title' => null,
    'subtitle' => null,
    'avatar' => null,
    'meta' => [],
    'class' => '',
])

@php
    $detailId = 'detail-' . uniqid();
    $defaultTabs = ['overview' => 'Overview', 'timeline' => 'Timeline', 'documents' => 'Documents', 'settings' => 'Settings'];
    $tabs = array_merge($defaultTabs, $tabs);
    $activeTab = $activeTab ?? 'overview';
@endphp

<div id="{{ $detailId }}" class="detail-page {{ $class }}" {{ $attributes }}>
    {{-- Breadcrumbs --}}
    @if(!empty($breadcrumbs))
    <nav class="breadcrumb-nav mb-4" aria-label="Breadcrumb" style="font-size: var(--text-sm);">
        <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
            @foreach($breadcrumbs as $index => $crumb)
                <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" aria-current="{{ $loop->last ? 'page' : 'false' }}">
                    @if(!$loop->last)
                        <a href="{{ $crumb['url'] }}" class="text-muted hover:text-primary">{{ $crumb['label'] }}</a>
                    @else
                        <span class="fw-medium text-slate-900">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    @endif

    {{-- Page Header --}}
    <div class="detail-header d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 mb-6 flex-wrap">
        <div class="detail-title-section">
            @if($avatar)
                <div class="d-inline-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-primary-light text-primary">
                        @if($avatar['url'] ?? false)
                            <img src="{{ $avatar['url'] }}" alt="" />
                        @else
                            {{ $avatar['initial'] ?? strtoupper(substr($title ?? 'E', 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        @if($title)
                            <h1 class="h3 mb-1 fw-bold">{{ $title }}</h1>
                        @endif
                        @if($subtitle)
                            <p class="text-muted small mb-0">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            @else
                <h1 class="h3 mb-1 fw-bold">{{ $title ?? 'Details' }}</h1>
                @if($subtitle)
                    <p class="text-muted small mb-0">{{ $subtitle }}</p>
                @endif
            @endif

            @if(!empty($meta))
                <div class="detail-meta d-flex flex-wrap gap-3 mt-3">
                    @foreach($meta as $key => $value)
                        <div class="meta-item d-flex align-items-center gap-1 text-sm text-muted">
                            @if(is_array($value) && isset($value['icon']))
                                <i class="bi {{ $value['icon'] }}"></i>
                                <span>{{ $value['label'] ?? $value }}</span>
                            @else
                                <span class="text-slate-400">{{ $key }}:</span>
                                <span class="fw-medium text-slate-900">{{ $value }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Actions --}}
        @if(!empty($actions))
            <div class="detail-actions d-flex flex-wrap gap-2 ms-lg-auto">
                @foreach($actions as $action)
                    <x-button
                        variant="{{ $action['variant'] ?? 'secondary' }}"
                        size="sm"
                        icon="{{ $action['icon'] ?? '' }}"
                        icon-position="left"
                        :href="$action['url'] ?? '#'"
                        :class="$action['class'] ?? ''"
                        @if(isset($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif
                        @if(isset($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif
                    >
                        {{ $action['label'] }}
                    </x-button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Tab Navigation --}}
    @if(count($tabs) > 1)
    <div class="detail-tabs mb-4" role="tablist" aria-label="Detail sections">
        <ul class="nav nav-tabs nav-tabs-detail border-bottom border-slate-200" role="tablist">
            @foreach($tabs as $key => $label)
                @php
                    $tabId = 'tab-' . $key;
                    $panelId = 'panel-' . $key;
                    $isActive = $key === $activeTab;
                @endphp
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $isActive ? 'active' : '' }}"
                        id="{{ $tabId }}"
                        data-bs-toggle="tab"
                        data-bs-target="#{{ $panelId }}"
                        role="tab"
                        aria-controls="{{ $panelId }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        type="button"
                    >
                        <i class="bi bi-{{ $key === 'overview' ? 'house' : ($key === 'timeline' ? 'clock-history' : ($key === 'documents' ? 'file-earmark' : 'gear')) }} me-1"></i>
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Tab Panels --}}
    <div class="tab-content detail-panels" id="{{ $detailId }}-panels">
        @foreach($tabs as $key => $label)
            @php
                $panelId = 'panel-' . $key;
                $isActive = $key === $activeTab;
            @endphp
            <div
                class="tab-pane fade {{ $isActive ? 'show active' : '' }}"
                id="{{ $panelId }}"
                role="tabpanel"
                aria-labelledby="tab-{{ $key }}"
                {{ $isActive ? '' : 'hidden' }}
            >
                @isset($content[$key])
                    {{ $content[$key] }}
                @else
                    {{-- Default empty state --}}
                    <div class="empty-state">
                        <i class="bi bi-{{ $key === 'overview' ? 'info-circle' : ($key === 'timeline' ? 'clock-history' : ($key === 'documents' ? 'file-earmark' : 'gear')) }} empty-state-icon"></i>
                        <h3 class="empty-state-title">{{ $label }}</h3>
                        <p class="empty-state-message">No content available for this section.</p>
                    </div>
                @endisset
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Initialize tab persistence
    document.addEventListener('DOMContentLoaded', function() {
        const tabContainer = document.getElementById('{{ $detailId }}-panels');
        if (!tabContainer) return;

        // Persist active tab in sessionStorage
        const tabKey = 'detail-tab-' + '{{ $detailId }}';

        // Restore active tab
        const savedTab = sessionStorage.getItem('detail-active-tab-' + '{{ $detailId }}');
        if (savedTab) {
            const trigger = document.querySelector('#tab-' + savedTab);
            if (trigger) {
                new bootstrap.Tab(trigger).show();
            }
        }

        // Save on tab change
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(trigger) {
            trigger.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.id.replace('tab-', '');
                sessionStorage.setItem('detail-active-tab-' + '{{ $detailId }}', tabId);
            });
        });

        // Keyboard navigation for tabs
        document.addEventListener('keydown', function(e) {
            if (e.target.matches('[data-bs-toggle="tab"]')) {
                const tabs = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]'));
                const currentIndex = tabs.indexOf(e.target);

                if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                    e.preventDefault();
                    const direction = e.key === 'ArrowRight' ? 1 : -1;
                    const newIndex = (tabs.indexOf(e.target) + direction + tabs.length) % tabs.length;
                    tabs[newIndex].focus();
                    new bootstrap.Tab(tabs[newIndex]).show();
                }
            });
        });
    });
})();
</script>
@endpush