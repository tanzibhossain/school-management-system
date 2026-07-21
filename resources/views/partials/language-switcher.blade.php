{{-- Language switcher — rendered only when more than one language is active.
     $appLanguages / $appLanguage are shared by the SetLocale middleware. --}}
@if(($appLanguages ?? collect())->count() > 1)
  <div class="dropdown {{ $class ?? '' }}">
    <a class="dropdown-toggle text-decoration-none {{ $linkClass ?? '' }}" href="#" data-bs-toggle="dropdown" role="button">
      {{ $appLanguage?->flag }} {{ $appLanguage?->native_name ?? strtoupper(app()->getLocale()) }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
      @foreach($appLanguages as $lang)
        <li><a class="dropdown-item {{ $lang->code === app()->getLocale() ? 'active' : '' }}"
               href="{{ route('language.switch', $lang->code) }}">{{ $lang->flag }} {{ $lang->native_name }}</a></li>
      @endforeach
    </ul>
  </div>
@endif
