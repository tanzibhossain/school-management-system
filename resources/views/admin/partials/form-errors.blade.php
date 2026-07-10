{{-- Inline validation error summary — include at the top of a modal/form body. --}}
@if ($errors->any())
  <div class="alert alert-danger py-2">
    <ul class="mb-0 ps-3">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif
