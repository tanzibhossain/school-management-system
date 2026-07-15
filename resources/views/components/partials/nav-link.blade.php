@php
    $isActive = $item['active'] ?? false;
    $isDisabled = ! empty($item['disabled']);
    $itemClasses = ['nav-link'];
    if ($isActive) $itemClasses[] = 'active';
    if ($isDisabled) $itemClasses[] = 'disabled';

    $href = $item['href'] ?? '#';
    $icon = $item['icon'] ?? 'bi-circle';
    $badge = $item['badge'] ?? null;
@endphp
<li class="nav-item" role="none">
    <a
        href="{{ $href }}"
        class="{{ implode(' ', $itemClasses) }}"
        role="menuitem"
        aria-current="{{ $isActive ? 'page' : 'false' }}"
        aria-disabled="{{ $isDisabled ? 'true' : 'false' }}"
        @if($isDisabled) tabindex="-1" @endif
    >
        <i class="bi {{ $icon }} nav-icon" aria-hidden="true"></i>
        <span class="nav-label flex-grow-1">{{ $item['label'] }}</span>
        @if($badge)
            <span class="badge badge-sm ms-auto">{{ $badge }}</span>
        @endif
    </a>
</li>
