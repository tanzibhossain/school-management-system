@switch($type)
  @case('quick_links')
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6 section-title mb-3">{{ $d['heading'] ?? 'Quick links' }}</h3>
      <div class="d-flex flex-column gap-2">
        @foreach($d['links'] ?? [] as $l)
          <a href="{{ $l['url'] ?? '#' }}" class="text-decoration-none"><i class="bi bi-chevron-right small"></i> {{ $l['label'] ?? '' }}</a>
        @endforeach
      </div>
    </div></div>
    @break

  @case('office_hours')
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6 section-title mb-3">{{ $d['heading'] ?? 'Office hours' }}</h3>
      <ul class="list-unstyled small mb-0">
        @foreach($d['lines'] ?? [] as $line)
          <li class="d-flex justify-content-between border-bottom py-1">
            <span>{{ is_array($line) ? ($line['label'] ?? '') : $line }}</span>
            @if(is_array($line) && !empty($line['value']))<span class="text-muted">{{ $line['value'] }}</span>@endif
          </li>
        @endforeach
      </ul>
    </div></div>
    @break

  @case('contact_info')
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6 section-title mb-3">{{ $d['heading'] ?? 'Contact' }}</h3>
      <ul class="list-unstyled small mb-0">
        @if(($d['address'] ?? null) || ($d['school']->address ?? null))<li class="mb-2"><i class="bi bi-geo-alt text-brand"></i> {{ $d['address'] ?? $d['school']->address }}</li>@endif
        @if($d['phone'] ?? null)<li class="mb-2"><i class="bi bi-telephone text-brand"></i> {{ $d['phone'] }}</li>@endif
        @if(($d['email'] ?? null) || ($d['school']->email ?? null))<li><i class="bi bi-envelope text-brand"></i> {{ $d['email'] ?? $d['school']->email }}</li>@endif
      </ul>
    </div></div>
    @break

  @case('recent_notices')
    <div class="card mb-3"><div class="card-body">
      <h3 class="h6 section-title mb-3">{{ $d['heading'] ?? 'Recent notices' }}</h3>
      @forelse(($d['notices'] ?? collect())->take($d['limit'] ?? 5) as $n)
        <div class="small border-bottom py-2">
          <div class="fw-semibold">{{ $n->title }}</div>
          <div class="text-muted">{{ optional($n->publish_at ?? $n->created_at)->format('d M Y') }}</div>
        </div>
      @empty
        <p class="text-muted small mb-0">{{ __('No notices.') }}</p>
      @endforelse
    </div></div>
    @break
@endswitch
