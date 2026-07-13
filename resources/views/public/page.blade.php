@extends('public.layout')
@section('title', ($page->meta_title ?: $page->title) . ' · ' . ($settings->site_name ?? $school?->name ?? 'School'))
@section('content')
  @includeFirst(['public.templates.' . $view['template'], 'public.templates.full'], ['view' => $view, 'page' => $page])
@endsection
