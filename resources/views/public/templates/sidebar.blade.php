<div class="container py-4 py-lg-5">
  <div class="row g-4 g-lg-5">
    <div class="col-lg-8">
      <h1 class="section-title h3 mb-4">{{ $page->title }}</h1>
      @foreach ($view['blocks'] as $i => $b)
        @include('public.blocks.render', ['type' => $b['type'], 'd' => $b['d'], 'contained' => true, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'path' => [$i], 'group' => 'blocks'])
      @endforeach
    </div>
    <aside class="col-lg-4">
      @foreach ($view['sidebar'] as $i => $b)
        @include('public.sidebar.render', ['type' => $b['type'], 'd' => $b['d'], 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'path' => [$i], 'group' => 'sidebar'])
      @endforeach
    </aside>
  </div>
</div>
