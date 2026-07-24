@extends('public.layout')
{{-- Sections below deliberately use the BLOCK form (@section(...)@endsection)
     with a raw {!! !!} echo, not the inline @section('name', $value) form.
     Laravel's inline form silently HTML-escapes $value itself (see
     ManagesLayouts::startSection() -> e($content)) — since layout.blade.php's
     $pageTitle/$metaDesc/$ogUrl already run every yielded section through
     Blade's own {{ }} once (see its <title>/og:title/twitter:title tags),
     escaping here too meant every title/description containing &, ", <, or >
     was rendered double-escaped ("&amp;amp;" instead of "&amp;") while the
     page's own <h1> (echoed once via {{ $page->title }} directly, never
     through a section) was correctly single-escaped — the inconsistency
     PageSeoMetaTagsTest::test_page_title_is_escaped_consistently_across_title_og_and_twitter_tags
     catches. Keeping the escape in layout.blade.php (used consistently
     everywhere else in this app for text content) and echoing raw here
     makes every title/description escape exactly once, no matter which
     section supplied it. --}}
@section('title')
{!! ($page->meta_title ?: $page->title) . ' · ' . ($settings->site_name ?? $school?->name ?? 'School') !!}
@endsection
{{-- Only defined when the page has its own value — layout.blade.php falls
     back to the site-wide Website > Settings default otherwise (see its
     $metaDesc/$ogUrl computation). --}}
@if ($page->meta_desc)
@section('meta_description')
{!! $page->meta_desc !!}
@endsection
@endif
@if ($page->og_image)
@section('og_image')
{!! $page->og_image !!}
@endsection
@endif
@section('content')
  @includeFirst(['public.templates.' . $view['template'], 'public.templates.full'], ['view' => $view, 'page' => $page])
@endsection
