@extends('public.layout')
@section('title', ($settings->site_name ?? $school?->name ?? 'Our School'))
@section('content')
    <header class="hero py-5">
        <div class="container py-4 py-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h1 class="display-5 mb-3">Welcome to {{ $settings->site_name ?? $school?->name ?? 'Demo School' }}</h1>
                    <p class="lead mb-4 text-white-50" style="max-width:38rem;">
                        {{ $settings->meta_description ?? 'Nurturing curious minds and building a community of lifelong learners.' }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#results" class="btn btn-light btn-lg px-4"><i class="bi bi-mortarboard"></i> Check
                            results</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-4">{{ __('Portal login') }}</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bg-white text-center rounded-3 p-3 h-100">
                                <div class="stat-num">{{ number_format($stats['active_students']) }}</div>
                                <div class="text-muted small mt-1">{{ __('Students') }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white text-center rounded-3 p-3 h-100">
                                <div class="stat-num">{{ number_format($stats['active_staff']) }}</div>
                                <div class="text-muted small mt-1">Teachers &amp; staff</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="notices" class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <h2 class="section-title h3 mb-0">{{ __('Latest notices') }}</h2>
                <span class="text-muted small">{{ $notices->count() }} active</span>
            </div>
            @if ($notices->isEmpty())
                <p class="text-muted">{{ __('No notices published right now. Check back soon.') }}</p>
            @else
                <div class="row g-3">
                    @foreach ($notices->take(6) as $n)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-2 text-brand">
                                        <i class="bi bi-megaphone-fill"></i>
                                        <span
                                            class="small text-muted">{{ optional($n->publish_at ?? $n->created_at)->format('d M Y') }}</span>
                                    </div>
                                    <h3 class="h6 fw-semibold">{{ $n->title }}</h3>
                                    <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit(strip_tags($n->body), 120) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="staff" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title h3 mb-4">{{ __('Our team') }}</h2>
            @if ($staff->isEmpty())
                <p class="text-muted">{{ __('Staff profiles are coming soon.') }}</p>
            @else
                <div class="row g-3">
                    @foreach ($staff->take(8) as $member)
                        <div class="col-6 col-md-3">
                            <div class="card h-100 text-center">
                                <div class="card-body">
                                    <div class="rounded-circle bg-white border d-inline-flex align-items-center justify-content-center mb-2"
                                        style="width:64px;height:64px;">
                                        @if ($member->photo)<img src="{{ $member->photo }}" class="rounded-circle"
                                            style="width:64px;height:64px;object-fit:cover;" alt="">
                                        @else<span
                                        class="text-brand fw-bold fs-4">{{ strtoupper(mb_substr($member->name, 0, 1)) }}</span>@endif
                                    </div>
                                    <div class="fw-semibold">{{ $member->name }}</div>
                                    <div class="text-muted small">{{ $member->designation?->name ?? 'Staff' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="results" class="py-5">
        <div class="container">
            <div class="card">
                <div class="card-body p-4 p-lg-5 text-center">
                    <h2 class="section-title h3 mb-2">{{ __('Check your exam results') }}</h2>
                    <p class="text-muted mb-4">Results are published here once released. Sign in to the student portal to
                        view full report cards.</p>
                    <a href="{{ route('login') }}" class="btn btn-brand btn-lg px-4"><i
                            class="bi bi-box-arrow-in-right"></i> {{ __('Student portal login') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection