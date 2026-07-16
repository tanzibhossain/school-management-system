@extends('layouts.admin')
@section('title', 'Payment settings')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Payment settings', 'crumbs' => ['Settings', 'Payment']])

  <form method="POST" action="{{ route('admin.payment-config.update') }}" autocomplete="off">
    @csrf @method('PUT')

    {{-- How fees are collected --}}
    <div class="card mb-4">
      <div class="card-header">How do you collect fees?</div>
      <div class="card-body">
        <div class="row g-2">
          @foreach([
            'offline' => ['Offline only', 'Cash, cheque and bank transfer recorded at the office.'],
            'online'  => ['Online only', 'Families pay through a payment gateway.'],
            'both'    => ['Offline & online', 'Accept both — families can pay online or at the office.'],
          ] as $val => $meta)
            <div class="col-md-4">
              <label class="border rounded p-3 d-block h-100 {{ old('payment_mode', $config->payment_mode) === $val ? 'border-primary' : '' }}" style="cursor:pointer;">
                <div class="form-check mb-1">
                  <input class="form-check-input" type="radio" name="payment_mode" value="{{ $val }}" @checked(old('payment_mode', $config->payment_mode) === $val)>
                  <span class="fw-semibold">{{ $meta[0] }}</span>
                </div>
                <div class="text-muted small">{{ $meta[1] }}</div>
              </label>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Online gateways — disabled when the school is offline-only --}}
    <div class="row g-4" id="onlineGateways" style="transition:opacity .15s;">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>bKash</span>
            @if($config->bkash_app_key)<span class="badge text-bg-success">Credentials set</span>@endif
          </div>
          <div class="card-body">
            <div class="form-check form-switch mb-3">
              <input type="hidden" name="bkash_enabled" value="0">
              <input class="form-check-input" type="checkbox" role="switch" name="bkash_enabled" value="1" id="bkashOn" @checked(old('bkash_enabled', $config->bkash_enabled))>
              <label class="form-check-label" for="bkashOn">Enable bKash</label>
            </div>
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label small">App key <span class="text-danger star-bkash" style="display:none">*</span></label>
                <input name="bkash_app_key" class="form-control gw-field" data-req="1" data-gw="bkash" data-has="{{ $config->bkash_app_key ? '1' : '0' }}" placeholder="{{ $config->bkash_app_key ? '•••• (unchanged)' : '' }}" autocomplete="off"></div>
              <div class="col-md-6"><label class="form-label small">App secret <span class="text-danger star-bkash" style="display:none">*</span></label>
                <input name="bkash_app_secret" type="password" class="form-control gw-field" data-req="1" data-gw="bkash" data-has="{{ $config->bkash_app_secret ? '1' : '0' }}" placeholder="{{ $config->bkash_app_secret ? '•••• (unchanged)' : '' }}" autocomplete="new-password"></div>
              <div class="col-md-6"><label class="form-label small">Username <span class="text-danger star-bkash" style="display:none">*</span></label>
                <input name="bkash_username" class="form-control gw-field" data-req="1" data-gw="bkash" data-has="{{ $config->bkash_username ? '1' : '0' }}" placeholder="{{ $config->bkash_username ? '•••• (unchanged)' : '' }}" autocomplete="off"></div>
              <div class="col-md-6"><label class="form-label small">Password <span class="text-danger star-bkash" style="display:none">*</span></label>
                <input name="bkash_password" type="password" class="form-control gw-field" data-req="1" data-gw="bkash" data-has="{{ $config->bkash_password ? '1' : '0' }}" placeholder="{{ $config->bkash_password ? '•••• (unchanged)' : '' }}" autocomplete="new-password"></div>
              <div class="col-12"><label class="form-label small">Base URL</label>
                <input name="bkash_base_url" class="form-control gw-field" data-gw="bkash" value="{{ old('bkash_base_url', $config->bkash_base_url) }}" placeholder="sandbox or production URL"></div>
            </div>
            <div class="form-text mt-2">Leave a field blank to keep the stored value. Fee: {{ $config->bkash_fee_pct }}%.</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>SSLCommerz</span>
            @if($config->sslcommerz_store_id)<span class="badge text-bg-success">Credentials set</span>@endif
          </div>
          <div class="card-body">
            <div class="form-check form-switch mb-3">
              <input type="hidden" name="sslcommerz_enabled" value="0">
              <input class="form-check-input" type="checkbox" role="switch" name="sslcommerz_enabled" value="1" id="sslOn" @checked(old('sslcommerz_enabled', $config->sslcommerz_enabled))>
              <label class="form-check-label" for="sslOn">Enable SSLCommerz</label>
            </div>
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label small">Store ID <span class="text-danger star-ssl" style="display:none">*</span></label>
                <input name="sslcommerz_store_id" class="form-control gw-field" data-req="1" data-gw="ssl" data-has="{{ $config->sslcommerz_store_id ? '1' : '0' }}" placeholder="{{ $config->sslcommerz_store_id ? '•••• (unchanged)' : '' }}" autocomplete="off"></div>
              <div class="col-md-6"><label class="form-label small">Store password <span class="text-danger star-ssl" style="display:none">*</span></label>
                <input name="sslcommerz_store_pass" type="password" class="form-control gw-field" data-req="1" data-gw="ssl" data-has="{{ $config->sslcommerz_store_pass ? '1' : '0' }}" placeholder="{{ $config->sslcommerz_store_pass ? '•••• (unchanged)' : '' }}" autocomplete="new-password"></div>
              <div class="col-12"><label class="form-label small">Base URL</label>
                <input name="sslcommerz_base_url" class="form-control gw-field" data-gw="ssl" value="{{ old('sslcommerz_base_url', $config->sslcommerz_base_url) }}" placeholder="sandbox or production URL"></div>
            </div>
            <div class="form-text mt-2">Leave a field blank to keep the stored value. Fee: {{ $config->sslcommerz_fee_pct }}%.</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Numbering + fees --}}
    <div class="row g-4 mt-0">
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
        <div class="card"><div class="card-header">Fees &amp; charges</div><div class="card-body row g-3">
          <div class="col-md-4"><label class="form-label">bKash fee %</label>
            <input type="number" step="0.01" min="0" max="100" name="bkash_fee_pct" class="form-control" value="{{ old('bkash_fee_pct', $config->bkash_fee_pct) }}"></div>
          <div class="col-md-4"><label class="form-label">SSLCommerz fee %</label>
            <input type="number" step="0.01" min="0" max="100" name="sslcommerz_fee_pct" class="form-control" value="{{ old('sslcommerz_fee_pct', $config->sslcommerz_fee_pct) }}"></div>
          <div class="col-md-4"><label class="form-label">Cheque bounce fee</label>
            <input type="number" step="0.01" min="0" name="bounce_fee_amount" class="form-control" value="{{ old('bounce_fee_amount', $config->bounce_fee_amount) }}"></div>
        </div></div>
      </div>
    </div>

    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save"></i> Save payment settings</button></div>
  </form>

  @push('scripts')
  <script>
    (function () {
      var gateways = document.getElementById('onlineGateways');
      if (! gateways) return;
      var bkashOn = document.getElementById('bkashOn');
      var sslOn = document.getElementById('sslOn');

      function sync() {
        var picked = document.querySelector('input[name="payment_mode"]:checked');
        var offline = picked && picked.value === 'offline';
        var bkOn = bkashOn && bkashOn.checked;
        var slOn = sslOn && sslOn.checked;

        // Enable switches (+ their hidden partners) are usable only when online.
        gateways.querySelectorAll('.form-check-input, input[type="hidden"]').forEach(function (el) { el.disabled = offline; });
        gateways.style.opacity = offline ? '0.5' : '1';

        // A gateway's own fields stay disabled until that gateway's Enable switch
        // is on (and never while offline-only). Required + asterisk only when the
        // gateway is on and the credential has no stored value yet.
        document.querySelectorAll('.gw-field').forEach(function (el) {
          var gw = el.dataset.gw;
          var on = ! offline && ((gw === 'bkash' && bkOn) || (gw === 'ssl' && slOn));
          el.disabled = ! on;
          var need = on && el.dataset.req === '1' && el.dataset.has !== '1';
          el.required = need;
          var star = el.parentElement.querySelector('span[class*="star-"]');
          if (star) star.style.display = need ? '' : 'none';
        });
      }

      document.querySelectorAll('input[name="payment_mode"]').forEach(function (r) { r.addEventListener('change', sync); });
      [bkashOn, sslOn].forEach(function (t) { if (t) t.addEventListener('change', sync); });
      sync();
    })();
  </script>
  @endpush
@endsection
