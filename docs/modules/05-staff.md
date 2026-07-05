# 05 — Staff

**Status:** ✅ Done · **Depends on:** Academic, User · **Path:** `app/Modules/Staff`

## Scope
This module manages human-resource data for school employees: staff profiles, departments, designations, teaching assignments, termination, re-hire, and RFID-based attendance linkage.

## Tables
| Table | Purpose / key columns |
|---|---|
| `staff` | employee profile with generated employee ID, linked user account, designation, department, employment type, salary, RFID number, and status |
| `staff_academics` | per-year teaching assignments including class/section and class-teacher flag |
| `designations`, `departments` | reference data for staff categorization |
| `staff_addresses`, `staff_documents`, `staff_experiences` | profile supporting data |
| `staff_id_configs` | configurable employee ID generation rules |

## API Endpoints
- Admin CRUD for `/v2/designations` and `/v2/departments`
- Admin CRUD and transitions for `/v2/staff`, including `POST /v2/staff/{id}/terminate` and `POST /v2/staff/{id}/re-hire`
- Staff profile endpoint: `GET /v2/staff/me/profile`

## Services & Business Rules
- `StaffService::hire()` is the canonical hire entry point and is also used by DataImport.
- Teaching assignments are created through the academic assignment flow and can be removed or updated.
- Termination and re-hire update staff status and re-hire counters.
- Employee ID generation uses the same sequence-based pattern as student IDs.

## Integration Points
- Attendance uses the staff RFID number and attendance punches.
- Leave, Loan, Payroll, ID Card, Academic, and Reporting all consume staff data.
- Staff members can be linked as class teachers through teacher assignment flows.
