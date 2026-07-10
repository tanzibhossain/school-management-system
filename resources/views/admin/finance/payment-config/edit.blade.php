@extends('layouts.admin')
@section('title', 'Payment config')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Payment configuration', 'crumbs' => ['Finance', 'Payment config']])

  <form method="POST" action="{{ route('admin.payment-config.update') }}">
    @csrf @method('PUT')
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card"><div class="card-header">Numbering</div><div class="card-body row g-3">
          <div class="col-md-6"><label class="form-label">Invoice prefix</label>
            <input name="invoice_prefix" class="form-control" value="{{ old('invoice_prefix', $config->invoice_prefix) }}" placeholder="e.g. INV-"></div>
          <div class="col-md-6"><label class="form-label">Receipt prefix</label>
            <input name="receipt_prefix" class="form-control" value="{{ old('receipt_prefix', $config->receipt_prefix) }}" placeholder="e.g. RCP-"></div>
          <div class="col-12"><div class="alert alert-light border py-2 mb-0 small text-muted">Current sequences — invoices: {{ $config->invoice_last_seq }}, receipts: {{ $config->receipt_last_seq }}.</div></div>
        </div></div>
      </div>
      <div class="col-lg-6">
        <div class="card"><div class="card-header">Fees</div><div class="card-body row g-3">
          <div class="col-md-6"><label class="form-label">bKash fee %</label>
            <input type="number" step="0.01" min="0" max="100" name="bkash_fee_pct" class="form-control" value="{{ old('bkash_fee_pct', $config->bkash_fee_pct) }}"></div>
          <div class="col-md-6"><label class="form-label">SSLCommerz fee %</label>
            <input type="number" step="0.01" min="0" max="100" name="sslcommerz_fee_pct" class="form-control" value="{{ old('sslcommerz_fee_pct', $config->sslcommerz_fee_pct) }}"></div>
          <div class="col-md-6"><label class="form-label">Cheque bounce fee</label>
            <input type="number" step="0.01" min="0" name="bounce_fee_amount" class="form-control" value="{{ old('bounce_fee_amount', $config->bounce_fee_amount) }}"></div>
        </div></div>
        <div class="alert alert-secondary border mt-3 small mb-0"><i class="bi bi-shield-lock"></i> Gateway credentials (bKash / SSLCommerz keys) are stored encrypted and managed separately for security.</div>
      </div>
    </div>
    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save"></i> Save configuration</button></div>
  </form>
@endsection
