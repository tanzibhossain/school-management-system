<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'testimonials' ? 'active' : '' }}" href="{{ route('admin.testimonials.index') }}">{{ __('Testimonials') }}</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'admit-cards' ? 'active' : '' }}" href="{{ route('admin.admit-cards.index') }}">{{ __('Admit cards') }}</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'id-cards' ? 'active' : '' }}" href="{{ route('admin.id-cards.index') }}">{{ __('ID cards') }}</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'templates' ? 'active' : '' }}" href="{{ route('admin.cert-templates.index') }}">{{ __('Testimonial templates') }}</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'id-templates' ? 'active' : '' }}" href="{{ route('admin.id-card-templates.index') }}">{{ __('ID templates') }}</a></li>
</ul>
