@extends('layouts.admin')
@section('title', __('Page Templates'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Page Templates'),
    'crumbs' => [__('Website'), __('Page Templates')],
  ])

  <p class="text-muted small">
    {{ __('Saved from the page editor Save as Template action — pick one from Start From the next time you create a page.') }}
  </p>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Saved') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @forelse ($templates as $t)
          <tr>
            <td class="fw-semibold">{{ $t->name }}</td>
            <td class="text-muted small">{{ $t->created_at?->format('M j, Y g:i A') }}</td>
            <td class="text-end">
              <form method="POST" action="{{ route('admin.page-templates.update', $t->id) }}" class="d-inline" onsubmit="return fillNewName(this, '{{ $t->name }}')">
                @csrf @method('PUT')
                <input type="hidden" name="name">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ __('Rename') }}" aria-label="{{ __('Rename') }}: {{ $t->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
              </form>
              <form method="POST" action="{{ route('admin.page-templates.destroy', $t->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this template? Pages already created from it are not affected.') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}: {{ $t->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-center text-muted py-4">
            {{ __('No saved templates yet — open any page in the editor and use "Save as Template" (Page Settings panel).') }}
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div></div>

  {{-- Same lightweight prompt-and-submit pattern as the editor's own "Save
       as Template" action (edit.blade.php's fillTemplateName()) — a single
       free-text field doesn't need a full modal. --}}
  @push('scripts')
    <script>
      function fillNewName(form, current) {
        var name = window.prompt(@json(__('Rename template:')), current);
        if (!name || name === current) return false;
        form.querySelector('[name="name"]').value = name;
        return true;
      }
    </script>
  @endpush
@endsection
