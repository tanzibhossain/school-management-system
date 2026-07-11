<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'runs' ? 'active' : '' }}" href="{{ route('admin.payroll.runs.index') }}">Payroll runs</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'salaries' ? 'active' : '' }}" href="{{ route('admin.payroll.staff-salaries.index') }}">Staff salaries</a></li>
  <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'components' ? 'active' : '' }}" href="{{ route('admin.payroll.components.index') }}">Components</a></li>
</ul>
