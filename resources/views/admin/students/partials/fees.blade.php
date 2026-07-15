{{-- Fees Tab --}}
<div class="row g-4">
    {{-- Fee Summary --}}
    <div class="col-xl-4">
        <x-card title="Fee Overview" subtitle="Current academic year">
            <div class="row g-3 text-center mb-3">
                <div class="col-4">
                    <div class="p-3 bg-danger-light text-danger rounded">
                        <div class="h3 fw-bold mb-0">{{ number_format($feeSummary->total_due ?? 0, 0) }}</div>
                        <div class="text-xs text-muted">Total Due</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 bg-success-light text-success rounded">
                        <div class="h3 fw-bold mb-0">{{ number_format($feeSummary->total_paid ?? 0, 0) }}</div>
                        <div class="text-xs text-muted">Total Paid</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 bg-warning-light text-warning rounded">
                        <div class="h3 fw-bold mb-0">{{ number_format($feeSummary->balance ?? 0, 0) }}</div>
                        <div class="text-xs text-muted">Balance</div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-grid gap-2">
                <a href="{{ route('admin.invoices.generate-single', ['student' => $student->id]) }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-plus me-1"></i> Generate Invoice
                </a>
                <a href="{{ route('admin.payments.create', ['student' => $student->id]) }}" class="btn btn-outline-primary">
                    <i class="bi bi-credit-card me-1"></i> Record Payment
                </a>
                <a href="{{ route('admin.student-credit.index', ['student' => $student->id]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-wallet2 me-1"></i> Credit Ledger
                </a>
            </div>
        </x-card>
    </div>

    {{-- Invoices List --}}
    <div class="col-xl-8">
        <x-card title="Invoices" subtitle="All invoices for this student">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Period</th>
                            <th class="text-end">Amount Due</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="fw-medium">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $invoice->month }} {{ $invoice->academic_year_id ? $invoice->academicYear->year : '' }}</td>
                                <td class="text-end fw-medium">{{ number_format($invoice->total_amount, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($invoice->paid_amount, 2) }}</td>
                                <td class="text-end {{ $invoice->balance > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                    {{ number_format($invoice->balance, 2) }}
                                </td>
                                <td>{{ $invoice->due_date->format('M j, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'cancelled' ? 'danger' : ($invoice->balance > 0 && $invoice->due_date < now() ? 'danger' : 'warning')) }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('invoices.show')
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @if($invoice->balance > 0)
                                        <a href="{{ route('admin.payments.create', ['invoice' => $invoice->id]) }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No invoices found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Payment History --}}
    <div class="col-12">
        <x-card title="Payment History" subtitle="All payments made">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Receipt #</th>
                            <th>Invoice</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Collected By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_at->format('M j, Y H:i') }}</td>
                                <td>{{ $payment->receipt_number }}</td>
                                <td>{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                <td class="text-end fw-medium text-success">{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $payment->method }}</span>
                                </td>
                                <td>{{ $payment->collectedBy->name ?? '—' }}</td>
                                <td class="text-end">
                                    @can('payments.show')
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No payments found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>