# 10 — Attendance

**Status:** ✅ Done · **Depends on:** Student, Staff · **Path:** `app/Modules/Attendance`

## Scope
Daily attendance tracking for students and staff, including bulk student attendance, punch-based staff attendance, auto clock-out rules, and attendance percentage calculations.

## Tables
| Table | Purpose / key columns |
|---|---|
| `student_attendances` | per-student daily status (`present`, `absent`, `late`, `half_day`, `leave`) |
| `staff_attendances` | punch-based staff check-in/check-out history |
| `attendance_settings` | edit windows, late thresholds, auto-close policy, max shift hours |

## API Endpoints
- Bulk upsert endpoints for student attendance by class/section/date
- Staff attendance punch and close endpoints
- Attendance summary and correction endpoints for admins/teachers

## Services & Business Rules
- Student attendance is unique per student/date.
- Approved leave overrides an absent status into `leave`.
- Staff auto-close uses school-local closing time from opening hours.
- Retroactive closure creates a void day excluded from percentage calculations.

## Integration Points
- Used by Mark for attendance-based division logic.
- Leaves from the Leave module can affect attendance outcomes.
- Uses school timezone and opening hour data from the School module.
