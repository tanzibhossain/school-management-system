{{-- Documents Tab --}}
<div class="row g-4">
    {{-- Upload Section --}}
    <div class="col-12">
        <x-card title="{{ __('Upload Documents') }}" subtitle="Supported: PDF, JPG, PNG, DOC, DOCX (Max 10MB each)">
            <form action="{{ route('admin.students.documents.store', $student) }}" method="POST" enctype="multipart/form-data" class="dropzone" id="document-dropzone">
                @csrf
                <div class="dropzone-content text-center py-5">
                    <i class="bi bi-cloud-upload fs-1 text-muted mb-3"></i>
                    <p class="text-muted mb-3">{{ __('Drag And Drop Files Here, Or Click To Browse') }}</p>
                    <p class="text-xs text-muted mb-3">{{ __('Supported: PDF, JPG, PNG, DOC, DOCX (Max 10MB Each)') }}</p>
                    <button type="button" class="btn btn-primary" id="browse-files">
                        <i class="bi bi-folder-plus me-1"></i> Browse Files
                    </button>
                    <input type="file" name="documents[]" id="document-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="d-none" style="display: none;">
                </div>
                <div class="dropzone-preview mt-3" id="file-preview" style="display: none;">
                    <div class="row g-2" id="preview-files"></div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary" id="upload-btn" disabled>
                            <i class="bi bi-cloud-upload me-1"></i> Upload All
                        </button>
                        <button type="button" class="btn btn-outline-secondary ms-2" id="clear-files">
                            <i class="bi bi-x-circle me-1"></i> Clear All
                        </button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Documents List --}}
    <div class="col-12">
        <x-card title="{{ __('Documents') }}" subtitle="All uploaded documents">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" class="form-check-input select-all" aria-label="Select all">
                            </th>
                            <th>{{ __('Document') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Uploaded') }}</th>
                            <th>{{ __('Uploaded By') }}</th>
                            <th class="text-end" style="width: 100px;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input row-checkbox" value="{{ $doc->id }}" name="selected_documents[]">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="file-icon-wrapper">
                                            @if($doc->mime_type == 'application/pdf')
                                                <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                                            @elseif(str_starts_with($doc->mime_type, 'image/'))
                                                <i class="bi bi-file-earmark-image text-primary fs-4"></i>
                                            @elseif(str_contains($doc->mime_type, 'word') || str_contains($doc->mime_type, 'officedocument'))
                                                <i class="bi bi-file-earmark-word text-primary fs-4"></i>
                                            @else
                                                <i class="bi bi-file-earmark text-muted fs-4"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $doc->original_name }}</div>
                                            <small class="text-muted">{{ $doc->category ?? 'General' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700">{{ $doc->mime_type }}</span>
                                </td>
                                <td>{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                                <td>{{ $doc->created_at->format('M j, Y H:i') }}</td>
                                <td>{{ $doc->uploadedBy->name ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.students.documents.download', [$student, $doc]) }}" class="btn btn-outline-primary" title="{{ __('Download') }}">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @can('students.documents.delete')
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('{{ route('admin.students.documents.destroy', [$student, $doc]) }}', 'Delete this document?')" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">{{ __('No Documents Uploaded Yet') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Bulk Actions --}}
    <div class="col-12">
        <div class="bulk-actions d-flex align-items-center gap-2" style="display: none;">
            <select class="form-select form-select-sm bulk-action-select" style="width: auto;">
                <option value="">{{ __('Bulk Actions...') }}</option>
                <option value="delete">{{ __('Delete Selected') }}</option>
                <option value="download">{{ __('Download Selected') }}</option>
            </select>
            <button type="button" class="btn btn-sm btn-primary bulk-action-apply" disabled>{{ __('Apply') }}</button>
            <span class="bulk-count text-muted small d-none"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('document-dropzone');
    const fileInput = document.getElementById('document-input');
    const browseBtn = document.getElementById('browse-files');
    const previewContainer = document.getElementById('file-preview');
    const previewFiles = document.getElementById('preview-files');
    const uploadBtn = document.getElementById('upload-btn');
    const clearBtn = document.getElementById('clear-files');
    const form = document.getElementById('document-dropzone');

    let files = [];

    // Browse button
    browseBtn?.addEventListener('click', () => fileInput.click());

    // File input change
    fileInput?.addEventListener('change', handleFiles);

    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropzone?.addEventListener(event, e => {
            e.preventDefault();
            e.stopPropagation();
            if (event === 'dragenter' || event === 'dragover') {
                dropzone.classList.add('border-primary', 'bg-primary-light');
            } else {
                dropzone.classList.remove('border-primary', 'bg-primary-light');
            }
        });
    });

    dropzone?.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('border-primary', 'bg-primary-light');
        const dt = e.dataTransfer;
        handleFiles({ target: { files: dt.files } });
    });

    function handleFiles(e) {
        const newFiles = Array.from(e.target.files);
        const validFiles = newFiles.filter(f => {
            const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (!validTypes.includes(f.type)) {
                alert(`Invalid file type: ${f.name}. Allowed: PDF, JPG, PNG, DOC, DOCX`);
                return false;
            }
            if (f.size > maxSize) {
                alert(`File too large: ${f.name}. Max 10MB.`);
                return false;
            }
            return true;
        });

        files = [...files, ...validFiles];
        updatePreview();
    }

    function updatePreview() {
        if (files.length > 0) {
            previewContainer.style.display = 'block';
            previewFiles.innerHTML = files.map((file, index) => `
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card border border-slate-200">
                        <div class="card-body p-2 text-center">
                            <div class="file-icon-wrapper mb-2">
                                ${getFileIcon(files[index])}
                            </div>
                            <p class="card-text small text-truncate mb-1">${file.name}</p>
                            <p class="card-text text-xs text-muted">${(file.size / 1024).toFixed(1)} KB</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-file" data-index="${index}">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `).join('');

            uploadBtn.disabled = false;

            // Remove file handlers
            previewFiles.querySelectorAll('.remove-file').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    files.splice(index, 1);
                    updatePreview();
                });
            });
        } else {
            previewContainer.style.display = 'none';
            uploadBtn.disabled = true;
        }
    }

    function getFileIcon(file) {
        if (file.type === 'application/pdf') return '<i class="bi bi-file-earmark-pdf text-danger fs-2"></i>';
        if (file.type.startsWith('image/')) return '<i class="bi bi-file-earmark-image text-primary fs-2"></i>';
        if (file.type.includes('word') || file.type.includes('officedocument')) return '<i class="bi bi-file-earmark-word text-primary fs-2"></i>';
        return '<i class="bi bi-file-earmark text-muted fs-2"></i>';
    }

    // Browse button
    browseBtn?.addEventListener('click', () => fileInput.click());

    // File input change
    fileInput?.addEventListener('change', handleFiles);

    // Clear files
    clearBtn?.addEventListener('click', () => {
        files = [];
        fileInput.value = '';
        updatePreview();
    });

    // Form submit
    form?.addEventListener('submit', async e => {
        if (files.length === 0) {
            e.preventDefault();
            return;
        }

        const formData = new FormData();
        files.forEach(f => formData.append('documents[]', f));

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                location.reload();
            } else {
                alert('Upload failed');
            }
        } catch (err) {
            console.error(err);
            alert('Upload failed');
        }
    });

    // Bulk actions
    const selectAll = document.querySelector('.select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActions = document.querySelector('.bulk-actions');
    const bulkSelect = document.querySelector('.bulk-action-select');
    const bulkApply = document.querySelector('.bulk-action-apply');
    const bulkCount = document.querySelector('.bulk-count');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const count = checked.length;

        if (count > 0) {
            bulkActions.style.display = 'flex';
            bulkCount.textContent = `${count} selected`;
            bulkCount.classList.remove('d-none');
            bulkSelect.disabled = false;
            bulkApply.disabled = false;
        } else {
            bulkActions.style.display = 'none';
            bulkCount.classList.add('d-none');
        }

        // Update select all state
        const total = document.querySelectorAll('.row-checkbox').length;
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        const selectAll = document.querySelector('.select-all');
        if (selectAll) {
            selectAll.checked = count === total && total > 0;
            selectAll.indeterminate = count > 0 && count < total;
        }
    }

    selectAll?.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkUI();
    });

    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkUI);
    });

    bulkApply?.addEventListener('click', async () => {
        const action = bulkSelect.value;
        const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);

        if (!action || !ids.length) return;

        if (action === 'delete' && !confirm(`Delete ${ids.length} documents?`)) return;

        try {
            const response = await fetch('/admin/students/documents/bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action, ids })
            });

            if (response.ok) location.reload();
        } catch (err) {
            alert('Bulk action failed');
        }
    });

    function confirmDelete(url, message) {
        if (confirm(message)) {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => location.reload());
        }
    }

    updateBulkUI();
});
</script>
@endpush