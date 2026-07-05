# 11 — Mark

**Status:** ✅ Done · **Depends on:** Examination, Attendance, Student · **Path:** `app/Modules/Mark`

## Scope
Examination mark entry, grading, result processing, tabulation, and locked report generation.

## Tables
| Table | Purpose / key columns |
|---|---|
| `mark_settings` | per-class grading mode and result strategy |
| `grade_boundaries` | grade thresholds and GPA values |
| `marks` | marks entered per exam subject, including absent and grace mark metadata |
| `exam_results` | computed totals, percentages, grades, GPA, pass/fail, merit position |
| `grace_marks` | audited grace mark adjustments |

## API Endpoints
- Mark entry, update, and lock endpoints
- Result tabulation and report endpoints
- Grade boundary and mark-setting configuration endpoints

## Services & Business Rules
- Results are locked after moderator approval and must not be recomputed.
- Supports grade-based and marks-based modes.
- Handles absent marks distinctly from zero.
- Supports optional fourth-subject GPA logic and merit tie-break rules.
- Tabulation is cached under a dedicated tag.

## Integration Points
- Consumes exam definitions from Examination.
- Uses attendance snapshots from Attendance for division-based marks.
- Feeds into Certificate and Report modules.
