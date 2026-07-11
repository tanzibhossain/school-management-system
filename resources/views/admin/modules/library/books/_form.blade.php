@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $b->id : 'createModal';
  $action = $isEdit ? route('admin.library.books.update', $b->id) : route('admin.library.books.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit book' : 'Add book' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-8"><label class="form-label">Title <span class="text-danger">*</span></label>
        <input name="title" class="form-control" value="{{ $isEdit ? $b->title : old('title') }}" required></div>
      <div class="col-md-4"><label class="form-label">Total copies <span class="text-danger">*</span></label>
        <input type="number" min="1" name="total_copies" class="form-control" value="{{ $isEdit ? $b->total_copies : old('total_copies', 1) }}" required></div>
      <div class="col-md-6"><label class="form-label">Author</label>
        <input name="author" class="form-control" value="{{ $isEdit ? $b->author : old('author') }}"></div>
      <div class="col-md-6"><label class="form-label">Category</label>
        <input name="category" class="form-control" value="{{ $isEdit ? $b->category : old('category') }}"></div>
      <div class="col-md-4"><label class="form-label">ISBN</label>
        <input name="isbn" class="form-control" value="{{ $isEdit ? $b->isbn : old('isbn') }}"></div>
      <div class="col-md-5"><label class="form-label">Publisher</label>
        <input name="publisher" class="form-control" value="{{ $isEdit ? $b->publisher : old('publisher') }}"></div>
      <div class="col-md-3"><label class="form-label">Edition</label>
        <input name="edition" class="form-control" value="{{ $isEdit ? $b->edition : old('edition') }}"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
