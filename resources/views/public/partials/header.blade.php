@php
    $primary = $settings->primary_color ?? '#1d4ed8';
    $topText = $settings->topbar_text_color ?? '#ffffff';
    $siteName = $settings->site_name ?? ($school->name ?? 'Our School');

    $loc = $school?->locale ?? app()->getLocale();
    $tz = $school?->timezone ?? config('app.timezone');
    try {
        $today = \Illuminate\Support\Carbon::now($tz)->locale($loc);
    } catch (\Throwable $e) {
        $today = now()->locale($loc);
    }
    $dateStr = $today->translatedFormat('l j F Y');
    if (str_starts_with((string) $loc, 'bn')) {
        $dateStr = strtr($dateStr, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    }

    $tickerPos = $settings->ticker_position ?? 'below_nav';
    $showTicker = $tickerPos !== 'hidden' && ($ticker ?? collect())->isNotEmpty();
    $headerPhones = $school ? $school->phones->where('show_in_header', true)->values() : collect();
    $logoUrl = \App\Support\Media::url($school?->logo);
    $established = $school?->established ? (optional($school->established)->format('Y') ?? $school->established) : null;
    $codes = collect([
        ['label' => $school?->institution_code_label, 'value' => $school?->institution_code],
        ['label' => $school?->school_code_label, 'value' => $school?->school_code],
        ['label' => $school?->technical_branch_code_label, 'value' => $school?->technical_branch_code],
    ])->filter(fn($c) => filled($c['value']));
@endphp

{{-- Row 1: top utility bar (primary bg, configurable text colour) --}}
<div style="background: {{ $primary }}; color: {{ $topText }};">
    <div class="container">
        <div class="row align-items-center g-2 py-1 small" style="min-height:30px;">
            <div class="col-6 text-start text-capitalize">{{ $dateStr }}</div>
            <div class="col-6 text-end">
                @if($headerPhones->isNotEmpty())
                    <i class="bi bi-telephone-fill"></i>
                    @foreach($headerPhones as $ph)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $ph->phone) }}"
                        style="color: {{ $topText }}; text-decoration:none;">{{ $ph->phone }}</a>@if(!$loop->last), @endif
                    @endforeach
                @endif
                @if(($appLanguages ?? collect())->count() > 1)
                    <span class="ms-3">
                        @foreach($appLanguages as $lang)
                            <a href="{{ route('language.switch', $lang->code) }}"
                               style="color: {{ $topText }}; text-decoration:{{ $lang->code === app()->getLocale() ? 'underline' : 'none' }};">{{ $lang->native_name }}</a>@if(!$loop->last) <span style="opacity:.5">|</span> @endif
                        @endforeach
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Row 2: logo | name | institution data --}}
<div class="bg-white border-bottom">
    <div class="container py-3">
        <div class="row align-items-center g-3">
            <div class="col-4 col-md-2 text-center text-md-start">
                <a href="{{ route('home') }}">
                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $siteName }}"
                        style="max-height:72px;max-width:100%;">
                    @else<i class="bi bi-mortarboard-fill display-5" style="color: {{ $primary }};"></i>@endif
                </a>
            </div>
            <div class="col-8 col-md-6">
                <a href="{{ route('home') }}" class="text-decoration-none">
                    <div class="h4 fw-bold mb-0" style="color: {{ $primary }};">{{ $siteName }}</div>
                </a>
                @if($school?->address)
                <div class="text-muted small">{{ $school->address }}</div>@endif
            </div>
            <div class="col-12 col-md-4 small text-muted text-md-end">
                @foreach($codes as $c)
                    <div>{{ $c['label'] ?: 'Code' }}: <strong>{{ $c['value'] }}</strong></div>
                @endforeach
                @if($established)
                <div>Established: <strong>{{ $established }}</strong></div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Notice ticker (above the nav) --}}
@if($showTicker && $tickerPos === 'above_nav')
    @include('public.partials.ticker')
@endif

{{-- Row 3: navigation --}}
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: {{ $primary }};">
    <div class="container">
        <button class="navbar-toggler ms-auto" data-bs-toggle="collapse" data-bs-target="#pubnav"><span
                class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="pubnav">
            <ul class="navbar-nav me-auto">
                @if(($navMenu ?? null) && $navMenu->items->isNotEmpty())
                    @foreach($navMenu->items as $item)
                        @if($item->children->isNotEmpty())
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $item->label }}</a>
                                <ul class="dropdown-menu">
                                    @foreach($item->children as $child)
                                        <li><a class="dropdown-item" href="{{ $child->resolvedUrl() }}" target="{{ $child->target }}">{{ $child->label }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item"><a class="nav-link" href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}">{{ $item->label }}</a></li>
                        @endif
                    @endforeach
                @else
                    {{-- Fallback nav when no menu has been built yet --}}
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/faculty') }}">{{ __('Faculty') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/online-admission') }}">{{ __('Online admission') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/notices') }}">{{ __('Notices') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/contact') }}">{{ __('Contact') }}</a></li>
                @endif
            </ul>
            <a class="btn btn-light btn-sm px-3" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i>
                Login</a>
        </div>
    </div>
</nav>

{{-- Notice ticker (below the nav) --}}
@if($showTicker && $tickerPos === 'below_nav')
    @include('public.partials.ticker')
@endif