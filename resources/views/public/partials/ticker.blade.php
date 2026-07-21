<div class="border-bottom bg-light" data-notice-ticker>
    <div class="container d-flex align-items-center gap-2 py-1" style="overflow:hidden;">
        <a href="{{ url('/notices') }}" class="badge text-bg-danger text-decoration-none flex-shrink-0"><i class="bi bi-megaphone-fill"></i> {{ __('Notice') }}</a>
        <div class="pub-ticker flex-grow-1">
            <div class="pub-ticker-track">
                @foreach($ticker as $t)
                    <a href="{{ url('/notices') }}" class="text-decoration-none text-dark me-5">{{ $t->title }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
