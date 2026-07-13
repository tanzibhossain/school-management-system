@if (($page->show_title ?? true) && empty($view['blocks']))
  <div class="container py-5"><h1 class="section-title h2">{{ $page->title }}</h1></div>
@endif
@foreach ($view['blocks'] as $b)
  @include('public.blocks.render', ['type' => $b['type'], 'd' => $b['d']])
@endforeach
