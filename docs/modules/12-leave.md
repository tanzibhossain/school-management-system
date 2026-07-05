# 12 — Leave

**Status:** ✅ Done · **Depends on:** Student, Staff · **Path:** `app/Modules/Leave`

## Scope
Leave type management and leave request workflow for students and staff, including approval rules and integration with attendance.

## Tables
| Table | Purpose / key columns |
|---|---|
| `leave_types` | leave categories and rules |
| `student_leave_requests` | student leave applications and approvals |
| `staff_leave_requests` | staff leave applications and approvals |

## API Endpoints
- Leave-type CRUD endpoints
- Student/staff leave request create, view, and approval endpoints

## Services & Business Rules
- Approved leave is reflected in attendance as `leave`.
- Staff approvals are restricted to admin-only workflows.
- Leave requests are tied to attendance and school calendars.

## Integration Points
- Updates attendance outcomes through the Working Day / attendance integration.
- Used by student and staff HR workflows.
