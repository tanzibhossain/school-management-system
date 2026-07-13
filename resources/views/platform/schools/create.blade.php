@extends('platform.layout')
@section('title', 'Provision school')
@section('content')
  <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">Platform</li><li class="breadcrumb-item"><a href="{{ route('platform.schools.index') }}" class="text-decoration-none">Schools</a></li><li class="breadcrumb-item active">Provision</li></ol></nav>
  <h1 class="h4 mb-3">Provision school (offline / manual)</h1>

  <div class="row"><div class="col-lg-8">
    <div class="card"><div class="card-body">
      <p class="text-muted small">Creates the school and its first admin together. No password is sent — the admin
        receives a signed “set your password” link by email. Use this for offline-paid accounts (Stripe self-serve
        signups provision themselves via webhook).</p>
      <form method="POST" action="{{ route('platform.schools.store') }}" class="row g-3">
        @csrf
        <div class="col-md-8"><label class="form-label">School name <span class="text-danger">*</span></label>
          <input name="school_name" class="form-control" value="{{ old('school_name') }}" required></div>
        <div class="col-md-4"><label class="form-label">Subdomain <span class="text-danger">*</span></label>
          <div class="input-group"><input name="subdomain" class="form-control" value="{{ old('subdomain') }}" required>
            <span class="input-group-text">.yourapp.com</span></div>
          <div class="form-text">lowercase, letters/numbers/dashes.</div></div>

        <div class="col-md-6"><label class="form-label">Admin name <span class="text-danger">*</span></label>
          <input name="admin_name" class="form-control" value="{{ old('admin_name') }}" required></div>
        <div class="col-md-6"><label class="form-label">Admin email <span class="text-danger">*</span></label>
          <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required></div>

        <div class="col-md-4"><label class="form-label">Country code</label>
          <input name="country_code" class="form-control" maxlength="2" placeholder="BD" value="{{ old('country_code') }}"></div>
        <div class="col-md-4"><label class="form-label">Plan <span class="text-danger">*</span></label>
          <select name="plan_id" class="form-select" required>
            <option value="">Choose…</option>
            @foreach ($plans as $p)
              <option value="{{ $p->id }}" @selected(old('plan_id')==$p->id)>{{ $p->name }}</option>
            @endforeach
          </select></div>
        <div class="col-md-4"><label class="form-label">Subscription expires <span class="text-danger">*</span></label>
          <input type="date" name="subscription_expires_at" class="form-control" value="{{ old('subscription_expires_at') }}" required></div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">Provision &amp; email link</button>
          <a href="{{ route('platform.schools.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div></div>
  </div></div>
@endsection
