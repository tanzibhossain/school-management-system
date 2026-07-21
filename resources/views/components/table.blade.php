{{-- Table Component --}}
@props([
    'headers' => [],
    'rows' => [],
    'striped' => false,
    'hoverable' => true,
    'bordered' => false,
    'responsive' => true,
    'sortable' => false,
    'actions' => null,
    'emptyMessage' => 'No data available',
    'class' => '',
    'headerClass' => '',
    'rowClass' => '',
    'emptyClass' => '',
    'caption' => null,
    'footer' => null,
])

@php
    $tableClasses = ['table'];
    if ($striped) $tableClasses[] = 'table-striped';
    if ($hoverable) $tableClasses[] = 'table-hover';
    if ($bordered) $tableClasses[] = 'table-bordered';
    if ($class) $tableClasses[] = $class;
    $tableClassString = implode(' ', $tableClasses);

    $responsiveClass = $responsive ? 'table-responsive' : '';
@endphp

@if($responsive)
<div class="table-responsive">
@endif

<table class="{{ $tableClassString }}" {{ $attributes }}>
    @if($caption)
    <caption class="caption-top">{{ $caption }}</caption>
    @endif
    <thead class="{{ $headerClass }}">
        <tr>
            @foreach($headers as $header)
                @php
                    $headerAttrs = [];
                    if (isset($header['sortable']) && $header['sortable'] && $sortable) {
                        $headerAttrs['data-sortable'] = 'true';
                        $headerAttrs['data-sort-key'] = $header['key'] ?? $header['label'];
                    }
                    if (isset($header['width'])) {
                        $headerAttrs['style'] = 'width: ' . $header['width'];
                    }
                    if (isset($header['class'])) {
                        $headerAttrs['class'] = $header['class'];
                    }
                @endphp
                <th {{ $headerAttrs }}>
                    {{ $header['label'] }}
                </th>
            @endforeach
            @if($actions)
            <th scope="col" class="text-end">{{ __('Actions') }}</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr class="{{ $rowClass }} {{ $loop->odd ? 'odd' : 'even' }}">
            @foreach($headers as $header)
                @php
                    $value = data_get($row, $header['key']);
                    $formatter = $header['formatter'] ?? null;
                    $displayValue = $formatter ? $formatter($value, $row) : $value;
                @endphp
                <td {{ isset($header['class']) ? 'class="' . $header['class'] . '"' : '' }}>
                    {{ $displayValue }}
                </td>
            @endforeach
            @if($actions)
            <td class="text-end">
                {{ $actions($row) }}
            </td>
            @endif
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($headers) + ($actions ? 1 : 0) }}" class="text-center py-5 {{ $emptyClass }}">
                <div class="empty-state">
                    <i class="bi bi-inbox empty-state-icon"></i>
                    <p class="empty-state-message">{{ $emptyMessage }}</p>
                </div>
            </td>
        </tr>
    @endforelse
    </tbody>
    @if($footer)
    <tfoot>
        <tr>
            {{ $footer }}
        </tr>
    </tfoot>
    @endif
</table>

@if($responsive)
</div>
@endif