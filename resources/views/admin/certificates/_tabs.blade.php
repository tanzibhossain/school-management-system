<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'testimonials' ? 'active' : '' }}" href="{{ route('admin.testimonials.index') }}">Testimonials</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'admit-cards' ? 'active' : '' }}" href="{{ route('admin.admit-cards.index') }}">Admit cards</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'templates' ? 'active' : '' }}" href="{{ route('admin.cert-templates.index') }}">Templates</a></li>
</ul>
