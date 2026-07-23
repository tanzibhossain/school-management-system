@php
  // Local alias so the (fairly long) FQCN doesn't have to be repeated at
  // every call site below — `use` imports aren't valid inside a compiled
  // Blade @php block (it's PHP function-body code, not file-top-level).
  $bp = \App\Modules\Website\Support\BlockPresentation::class;

  $contained = $contained ?? false;
  $style = $style ?? [];
  $layout = $layout ?? [];

  // Identifies this rendered block back to its editor row (blocks[$index]) —
  // used only by the admin live-preview iframe's click-to-select/drag-reorder/
  // context-menu bridge (see public/layout.blade.php); inert data attributes
  // (and draggable is simply absent) on the real public site.
  $editorAttrs = isset($index)
    ? ' data-block-index="'.(int) $index.'" data-block-group="'.e($group ?? 'blocks').'" draggable="true"'
    : '';

  // hero/admission_form manage their own spacing+background entirely — every
  // other block type gets the standard section+container+default-padding
  // treatment, with the Style tab's overrides applied on the same wrapper
  // element so a custom value cleanly replaces the default instead of adding
  // to it (inline style always wins over the py-4/py-lg-5 utility classes).
  $selfContained = in_array($type, ['hero', 'admission_form'], true);
  $wrap = $bp::wrapper($style, $layout);
  $defaultSpacing = $selfContained ? '' : ($contained ? 'mb-3' : 'py-4 py-lg-5');
  $wrapClass = trim($wrap['class'].' '.$defaultSpacing);
  $wrapStyleAttr = $wrap['style'] !== '' ? ' style="'.$wrap['style'].'"' : '';

  $open = $contained || $selfContained ? '' : '<div class="container">';
  $close = $contained || $selfContained ? '' : '</div>';
@endphp
@if ($contained)
  <div class="{{ $wrapClass }}"{!! $wrapStyleAttr !!}{!! $editorAttrs !!}>
@else
  <section class="{{ $wrapClass }}"{!! $wrapStyleAttr !!}{!! $editorAttrs !!}>
