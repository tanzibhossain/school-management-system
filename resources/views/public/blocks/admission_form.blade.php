<?php
// Admission form block partial - using pure PHP for complex logic
//
// $show/$getLabel/$isRequired are built HERE, from the plain 'standard'
// array PageRenderService::prepareAdmissionFormFields() returns — never
// pulled pre-built from $d['field_data'] itself. That array is part of a
// structure PageRenderService::renderPage() caches (Cache::remember(),
// Redis in every real environment), and Redis cannot serialize a Closure;
// embedding these closures in the cached data used to throw "Serialization
// of 'Closure' is not allowed" on every real (non-preview) page load.
$fieldData = $d['field_data'] ?? [];
$standard = $fieldData['standard'] ?? [];
$custom   = $fieldData['custom'] ?? [];
$show       = fn ($k) => ! empty($standard[$k]['enabled']);
$getLabel   = fn ($k, $default) => $standard[$k]['label'] ?? $default;
$isRequired = fn ($k) => ! empty($standard[$k]['required']);

$enabledCustom = array_filter($custom, fn($cfg) => !empty($cfg['enabled']));
?>

<?= $open ?>
  <h2 class="section-title h4 mb-2"><?= e($d['heading'] ?? __('Online admission')) ?></h2>
  <?php if (!empty($d['intro'])): ?>
    <p class="text-muted mb-3"><?= e($d['intro']) ?></p>
  <?php endif; ?>
  <?php if (session('admission_reference')): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ __('Application Submitted. Your Reference Number Is') }} <strong><?= e(session('admission_reference')) ?></strong> — please keep it safe.</div>
  <?php endif; ?>
  <?php if ($errors->any()): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors->all() as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <form method="POST" action="<?= route('admission.submit') ?>" enctype="multipart/form-data" class="card"><div class="card-body">
    <?= csrf_field() ?>
    <?php if (isset($page) && $page instanceof \App\Modules\Website\Models\Page): ?>
      {{-- Lets the server validate custom fields against this page's saved form config. --}}
      <input type="hidden" name="page_id" value="<?= (int) $page->id ?>">
    <?php endif; ?>
    {{-- Student --}}
    <h3 class="text-uppercase text-muted small fw-semibold form-section-label">{{ __('Student Information') }}</h3>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
        <input name="first_name" class="form-control" value="<?= e(old('first_name')) ?>" required></div>
      <?php if ($show('last_name')): ?>
        <div class="col-md-4">
          <label class="form-label"><?= $getLabel('last_name', __('Last name')) ?> <?php if ($isRequired('last_name')): ?><span class="text-danger">*</span><?php endif; ?></label>
          <input name="last_name" class="form-control" value="<?= e(old('last_name')) ?>" <?php if ($isRequired('last_name')) echo 'required'; ?>>
        </div>
      <?php endif; ?>
      <div class="col-md-4"><label class="form-label">{{ __('Date Of Birth') }} <span class="text-danger">*</span></label>
        <input type="date" name="dob" class="form-control" value="<?= e(old('dob')) ?>" required>
        <div class="form-text">{{ __('Must Fall Within The Age Range Set For The Selected Class.') }}</div></div>
      <div class="col-md-4"><label class="form-label">{{ __('Birth Certificate No.') }} <span class="text-danger">*</span></label>
        <input name="birth_certificate_no" class="form-control" value="<?= e(old('birth_certificate_no')) ?>" required></div>
      <div class="col-md-4"><label class="form-label">{{ __('Gender') }} <span class="text-danger">*</span></label>
        <select name="gender" class="form-select" required><option value="">—</option>
          <?php foreach(['male'=>__('Boy'),'female'=>__('Girl'),'other'=>__('Other')] as $v=>$l): ?>
            <option value="<?= e($v) ?>" <?php if (old('gender') === $v) echo 'selected'; ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">{{ __('Religion') }} <span class="text-danger">*</span></label>
        <select name="religion" class="form-select" required><option value="">—</option>
          <?php // value stays the canonical English word (stored in DB, matches
                 // the StoreAdmissionApplicationRequest 'in:' validation list and
                 // any downstream report/export) -- only the visible option TEXT
                 // is translated. ?>
          <?php foreach(['Islam','Hinduism','Buddhism','Christianity','Other'] as $r): ?>
            <option value="<?= e($r) ?>" <?php if (old('religion') === $r) echo 'selected'; ?>><?= e(__($r)) ?></option>
          <?php endforeach; ?>
        </select></div>
      <?php if ($show('blood_group')): ?>
        <div class="col-md-4">
          <label class="form-label"><?= $getLabel('blood_group', __('Blood group')) ?> <?php if ($isRequired('blood_group')): ?><span class="text-danger">*</span><?php endif; ?></label>
          <select name="blood_group" class="form-select" <?php if ($isRequired('blood_group')) echo 'required'; ?>><option value="">—</option>
            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
              <option value="<?= e($bg) ?>" <?php if (old('blood_group') === $bg) echo 'selected'; ?>><?= e($bg) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="col-md-4"><label class="form-label">{{ __('Class Applying For') }} <span class="text-danger">*</span></label>
        <select name="desired_class_id" class="form-select" required><option value="">{{ __('— Select class —') }}</option>
          <?php foreach(($d['classes'] ?? []) as $c): ?>
            <option value="<?= e($c->id) ?>" <?php if ((string)old('desired_class_id') === (string)$c->id) echo 'selected'; ?>><?= e($c->name) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">{{ __('Academic Year') }} <span class="text-danger">*</span></label>
        <select name="desired_academic_year_id" class="form-select" required><option value="">{{ __('— Select year —') }}</option>
          <?php foreach(($d['years'] ?? []) as $y): ?>
            <option value="<?= e($y->id) ?>" <?php if ((string)old('desired_academic_year_id') === (string)$y->id) echo 'selected'; ?>><?= e($y->year) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">{{ __('GPA') }} <span class="text-danger">*</span></label>
        <input name="gpa" class="form-control" value="<?= e(old('gpa')) ?>" required></div>
      <div class="col-md-8"><label class="form-label">{{ __('Previous School') }} <span class="text-danger">*</span></label>
        <input name="previous_school" class="form-control" value="<?= e(old('previous_school')) ?>" required></div>
      <?php if ($show('student_phone')): ?>
        <div class="col-md-4">
          <label class="form-label"><?= $getLabel('student_phone', __('Student phone')) ?> <?php if ($isRequired('student_phone')): ?><span class="text-danger">*</span><?php endif; ?></label>
          <input name="student_phone" class="form-control" value="<?= e(old('student_phone')) ?>" <?php if ($isRequired('student_phone')) echo 'required'; ?>>
        </div>
      <?php endif; ?>
      <?php if ($show('photo')): ?>
        <div class="col-md-8">
          <label class="form-label"><?= $getLabel('photo', __('Student photo')) ?> <?php if ($isRequired('photo')): ?><span class="text-danger">*</span><?php endif; ?></label>
          <input type="file" name="photo" accept="image/png,image/jpeg" class="form-control" <?php if ($isRequired('photo')) echo 'required'; ?>>
          <div class="form-text">{{ __('300×300 Pixels, Max 1 MB (JPG/PNG Only).') }}</div>
        </div>
      <?php endif; ?>
    </div>

    {{-- Parents --}}
    <h3 class="text-uppercase text-muted small fw-semibold form-section-label">{{ __('Parent Information') }}</h3>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label">{{ __("Father's Name") }} <span class="text-danger">*</span></label>
        <input name="father_name" class="form-control" value="<?= e(old('father_name')) ?>" required></div>
      <div class="col-md-4"><label class="form-label">{{ __("Father's Phone") }} <span class="text-danger">*</span></label>
        <input name="father_phone" class="form-control" value="<?= e(old('father_phone')) ?>" required></div>
      <div class="col-md-4"><label class="form-label">{{ __("Father's NID") }} <span class="text-danger">*</span></label>
        <input name="father_nid" class="form-control" value="<?= e(old('father_nid')) ?>" required></div>
      <div class="col-md-6"><label class="form-label">{{ __("Mother's Name") }} <span class="text-danger">*</span></label>
        <input name="mother_name" class="form-control" value="<?= e(old('mother_name')) ?>" required></div>
      <div class="col-md-6"><label class="form-label">{{ __("Mother's NID") }} <span class="text-danger">*</span></label>
        <input name="mother_nid" class="form-control" value="<?= e(old('mother_nid')) ?>" required></div>
    </div>

    {{-- Guardian --}}
    <?php if ($show('guardian')): ?>
    <h3 class="text-uppercase text-muted small fw-semibold form-section-label">{{ __('Guardian Information') }}</h3>
    <div class="row g-3 mb-3">
      <div class="col-md-3"><label class="form-label">{{ __('Guardian Type') }} <span class="text-danger">*</span></label>
        <select name="guardian_type" class="form-select" required>
          <?php foreach(['father'=>__('Father'),'mother'=>__('Mother'),'other'=>__('Other')] as $v=>$l): ?>
            <option value="<?= e($v) ?>" <?php if (old('guardian_type', 'father') === $v) echo 'selected'; ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label">{{ __('Guardian Name') }}</label>
        <input name="guardian_name" class="form-control" value="<?= e(old('guardian_name')) ?>" placeholder="{{ __('If Other Than Parent') }}"></div>
      <div class="col-md-3"><label class="form-label">{{ __('Guardian Phone') }}</label>
        <input name="guardian_phone" class="form-control" value="<?= e(old('guardian_phone')) ?>"></div>
      <div class="col-md-3"><label class="form-label">{{ __('Relationship') }}</label>
        <input name="guardian_relationship" class="form-control" value="<?= e(old('guardian_relationship')) ?>"></div>
    </div>
    <?php else: ?>
      <input type="hidden" name="guardian_type" value="father">
    <?php endif; ?>

    {{-- Address --}}
    <h3 class="text-uppercase text-muted small fw-semibold form-section-label">{{ __('Address') }}</h3>
    <div class="row g-3 mb-3">
      <div class="col-12"><label class="form-label">{{ __('Present Address') }} <span class="text-danger">*</span></label>
        <textarea name="present_address" rows="2" class="form-control" required><?= e(old('present_address')) ?></textarea></div>
      <?php if ($show('permanent_address')): ?>
      <div class="col-12">
        <div class="form-check mb-1"><input type="hidden" name="is_permanent_same" value="0"><input class="form-check-input" type="checkbox" name="is_permanent_same" value="1" id="permsame" <?php if (old('is_permanent_same')) echo 'checked'; ?>>
          <label class="form-check-label" for="permsame">{{ __('Permanent Address Same As Present') }}</label></div>
        <label class="form-label"><?= $getLabel('permanent_address', __('Permanent address')) ?> <?php if ($isRequired('permanent_address')): ?><span class="text-danger">*</span><?php endif; ?></label>
        <textarea name="permanent_address" rows="2" class="form-control" <?php if ($isRequired('permanent_address')) echo 'required'; ?>><?= e(old('permanent_address')) ?></textarea></div>
      <?php endif; ?>
      <?php if ($show('notes')): ?>
        <div class="col-12"><label class="form-label"><?= $getLabel('notes', __('Notes')) ?> <?php if ($isRequired('notes')): ?><span class="text-danger">*</span><?php endif; ?></label>
          <textarea name="notes" rows="2" class="form-control" <?php if ($isRequired('notes')) echo 'required'; ?>><?= e(old('notes')) ?></textarea></div>
      <?php endif; ?>
    </div>

    {{-- Custom fields --}}
    <?php if (!empty($enabledCustom)): ?>
    <h3 class="text-uppercase text-muted small fw-semibold form-section-label">{{ __('Additional Information') }}</h3>
    <div class="row g-3 mb-3">
        <?php foreach($enabledCustom as $key => $cfg): ?>
            <?php
                $label = $cfg['label'] ?? $key;
                $required = !empty($cfg['required']);
                $type = $cfg['type'] ?? 'text';
                $options = is_array($cfg['options'] ?? null) ? $cfg['options'] : (is_string($cfg['options'] ?? null) ? array_map('trim', explode(',', $cfg['options'])) : []);
            ?>
            <div class="col-md-6">
                <label class="form-label"><?= e($label) ?> <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?></label>
                <?php
                    if ($type === 'textarea') {
                        echo '<textarea name="custom['.$key.']" class="form-control" rows="3" '.($required ? 'required' : '').'>'.e(old('custom.'.$key)).'</textarea>';
                    } elseif ($type === 'select') {
                        echo '<select name="custom['.$key.']" class="form-select" '.($required ? 'required' : '').'><option value="">—</option>';
                        foreach ($options as $opt) {
                            echo '<option value="'.e($opt).'" '.(old('custom.'.$key) === $opt ? 'selected' : '').'>'.e($opt).'</option>';
                        }
                        echo '</select>';
                    } elseif ($type === 'number') {
                        echo '<input type="number" name="custom['.$key.']" class="form-control" value="'.e(old('custom.'.$key)).'" '.($required ? 'required' : '').'>';
                    } elseif ($type === 'date') {
                        echo '<input type="date" name="custom['.$key.']" class="form-control" value="'.e(old('custom.'.$key)).'" '.($required ? 'required' : '').'>';
                    } elseif ($type === 'file') {
                        echo '<input type="file" name="custom['.$key.']" accept="image/png,image/jpeg" class="form-control" '.($required ? 'required' : '').'>';
                    } elseif ($type === 'checkbox') {
                        echo '<div class="form-check"><input type="hidden" name="custom['.$key.']" value="0"><input class="form-check-input" type="checkbox" name="custom['.$key.']" value="1" '.(old('custom.'.$key) ? 'checked' : '').'><label class="form-check-label">'.e($label).'</label></div>';
                    } else {
                        echo '<input type="text" name="custom['.$key.']" class="form-control" value="'.e(old('custom.'.$key)).'" '.($required ? 'required' : '').'>';
                    }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <button class="btn btn-brand"><i class="bi bi-send"></i> {{ __('Submit Application') }}</button>
  </div></form>
<?= $close ?>