<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'runs' ? 'active' : '' }}" href="{{ route('admin.payroll.runs.index') }}">{{ __('Payroll Runs') }}</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'salaries' ? 'active' : '' }}" href="{{ route('admin.payroll.staff-salaries.index') }}">{{ __('Staff Salaries') }}</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'components' ? 'active' : '' }}" href="{{ route('admin.payroll.components.index') }}">{{ __('Components') }}</a></li>
</ul>