@endif
@switch($type)
  @case('hero')
    <header class="hero py-5" @if(!empty($d['image'])) style="background-image:linear-gradient(rgba(0,0,0,.45),rgba(0,0,0,.45)),url('{{ $d['image'] }}');background-size:cover;background-position:center;" @endif>
      <div class="container py-4 py-lg-5 text-center">
        <h1 class="display-5 mb-3">{{ $d['title'] ?? '' }}</h1>
        @if(!empty($d['subtitle']))<p class="lead text-white-50 mx-auto" style="max-width:42rem;">{{ $d['subtitle'] }}</p>@endif
        @if(!empty($d['button_text']))<a href="{{ $d['button_url'] ?? '#' }}" class="btn btn-light btn-lg mt-2 px-4">{{ $d['button_text'] }}</a>@endif
      </div>
    </header>
    @break

  @case('heading')
    {!! $open !!}
      <h2 class="section-title h3 text-{{ $d['align'] ?? 'start' }} mb-0">{{ $d['text'] ?? '' }}</h2>
    {!! $close !!}
    @break

  @case('richtext')
    {!! $open !!}
      @if(!empty($d['heading']))<h2 class="section-title h3 mb-3">{{ $d['heading'] }}</h2>@endif
      <div class="lh-lg">{!! $d['html'] ?? '' !!}</div>
    {!! $close !!}
    @break

  @case('image')
    {!! $open !!}
      <figure class="text-center mb-0">
        <img src="{{ $d['url'] ?? '' }}" class="img-fluid rounded-3" alt="{{ $d['caption'] ?? '' }}">
        @if(!empty($d['caption']))<figcaption class="text-muted small mt-2">{{ $d['caption'] }}</figcaption>@endif
      </figure>
    {!! $close !!}
    @break

  @case('video')
    {!! $open !!}
      @php
        // Video Options panel — see docs/modules/28-elementor-block-editor-plan.md
        // §7e. 'controls' defaults ON (matches _fields.blade.php's spec-level
        // default for a freshly added block); the rest default OFF, same
        // as an unset checkbox anywhere else in this app.
        $vSource = in_array($d['source'] ?? null, ['youtube', 'vimeo', 'dailymotion', 'videopress', 'self_hosted'], true) ? $d['source'] : 'youtube';
        $vStart = isset($d['start_time']) && $d['start_time'] !== '' ? max(0, (int) $d['start_time']) : null;
        $vEnd = isset($d['end_time']) && $d['end_time'] !== '' ? max(0, (int) $d['end_time']) : null;
        $vAutoplay = ! empty($d['autoplay']);
        $vMute = ! empty($d['mute']);
        $vLoop = ! empty($d['loop']);
        $vControls = ($d['controls'] ?? '') !== '' ? ! empty($d['controls']) : true;
        $vDownload = ! empty($d['download']);
        $vPreload = in_array($d['preload'] ?? null, ['none', 'metadata', 'auto'], true) ? $d['preload'] : 'metadata';
      @endphp
      @if(!empty($d['heading']))<h2 class="section-title h3 mb-3">{{ $d['heading'] }}</h2>@endif
      @if ($vSource === 'self_hosted')
        @if(!empty($d['file_url']))
          <video
            class="w-100 rounded-3"
            @if($vPreload !== 'none') preload="{{ $vPreload }}" @endif
            @if(!empty($d['poster'])) poster="{{ $d['poster'] }}" @endif
            @if($vControls) controls @endif
            @if(!$vDownload) controlslist="nodownload" @endif
            @if($vAutoplay) autoplay muted @elseif($vMute) muted @endif
            @if($vLoop) loop @endif
          ><source src="{{ $d['file_url'] }}{{ $vStart ? '#t='.$vStart.($vEnd ? ','.$vEnd : '') : '' }}"></video>
          @if(!empty($d['caption']))<p class="text-muted small mt-2 mb-0">{{ $d['caption'] }}</p>@endif
        @else
          <p class="text-muted mb-0">{{ __('No Video File Set.') }}</p>
        @endif
      @else
        @php
          $embedUrl = trim((string) ($d['url'] ?? ''));
          // Best-effort YouTube watch/short-link -> embed normalization +
          // start/end/autoplay/mute/loop/controls as URL params. Other
          // platforms (Vimeo/Dailymotion/VideoPress) are trusted to already
          // be a pasted embeddable URL — no per-platform param mapping for
          // those, to avoid guessing at APIs this app doesn't integrate with.
          if ($vSource === 'youtube' && $embedUrl !== '') {
            if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([a-zA-Z0-9_-]{6,})/', $embedUrl, $m)) {
              $embedUrl = 'https://www.youtube.com/embed/'.$m[1];
            }
            $ytParams = array_filter([
              $vAutoplay ? 'autoplay=1' : null,
              $vMute ? 'mute=1' : null,
              $vLoop ? 'loop=1' : null,
              $vControls ? null : 'controls=0',
              $vStart !== null ? 'start='.$vStart : null,
              $vEnd !== null ? 'end='.$vEnd : null,
            ]);
            if ($ytParams) {
              $embedUrl .= (str_contains($embedUrl, '?') ? '&' : '?').implode('&', $ytParams);
            }
          }
        @endphp
        @if($embedUrl !== '')
          <div class="ratio ratio-16x9"><iframe src="{{ $embedUrl }}" allowfullscreen loading="lazy"@if($vAutoplay) allow="autoplay"@endif></iframe></div>
          @if(!empty($d['caption']))<p class="text-muted small mt-2 mb-0">{{ $d['caption'] }}</p>@endif
        @else
          <p class="text-muted mb-0">{{ __('No Video URL Set.') }}</p>
        @endif
      @endif
    {!! $close !!}
    @break

  @case('button')
    {!! $open !!}
      <div class="text-{{ $d['align'] ?? 'start' }}">
        <a href="{{ $d['url'] ?? '#' }}" class="btn btn-brand"@if(!empty($d['open_new_tab'])) target="_blank" rel="noopener"@endif>{{ $d['text'] ?? __('Click Here') }}</a>
      </div>
    {!! $close !!}
    @break

  @case('divider')
    {!! $open !!}
      <hr class="my-0" style="border-top-style:{{ in_array($d['line_style'] ?? null, ['solid','dashed','dotted'], true) ? $d['line_style'] : 'solid' }};width:{{ max(1, min(100, (int) ($d['width_pct'] ?? 100))) }}%;margin-left:auto;margin-right:auto;">
    {!! $close !!}
    @break

  @case('spacer')
    {!! $open !!}
      <div style="height:{{ max(0, min(400, (int) ($d['height'] ?? 40))) }}px;" aria-hidden="true"></div>
    {!! $close !!}
    @break

  @case('icon')
    {!! $open !!}
      @php
        $iconMarkup = '<i class="bi '.e($d['icon'] ?? 'bi-star').'" style="font-size:'.max(12, min(200, (int) ($d['size'] ?? 32))).'px;color:'.(!empty($d['color']) ? e($d['color']) : 'var(--brand)').';"></i>';
      @endphp
      <div class="text-{{ $d['align'] ?? 'center' }}">
        @if(!empty($d['url']))<a href="{{ $d['url'] }}">{!! $iconMarkup !!}</a>@else{!! $iconMarkup !!}@endif
      </div>
    {!! $close !!}
    @break

  @case('google_maps')
    {!! $open !!}
      @if(!empty($d['embed_url']))
        <div class="rounded-3 overflow-hidden" style="height:{{ max(120, min(1000, (int) ($d['height'] ?? 320))) }}px;">
          <iframe src="{{ $d['embed_url'] }}" style="width:100%;height:100%;border:0;" loading="lazy" allowfullscreen></iframe>
        </div>
      @else
        <p class="text-muted mb-0">{{ __('No Map URL Set.') }}</p>
      @endif
    {!! $close !!}
    @break

  @case('image_text')
    {!! $open !!}
      <div class="row g-4 align-items-center {{ ($d['image_side'] ?? 'left') === 'right' ? 'flex-row-reverse' : '' }}">
        <div class="col-md-5"><img src="{{ $d['image'] ?? '' }}" class="img-fluid rounded-3" alt=""></div>
        <div class="col-md-7">
          @if(!empty($d['heading']))<h2 class="section-title h4 mb-3">{{ $d['heading'] }}</h2>@endif
          <div class="lh-lg">{!! $d['html'] ?? '' !!}</div>
        </div>
      </div>
    {!! $close !!}
    @break

  @case('staff')
    {!! $open !!}
      @if(!empty($d['heading']))<h2 class="section-title h3 mb-4">{{ $d['heading'] }}</h2>@endif
      <div class="row {{ $bp::columnClasses($layout, ['mobile' => 2, 'tablet' => 3, 'laptop' => 4, 'desktop' => 4]) }} g-3">
        @forelse($d['members'] ?? [] as $m)
          <div>
            <div class="card h-100 text-center"><div class="card-body">
              <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center mb-2" style="width:64px;height:64px;">
                @if($m->photo)<img src="{{ $m->photo }}" class="rounded-circle" style="width:64px;height:64px;object-fit:cover;" alt="">
                @else<span class="text-brand fw-bold fs-4">{{ strtoupper(mb_substr($m->name, 0, 1)) }}</span>@endif
              </div>
              <div class="fw-semibold small">{{ $m->name }}</div>
              <div class="text-muted small">{{ $m->designation?->name ?? 'Staff' }}</div>
            </div></div>
          </div>
        @empty
          <p class="text-muted mb-0">{{ __('No Staff To Show.') }}</p>
        @endforelse
      </div>
    {!! $close !!}
    @break

  @case('notices')
    {!! $open !!}
      <h2 class="section-title h3 mb-4">{{ $d['heading'] ?? 'Notices' }}</h2>
      <div class="row {{ $bp::columnClasses($layout, ['mobile' => 1, 'tablet' => 2, 'laptop' => 3, 'desktop' => 3]) }} g-3">
        @forelse(($d['notices'] ?? collect())->take($d['limit'] ?? 6) as $n)
          <div><div class="card h-100"><div class="card-body">
            <div class="small text-muted mb-1"><i class="bi bi-megaphone-fill text-brand"></i> {{ optional($n->publish_at ?? $n->created_at)->format('d M Y') }}</div>
            <h3 class="h6 fw-semibold">{{ $n->title }}</h3>
            <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit(strip_tags($n->body), 110) }}</p>
          </div></div></div>
        @empty
          <p class="text-muted mb-0">{{ __('No Notices Published.') }}</p>
        @endforelse
      </div>
    {!! $close !!}
    @break

  @case('stats')
    {!! $open !!}
      <div class="row {{ $bp::columnClasses($layout, ['mobile' => 2, 'tablet' => 4, 'laptop' => 4, 'desktop' => 4]) }} g-3 text-center">
        <div><div class="p-3 bg-light rounded-3"><div class="stat-num">{{ number_format($d['stats']['active_students'] ?? 0) }}</div><div class="text-muted small mt-1">{{ __('Students') }}</div></div></div>
        <div><div class="p-3 bg-light rounded-3"><div class="stat-num">{{ number_format($d['stats']['active_staff'] ?? 0) }}</div><div class="text-muted small mt-1">Teachers &amp; staff</div></div></div>
        @foreach($d['items'] ?? [] as $it)
          <div><div class="p-3 bg-light rounded-3"><div class="stat-num">{{ $it['value'] ?? '' }}</div><div class="text-muted small mt-1">{{ $it['label'] ?? '' }}</div></div></div>
        @endforeach
      </div>
    {!! $close !!}
    @break

  @case('gallery_photo')
    {!! $open !!}
      @if(!empty($d['heading']))<h2 class="section-title h3 mb-4">{{ $d['heading'] }}</h2>@endif
      <div class="row {{ $bp::columnClasses($layout, ['mobile' => 2, 'tablet' => 3, 'laptop' => 4, 'desktop' => 4]) }} g-3">
        @forelse($d['images'] ?? [] as $img)
          <div><a href="{{ is_array($img) ? ($img['url'] ?? '#') : $img }}" target="_blank"><img src="{{ is_array($img) ? ($img['url'] ?? '') : $img }}" class="img-fluid rounded-3" style="aspect-ratio:1;object-fit:cover;width:100%;" alt=""></a></div>
        @empty
          <p class="text-muted mb-0">{{ __('No Photos Yet.') }}</p>
        @endforelse
      </div>
    {!! $close !!}
    @break

  @case('gallery_video')
    {!! $open !!}
      @if(!empty($d['heading']))<h2 class="section-title h3 mb-4">{{ $d['heading'] }}</h2>@endif
      <div class="row {{ $bp::columnClasses($layout, ['mobile' => 1, 'tablet' => 2, 'laptop' => 2, 'desktop' => 2]) }} g-3">
        @forelse($d['videos'] ?? [] as $v)
          <div><div class="ratio ratio-16x9"><iframe src="{{ is_array($v) ? ($v['url'] ?? '') : $v }}" allowfullscreen loading="lazy"></iframe></div></div>
        @empty
          <p class="text-muted mb-0">{{ __('No Videos Yet.') }}</p>
        @endforelse
      </div>
    {!! $close !!}
    @break

  @case('admission_form')
    @include('public.blocks.admission_form')
    @break

  @case('contact')
    {!! $open !!}
      <div class="row g-4">
        <div class="col-md-6">
          <h2 class="section-title h4 mb-3">{{ $d['heading'] ?? 'Get in touch' }}</h2>
          <ul class="list-unstyled">
            @if(($d['address'] ?? null) || ($d['school']->address ?? null))<li class="mb-2"><i class="bi bi-geo-alt text-brand"></i> {{ $d['address'] ?? $d['school']->address }}</li>@endif
            @if($d['phone'] ?? null)<li class="mb-2"><i class="bi bi-telephone text-brand"></i> {{ $d['phone'] }}</li>@endif
            @if(($d['email'] ?? null) || ($d['school']->email ?? null))<li class="mb-2"><i class="bi bi-envelope text-brand"></i> {{ $d['email'] ?? $d['school']->email }}</li>@endif
          </ul>
          @if(!empty($d['map_embed']))<div class="ratio ratio-4x3 mt-3 rounded-3 overflow-hidden"><iframe src="{{ $d['map_embed'] }}" loading="lazy" style="border:0;"></iframe></div>@endif
        </div>
        <div class="col-md-6"><div class="card"><div class="card-body">
          @if(session('contact_sent'))
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ __('Thanks — Your Message Has Been Sent.') }}</div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
          @endif
          <form method="POST" action="{{ route('contact.submit') }}">
            @csrf
            <div class="row g-2">
              <div class="col-md-6"><input name="name" class="form-control" placeholder="{{ __('Your Name') }}" value="{{ old('name') }}" required></div>
              <div class="col-md-6"><input name="email" type="email" class="form-control" placeholder="{{ __('Email') }}" value="{{ old('email') }}"></div>
              <div class="col-md-6"><input name="phone" class="form-control" placeholder="{{ __('Phone') }}" value="{{ old('phone') }}"></div>
              <div class="col-md-6"><input name="subject" class="form-control" placeholder="{{ __('Subject') }}" value="{{ old('subject') }}"></div>
            </div>
            <div class="my-2"><textarea name="message" class="form-control" rows="4" placeholder="{{ __('Message') }}" required>{{ old('message') }}</textarea></div>
            <button class="btn btn-brand"><i class="bi bi-send"></i> {{ __('Send Message') }}</button>
          </form>
        </div></div></div>
      </div>
    {!! $close !!}
    @break

  @case('container')
    {!! $open !!}
      @php
        $containerDir = ($d['direction'] ?? 'column') === 'row' ? 'row' : 'column';
        $containerGap = max(0, min(80, (int) ($d['gap'] ?? 16)));
      @endphp
      <div class="d-flex flex-{{ $containerDir }}" style="gap:{{ $containerGap }}px;">
        @forelse ($d['blocks'] ?? [] as $child)
          @if($containerDir === 'row')<div class="flex-fill">@endif
          @include('public.blocks.render', ['type' => $child['type'], 'd' => $child['d'], 'style' => $child['style'], 'layout' => $child['layout'], 'contained' => true])
          @if($containerDir === 'row')</div>@endif
        @empty
          <p class="text-muted mb-0">{{ __('Empty Container — Add Blocks In The Editor.') }}</p>
        @endforelse
      </div>
    {!! $close !!}
    @break

  @case('grid')
    {!! $open !!}
      <div class="row {{ $bp::columnClasses($layout, ['mobile' => 1, 'tablet' => 2, 'laptop' => 3, 'desktop' => 3]) }} g-3">
        @forelse ($d['blocks'] ?? [] as $child)
          <div>
            @include('public.blocks.render', ['type' => $child['type'], 'd' => $child['d'], 'style' => $child['style'], 'layout' => $child['layout'], 'contained' => true])
          </div>
        @empty
          <p class="text-muted mb-0">{{ __('Empty Grid — Add Blocks In The Editor.') }}</p>
        @endforelse
      </div>
    {!! $close !!}
    @break
@endswitch
@if ($contained)
  </div>
@else
  </section>
@endif
