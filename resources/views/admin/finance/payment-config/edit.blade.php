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

    {{-- Online gateways available for this school's country --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h2 class="h6 mb-0 text-muted">Online gateways <span class="text-muted small">— available for your country</span></h2>
    </div>
    <div class="row g-4" id="onlineGateways" style="transition:opacity .15s;">
      @forelse($gateways as $key => $def)
        @php
          $configured = collect($def['fields'])->filter(fn ($m) => ! empty($m['required']))
              ->keys()->every(fn ($f) => filled($config->credential($key, $f)));
        @endphp
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span><i class="bi {{ $def['icon'] ?? 'bi-credit-card' }} me-1"></i>{{ $def['label'] }}
                <span class="text-muted small">({{ implode(', ', $def['currencies']) }})</span></span>
              @if($configured)<span class="badge text-bg-success">Credentials set</span>@endif
            </div>
            <div class="card-body">
              <div class="form-check form-switch mb-3">
                <input type="hidden" name="gw[{{ $key }}][enabled]" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="gw[{{ $key }}][enabled]" value="1"
                       id="{{ $key }}On" @checked($config->gatewayEnabled($key))>
                <label class="form-check-label" for="{{ $key }}On">Enable {{ $def['label'] }}</label>
              </div>
              <div class="row g-2">
                @foreach($def['fields'] as $field => $meta)
                  @php $has = filled($config->credential($key, $field)); @endphp
                  <div class="col-md-6">
                    <label class="form-label small">{{ $meta['label'] }}
                      @if(! empty($meta['required']))<span class="text-danger star-{{ $key }}" style="display:none">*</span>@endif</label>
                    <input name="gw[{{ $key }}][cred][{{ $field }}]"
                           type="{{ ! empty($meta['secret']) ? 'password' : 'text' }}"
                           class="form-control gw-field" data-gw="{{ $key }}"
                           data-req="{{ ! empty($meta['required']) ? '1' : '0' }}" data-has="{{ $has ? '1' : '0' }}"
                           value="{{ (empty($meta['secret']) && ! $has) ? old("gw.$key.cred.$field") : '' }}"
                           placeholder="{{ $has ? '•••• (unchanged)' : '' }}"
                           autocomplete="{{ ! empty($meta['secret']) ? 'new-password' : 'off' }}">
                  </div>
                @endforeach
              </div>
              <div class="row g-2 mt-1">
                <div class="col-md-4">
                  <label class="form-label small">Refund fee %</label>
                  <input type="number" step="0.01" min="0" max="100" name="gw[{{ $key }}][fee_pct]"
                         class="form-control" value="{{ old("gw.$key.fee_pct", $config->feePct($key) ?: '') }}"
                         placeholder="0">
                </div>
              </div>
              <div class="form-text mt-2">Leave a credential blank to keep the stored value. Refund fee % is deducted from refunds for this gateway.</div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12"><div class="alert alert-info mb-0">No online gateways are available for your country yet. Set your country in School settings, or an implemented gateway is required.</div></div>
      @endforelse
    </div>

    {{-- Numbering + charges --}}
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
        <div class="card"><div class="card-header">Charges</div><div class="card-body row g-3">
          <div class="col-md-6"><label class="form-label">Cheque bounce fee</label>
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

      function isOffline() {
        var p = document.querySelector('input[name="payment_mode"]:checked');
        return p && p.value === 'offline';
      }
      function gatewayOn(key) {
        var t = document.getElementById(key + 'On');
        return t && t.checked;
      }

      function sync() {
        var offline = isOffline();
        gateways.querySelectorAll('.form-check-input, input[type="hidden"]').forEach(function (el) { el.disabled = offline; });
        gateways.style.opacity = offline ? '0.5' : '1';

        document.querySelectorAll('.gw-field').forEach(function (el) {
          var on = ! offline && gatewayOn(el.dataset.gw);
          el.disabled = ! on;
          var need = on && el.dataset.req === '1' && el.dataset.has !== '1';
          el.required = need;
          var star = el.parentElement.querySelector('span[class*="star-"]');
          if (star) star.style.display = need ? '' : 'none';
        });
      }

      document.querySelectorAll('input[name="payment_mode"]').forEach(function (r) { r.addEventListener('change', sync); });
      gateways.querySelectorAll('.form-check-input').forEach(function (t) { t.addEventListener('change', sync); });
      sync();
    })();
  </script>
  @endpush
@endsection
