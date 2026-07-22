{{-- DataTable 2.0 Component --}}
@props([
    'id' => null,
    'columns' => [],
    'url' => null,
    'searchable' => true,
    'sortable' => true,
    'pageLength' => 25,
    'lengthMenu' => [10, 25, 50, 100],
    'responsive' => true,
    'bulkActions' => [],
    'actions' => null,
    'rowId' => 'id',
    'class' => '',
    'caption' => null,
    'footer' => null,
    'stateSave' => false,
    'processing' => true,
    'serverSide' => true,
    'exportButtons' => ['csv', 'excel', 'pdf'],
    'dom' => "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'row'<'col-sm-12'tr>><'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>B",
    'buttons' => [],
    'order' => [],
    'columnDefs' => [],
    'ajaxData' => [],
    'drawCallback' => null,
    'initComplete' => null,
])

@php
    $tableId = $id ?? 'datatable-' . uniqid();
    $bulkActionIds = array_column($bulkActions, 'id');
@endphp

<div class="datatable-wrapper {{ $class }}" {{ $attributes }}>
    {{-- Toolbar --}}
    @if(!empty($bulkActions) || !empty($actions))
    <div class="datatable-toolbar d-flex flex-wrap gap-2 mb-3">
        @if(!empty($bulkActions))
            <div class="bulk-actions d-flex align-items-center gap-2" style="display: none;">
                <select class="form-select form-select-sm bulk-action-select" style="width: auto;" disabled>
                    <option value="">{{ __('Bulk Actions...') }}</option>
                    @foreach($bulkActions as $action)
                        <option value="{{ $action['id'] }}">{{ $action['label'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-primary bulk-action-apply" disabled>{{ __('Apply') }}</button>
                <span class="bulk-count text-muted small d-none"></span>
            </div>
        @endif

        <div class="ms-auto d-flex gap-2">
            @if(!empty($actions))
                <div class="btn-group" role="group">
                    @foreach($actions as $action)
                        <x-button
                            variant="{{ $action['variant'] ?? 'secondary' }}"
                            size="sm"
                            icon="{{ $action['icon'] ?? '' }}"
                            icon-position="left"
                            :href="$action['url']"
                            :class="$action['class'] ?? ''"
                        >
                            {{ $action['label'] }}
                        </x-button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Table Container --}}
    <div class="table-responsive">
        <table
            id="{{ $tableId }}"
            class="table datatable-js table-hover align-middle {{ $responsive ? 'table-responsive' : '' }}"
            data-url="{{ $url }}"
            data-page-length="{{ $pageLength }}"
            data-length-menu='@json($lengthMenu)'
            data-searchable="{{ $searchable ? 'true' : 'false' }}"
            data-sortable="{{ $sortable ? 'true' : 'false' }}"
            data-server-side="{{ $serverSide ? 'true' : 'false' }}"
            data-processing="{{ $processing ? 'true' : 'false' }}"
            data-state-save="{{ $stateSave ? 'true' : 'false' }}"
            data-page-length="{{ $pageLength }}"
            data-length-menu='@json($lengthMenu)'
            data-order='@json($order)'
            data-column-defs='@json($columnDefs)'
            data-ajax-data='@json($ajaxData)'
            data-export-buttons='@json($exportButtons)'
            data-dom="{{ $dom }}"
            data-row-id="{{ $rowId }}"
            data-bulk-actions='@json($bulkActionIds)'
        >
            @if($caption)
            <caption>{{ $caption }}</caption>
            @endif
            <thead>
                <tr>
                    @if(!empty($bulkActions))
                    <th scope="col" class="text-center" style="width: 40px;">
                        <input type="checkbox" class="form-check-input select-all" aria-label="Select all">
                    </th>
                    @endif
                    @foreach($columns as $column)
                        @php
                            $colAttrs = [];
                            if (isset($column['width'])) {
                                $colAttrs['style'] = 'width: ' . $column['width'];
                            }
                            if (isset($column['class'])) {
                                $colAttrs['class'] = $column['class'];
                            }
                            if (isset($column['sortable']) && !$column['sortable']) {
                                $colAttrs['data-sortable'] = 'false';
                            }
                            if (isset($column['visible']) && !$column['visible']) {
                                $colAttrs['class'] = ($colAttrs['class'] ?? '') . ' d-none';
                            }
                            if (isset($column['render'])) {
                                $colAttrs['data-render'] = $column['render'];
                            }
                        @endphp
                        <th scope="col" {{ $colAttrs }}>
                            {{ __($column['label']) }}
                        </th>
                    @endforeach
                    @if(!empty($actions))
                    <th scope="col" class="text-end" style="width: 120px;">{{ __('Actions') }}</th>
                    @endif
                </tr>
            </thead>
            @if($footer)
            <tfoot>
                <tr>
                    @if(!empty($bulkActions))
                    <th></th>
                    @endif
                    @foreach($columns as $column)
                        <th>{{ __($column['label']) }}</th>
                    @endforeach
                    @if(!empty($actions))
                    <th>{{ __('Actions') }}</th>
                    @endif
                </tr>
            </tfoot>
            @endif
            <tbody>
                {{-- Rows will be populated by DataTables via AJAX --}}
            </tbody>
        </table>
    </div>

    {{-- Column Visibility Modal --}}
    <div class="modal fade" id="{{ $tableId }}-colvis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Column Visibility') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                        @foreach($columns as $index => $column)
                            @if(!isset($column['hideFromToggle']) || !$column['hideFromToggle'])
                                <div class="list-group-item px-3 py-2">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input col-toggle"
                                            type="checkbox"
                                            role="switch"
                                            data-column="{{ $index }}"
                                            @if(!isset($column['visible']) || $column['visible']) checked @endif
                                        >
                                        <label class="form-check-label">{{ __($column['label']) }}</label>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="{{ $tableId }}-colvis-apply">{{ __('Apply') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $dtLanguage = [
        'processing' => '<div class="spinner-border spinner-sm text-primary" role="status"><span class="visually-hidden">' . __('Loading...') . '</span></div>',
        'zeroRecords' => __('No matching records found'),
        'emptyTable' => __('No data available'),
        'info' => __('Showing _START_ to _END_ of _TOTAL_ entries'),
        'infoEmpty' => __('Showing 0 to 0 of 0 entries'),
        'infoFiltered' => __('(filtered from _MAX_ total entries)'),
        'lengthMenu' => __('_MENU_ entries per page'),
        'search' => '',
        'searchPlaceholder' => __('Search...'),
    ];
@endphp

@push('scripts')
<script>
(function() {
    const dtLanguage = @json($dtLanguage);

    // Wait for jQuery and DataTables
    function initDataTable() {
        if (typeof $ === 'undefined' || !$.fn.DataTable) {
            setTimeout(initDataTable, 100);
            return;
        }

        const tableEl = document.getElementById('{{ $tableId }}');
        if (!tableEl) return;

        const $table = $(tableEl);
        const url = $table.data('url');
        const pageLength = $table.data('page-length');
        const lengthMenu = $table.data('length-menu');
        const searchable = $table.data('searchable');
        const sortable = $table.data('sortable');
        const serverSide = $table.data('server-side');
        const processing = $table.data('processing');
        const stateSave = $table.data('state-save');
        const order = $table.data('order');
        const columnDefs = $table.data('column-defs');
        const ajaxData = $table.data('ajax-data');
        const exportButtons = $table.data('export-buttons');
        const dom = $table.data('dom');
        const rowId = $table.data('row-id');
        const bulkActionIds = $table.data('bulk-actions');

        // Build column definitions for DataTables
        const dtColumns = [];
        const visibleColumns = [];
        const ths = $table.find('thead th');
        ths.each(function(i, th) {
            const $th = $(th);
            const colDef = {
                data: $th.data('data') || $th.attr('data-data') || ($th.text().trim().toLowerCase().replace(/\s+/g, '_')),
                name: $th.text().trim(),
                orderable: !$th.data('sortable') && !$th.data('sortable') !== 'false' ? false : true,
                searchable: true,
                className: $th.attr('class') || '',
                render: $th.data('render') ? window[$th.data('render')] : undefined
            };
            dtColumns.push(colDef);
            visibleColumns.push($th.text().trim());
        });

        // Initialize DataTable
        const dt = $table.DataTable({
            processing: processing,
            serverSide: serverSide,
            ajax: {
                url: url,
                type: 'POST',
                data: function(d) {
                    // Add CSRF token
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    // Add custom ajax data
                    $.each({{ json_encode($ajaxData) }}, function(key, value) {
                        d[key] = value;
                    });
                    // Add bulk action filter if any selected
                    const selectedIds = $table.find('tbody input.row-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();
                    if (selectedIds.length) {
                        d.bulk_action_ids = selectedIds;
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error, thrown);
                    $table.find('tbody').html('<tr><td colspan="100%" class="text-center text-danger py-4">' + @json(__('Failed to load data')) + '</td></tr>');
                }
            },
            columns: dtColumns,
            order: order.length ? order : [],
            columnDefs: columnDefs.length ? columnDefs : [],
            pageLength: pageLength,
            lengthMenu: lengthMenu,
            searching: searchable,
            ordering: sortable,
            stateSave: stateSave,
            dom: dom,
            rowId: rowId,
            responsive: true,
            language: Object.assign({}, dtLanguage, {
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            }),
            buttons: exportButtons.length ? [
                {
                    extend: 'collection',
                    text: '<i class="bi bi-download me-1"></i> ' + @json(__('Export')),
                    className: 'btn btn-outline-secondary btn-sm',
                    buttons: exportButtons.map(btn => {
                        if (typeof btn === 'string') {
                            return { extend: btn, className: 'btn-sm' };
                        }
                        return { ...btn, className: (btn.className || '') + ' btn-sm' };
                    })
                }
            ] : [],
            drawCallback: function(settings) {
                // Initialize tooltips
                const tooltips = this.api().table().container().querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltips.forEach(el => new bootstrap.Tooltip(el));

                // Update bulk action UI
                updateBulkActions();

                // Call custom drawCallback
                @if($drawCallback)
                    {{ $drawCallback }};
                @endif
            },
            initComplete: function() {
                // Add column visibility toggle button
                const $wrapper = $(this.api().table().container());
                const $length = $wrapper.find('.dataTables_length');
                const colVisBtn = `
                    <button class="btn btn-outline-secondary btn-sm ms-2" type="button" data-bs-toggle="modal" data-bs-target="#{{ $tableId }}-colvis" title="{{ __('Column Visibility') }}">
                        <i class="bi bi-columns-gap"></i>
                    </button>
                `;
                $length.after(colVisBtn);

                @if($initComplete)
                    {{ $initComplete }};
                @endif
            },
            rowCallback: function(row, data) {
                // Add row checkbox for bulk actions
                if (bulkActionIds.length > 0) {
                    const $row = $(row);
                    const rowId = data.id || data.id;
                    if (rowId && !$row.find('.row-checkbox').length) {
                        $row.prepend('<td class="text-center"><input type="checkbox" class="form-check-input row-checkbox" value="' + rowId + '" aria-label="Select row"></td>');
                    }
                }
            }
        });

        // Column visibility modal
        const colvisModal = new bootstrap.Modal(document.getElementById('{{ $tableId }}-colvis'));
        const tableApi = dt;

        // Column toggle
        document.querySelectorAll('#{{ $tableId }}-colvis .col-toggle').forEach(function(cb) {
            cb.addEventListener('change', function() {
                const colIdx = parseInt(this.dataset.column, 10);
                tableApi.column(colIdx).visible(this.checked);
            });
        });

        document.getElementById('{{ $tableId }}-colvis-apply')?.addEventListener('click', function() {
            colvisModal.hide();
        });

        // Select all checkbox
        $table.on('change', 'thead .select-all', function() {
            const checked = this.checked;
            $table.find('tbody .row-checkbox').prop('checked', this.checked).trigger('change');
        });

        // Row checkbox change
        $table.on('change', 'tbody .row-checkbox', function() {
            updateBulkActions();
        });

        function updateBulkActions() {
            const checked = $table.find('tbody .row-checkbox:checked');
            const count = checked.length;
            const $bulkActions = $table.closest('.datatable-wrapper').find('.bulk-actions');
            const $selectAll = $table.find('thead .select-all');

            if (count > 0) {
                $bulkActions.show();
                $bulkActions.find('.bulk-action-select').prop('disabled', false);
                $bulkActions.find('.bulk-action-apply').prop('disabled', false);
                $bulkActions.find('.bulk-count').text(count + ' ' + @json(__('selected'))).removeClass('d-none');
            } else {
                $bulkActions.hide();
                $bulkActions.find('.bulk-action-select').prop('disabled', true);
                $bulkActions.find('.bulk-action-apply').prop('disabled', true);
                $bulkActions.find('.bulk-count').addClass('d-none');
            }

            // Update select all state
            const total = $table.find('tbody .row-checkbox').length;
            const checked = $table.find('tbody .row-checkbox:checked').length;
            $selectAll.prop('checked', count === total && total > 0);
            $selectAll.prop('indeterminate', count > 0 && count < total);
        }

        // Bulk action apply
        $table.closest('.datatable-wrapper').on('click', '.bulk-action-apply', function() {
            const actionId = $table.closest('.datatable-wrapper').find('.bulk-action-select').val();
            if (!actionId) return;

            const selectedIds = $table.find('tbody .row-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (!selectedIds.length) return;

            if (confirm(@json(__('Apply action to')) + ' ' + selectedIds.length + ' ' + @json(__('items?')))) {
                // Dispatch custom event for bulk action
                window.dispatchEvent(new CustomEvent('datatable:bulk-action', {
                    detail: { tableId: '{{ $tableId }}', action: actionId, ids: selectedIds }
                }));
            }
        });

        // Column visibility toggle from modal
        document.querySelectorAll('#{{ $tableId }}-colvis .col-toggle').forEach(function(cb) {
            cb.addEventListener('change', function() {
                const colIdx = parseInt(this.dataset.column, 10);
                tableApi.column(colIdx).visible(this.checked);
            });
        });

        // Apply button in modal
        document.getElementById('{{ $tableId }}-colvis-apply')?.addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('{{ $tableId }}-colvis'))?.hide();
        });

        // Expose API globally
        window.DataTablesAPI = window.DataTablesAPI || {};
        window.DataTablesAPI['{{ $tableId }}'] = tableApi;

        // Export global init function
        window.initDataTable_{{ $tableId }} = function() {
            return tableApi;
        };
    }

    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDataTable);
    } else {
        initDataTable();
    }
})();
</script>
@endpush