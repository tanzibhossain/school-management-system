{{-- Timeline Tab --}}
<div class="timeline-container">
    @if(!empty($timelineEvents))
        <ul class="timeline">
            @foreach($timelineEvents as $event)
                <li class="timeline-item">
                    <div class="timeline-marker {{ $event['type'] ?? 'default' }}">
                        <i class="bi {{ $event['icon'] ?? 'bi-circle' }}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-1 fw-medium">{{ $event['title'] }}</h6>
                            <span class="text-muted small">{{ $event['date']->format('M j, Y H:i') }}</span>
                        </div>
                        <p class="text-muted small mb-2">{{ $event['description'] }}</p>
                        @if(!empty($event['meta']))
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($event['meta'] as $key => $value)
                                    <span class="badge bg-slate-100 text-slate-700 text-xs">
                                        {{ $key }}: {{ $value }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($event['actions']))
                            <div class="mt-2 d-flex gap-1">
                                @foreach($event['actions'] as $action)
                                    <a href="{{ $action['url'] }}" class="btn btn-sm btn-outline-primary btn-icon">
                                        <i class="bi {{ $action['icon'] }}"></i>
                                        <span class="ms-1">{{ $action['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clock-history fs-1 text-slate-300"></i>
            <h3 class="mt-3 mb-1">No Timeline Events</h3>
            <p class="text-muted">Timeline events will appear here as they occur.</        </div>
    @endif
</div>

<style>
.timeline-container {
    position: relative;
}
.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
    position: relative;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--color-border);
}
.timeline-item {
    position: relative;
    padding-left: 56px;
    padding-bottom: 2rem;
}
.timeline-item:last-child {
    padding-bottom: 0;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: 11px;
    bottom: 0;
    top: 40px;
    width: 2px;
    background: var(--color-border);
}
.timeline-item:last-child::before {
    display: none;
}
.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    z-index: 1;
}
.timeline-marker.default { border-color: var(--color-border); color: var(--color-text-muted); }
.timeline-marker.academic { border-color: var(--color-primary); background: var(--color-primary-light); color: var(--color-primary); }
.timeline-marker.finance { border-color: var(--color-warning); background: var(--color-warning-light); color: var(--color-warning); }
.timeline-marker.attendance { border-color: var(--color-success); background: var(--color-success-light); color: var(--color-success); }
.timeline-marker.behavior { border-color: var(--color-danger); background: var(--color-danger-light); color: var(--color-danger); }
.timeline-marker.admin { border-color: var(--color-info); background: var(--color-info-light); color: var(--color-info); }

.timeline-content {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 1rem;
    box-shadow: var(--shadow-sm);
}
</style>