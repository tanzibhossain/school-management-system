{{-- Dropdown Component --}}
@props([
    'trigger' => null,
    'align' => 'end', // start, end
    'class' => '',
    'triggerClass' => '',
    'menuClass' => '',
    'placement' => 'bottom', // top, bottom, left, right
    'offset' => [0, 8],
    'closeOnClick' => true,
])

@php
    $dropdownId = 'dropdown-' . uniqid();
    $triggerId = $dropdownId . '-trigger';
    $menuId = $dropdownId . '-menu';
@endphp

<div class="dropdown {{ $class }}" id="{{ $dropdownId }}">
    @if($trigger)
        <button
            id="{{ $triggerId }}"
            class="dropdown-toggle btn {{ $triggerClass }}"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="{{ $closeOnClick ? 'true' : 'outside' }}"
            aria-expanded="false"
            aria-haspopup="true"
        >
            {{ $trigger }}
        </button>
    @else
        <button
            id="{{ $triggerId }}"
            class="dropdown-toggle btn {{ $triggerClass }}"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="{{ $closeOnClick ? 'true' : 'outside' }}"
            aria-expanded="false"
            aria-haspopup="true"
        >
            {{ $triggerSlot ?? 'Menu' }}
        </button>
    @endif

    <div
        class="dropdown-menu {{ $menuClass }}"
        id="{{ $menuId }}"
        aria-labelledby="{{ $triggerId }}"
        data-bs-popper="none"
        data-bs-placement="{{ $placement }}"
        data-bs-offset="{{ json_encode($offset) }}"
    >
        {{ $slot }}
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dropdown = document.getElementById('{{ $dropdownId }}');
            if (dropdown) {
                new bootstrap.Dropdown(dropdown.querySelector('.dropdown-toggle'));
            }
        });
    </script>
    @endpush
</div>