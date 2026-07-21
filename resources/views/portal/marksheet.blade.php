<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #1f2937; font-size: 12px; margin: 0; }
    .sheet { border: 2px solid #4f46e5; padding: 18px 22px; }
    .head { text-align: center; border-bottom: 1px solid #d1d5db; padding-bottom: 10px; margin-bottom: 12px; }
    .school { font-size: 20px; font-weight: bold; color: #312e81; }
    .addr { color: #6b7280; font-size: 11px; margin-top: 2px; }
    .title { display: inline-block; margin-top: 8px; background: #eef2ff; color: #4338ca; font-weight: bold;
      padding: 3px 12px; border-radius: 10px; font-size: 12px; }
    .meta { width: 100%; margin: 8px 0 12px; font-size: 12px; }
    .meta td { padding: 2px 4px; }
    .meta .lbl { color: #6b7280; width: 90px; }
    table.marks { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.marks th, table.marks td { border: 1px solid #cbd5e1; padding: 6px 8px; }
    table.marks th { background: #f1f5f9; text-align: left; font-size: 11px; }
    table.marks td.num, table.marks th.num { text-align: center; }
    .summary { width: 100%; margin-top: 14px; border-collapse: collapse; }
    .summary td { border: 1px solid #cbd5e1; padding: 8px; text-align: center; }
    .summary .k { color: #6b7280; font-size: 10px; display: block; }
    .summary .v { font-size: 15px; font-weight: bold; }
    .pass { color: #15803d; } .fail { color: #b91c1c; }
    .foot { margin-top: 40px; }
    .sign { display: inline-block; width: 45%; text-align: center; color: #6b7280; font-size: 11px; }
    .sign .line { border-top: 1px solid #9ca3af; margin-top: 28px; padding-top: 3px; }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="head">
      <div class="school">{{ optional($school)->name ?? 'School' }}</div>
      @if($school && $school->address)<div class="addr">{{ $school->address }}</div>@endif
      <div class="title">{{ $exam->title ?? 'Examination' }} — Academic Report</div>
    </div>

    <table class="meta">
      <tr>
        <td class="lbl">{{ __('Student') }}</td><td><strong>{{ $student->name }}</strong></td>
        <td class="lbl">{{ __('Class') }}</td><td>{{ optional($exam->schoolClass)->name ?? (optional($enrollment)->schoolClass->name ?? '—') }}@if($enrollment) · Sec {{ $enrollment->section->name ?? '' }}@endif</td>
      </tr>
      <tr>
        <td class="lbl">{{ __('Admission No.') }}</td><td>{{ $student->admission_number }}</td>
        <td class="lbl">{{ __('Roll') }}</td><td>{{ optional($enrollment)->roll_number ?? '—' }}</td>
      </tr>
    </table>

    <table class="marks">
      <thead>
        <tr>
          <th>{{ __('Subject') }}</th>
          <th class="num">{{ __('Marks') }}</th>
          <th class="num">{{ __('Full') }}</th>
          <th class="num">%</th>
          <th class="num">{{ __('Grade') }}</th>
          <th class="num">{{ __('GPA') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach(($result->subject_breakdown ?? []) as $u)
          <tr>
            <td>{{ $u['subject_name'] ?? '' }}@if(!empty($u['is_optional'])) <em style="color:#6b7280">(optional)</em>@endif</td>
            <td class="num">{{ $u['display_mark'] ?? ($u['obtained'] ?? '—') }}</td>
            <td class="num">{{ $u['possible'] ?? '—' }}</td>
            <td class="num">{{ isset($u['percentage']) ? number_format($u['percentage'], 1) : '—' }}</td>
            <td class="num">{{ $u['grade'] ?? '—' }}</td>
            <td class="num">{{ isset($u['gpa_point']) ? number_format($u['gpa_point'], 2) : '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <table class="summary">
      <tr>
        <td><span class="k">{{ __('Total Marks') }}</span><span class="v">{{ rtrim(rtrim(number_format($result->total_marks, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($result->total_possible, 2), '0'), '.') }}</span></td>
        <td><span class="k">{{ __('Percentage') }}</span><span class="v">{{ number_format($result->percentage, 2) }}%</span></td>
        <td><span class="k">{{ __('GPA') }}</span><span class="v">{{ $result->gpa !== null ? number_format($result->gpa, 2) : '—' }}</span></td>
        <td><span class="k">{{ __('Grade') }}</span><span class="v">{{ $result->grade ?? '—' }}</span></td>
        <td><span class="k">{{ __('Merit') }}</span><span class="v">{{ $result->merit_position ?? '—' }}</span></td>
        <td><span class="k">{{ __('Result') }}</span><span class="v {{ $result->is_pass ? 'pass' : 'fail' }}">{{ $result->is_pass ? 'PASS' : 'FAIL' }}</span></td>
      </tr>
    </table>

    <div class="foot">
      <span class="sign"><span class="line">{{ __('Class Teacher') }}</span></span>
      <span class="sign" style="float:right"><span class="line">{{ __('Head Teacher') }}</span></span>
    </div>
  </div>
</body>
</html>
