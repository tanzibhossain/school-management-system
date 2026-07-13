<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Name <span class="text-danger">*</span></label>
    <input name="name" class="form-control" value="{{ old('name', $plan?->name) }}" required></div>
  <div class="col-md-6"><label class="form-label">Slug <span class="text-danger">*</span></label>
    <input name="slug" class="form-control" value="{{ old('slug', $plan?->slug) }}" required>
    <div class="form-text">Stable identifier (e.g. <code>basic</code>) — used by billing/limit logic.</div></div>

  <div class="col-md-4"><label class="form-label">Price monthly</label>
    <input type="number" step="0.01" min="0" name="price_monthly" class="form-control" value="{{ old('price_monthly', $plan?->price_monthly) }}" placeholder="blank = free"></div>
  <div class="col-md-4"><label class="form-label">Price yearly</label>
    <input type="number" step="0.01" min="0" name="price_yearly" class="form-control" value="{{ old('price_yearly', $plan?->price_yearly) }}" placeholder="blank = free"></div>
  <div class="col-md-4"><label class="form-label">Currency</label>
    <input name="currency" maxlength="3" class="form-control text-uppercase" value="{{ old('currency', $plan?->currency ?? 'USD') }}"></div>

  <div class="col-md-4"><label class="form-label">Max students</label>
    <input type="number" min="1" name="max_students" class="form-control" value="{{ old('max_students', $plan?->max_students) }}" placeholder="blank = unlimited"></div>
  <div class="col-md-4"><label class="form-label">Max staff</label>
    <input type="number" min="1" name="max_staff" class="form-control" value="{{ old('max_staff', $plan?->max_staff) }}" placeholder="blank = unlimited"></div>
  <div class="col-md-4"><label class="form-label">Trial days</label>
    <input type="number" min="1" name="trial_days" class="form-control" value="{{ old('trial_days', $plan?->trial_days) }}" placeholder="blank = none"></div>

  <div class="col-md-4"><label class="form-label">Sort order</label>
    <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $plan?->sort_order ?? 0) }}"></div>
  <div class="col-md-4 d-flex align-items-end"><div class="form-check">
    <input type="hidden" name="is_self_serve" value="0">
    <input type="checkbox" class="form-check-input" id="ss{{ $plan?->id ?? 'new' }}" name="is_self_serve" value="1" @checked(old('is_self_serve', $plan?->is_self_serve ?? false))>
    <label class="form-check-label" for="ss{{ $plan?->id ?? 'new' }}">Self-serve (public)</label></div></div>
  <div class="col-md-4 d-flex align-items-end"><div class="form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" class="form-check-input" id="act{{ $plan?->id ?? 'new' }}" name="is_active" value="1" @checked(old('is_active', $plan?->is_active ?? true))>
    <label class="form-check-label" for="act{{ $plan?->id ?? 'new' }}">Active</label></div></div>
</div>
