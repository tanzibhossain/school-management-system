@extends('public.layout')
@section('title', ($page->meta_title ?: $page->title) . ' · ' . ($settings->site_name ?? $school?->name ?? 'School'))
{{-- Only defined when the page has its own value — layout.blade.php falls
     back to the site-wide Website > Settings default otherwise (see its
     $metaDesc/$ogUrl computation). --}}
@if ($page->meta_desc)
@section('meta_description', $page->meta_desc)
@endif
@if ($page->og_image)
@section('og_image', $page->og_image)
@endif
@section('content')
  @includeFirst(['public.templates.' . $view['template'], 'public.templates.full'], ['view' => $view, 'page' => $page])
@endsection
