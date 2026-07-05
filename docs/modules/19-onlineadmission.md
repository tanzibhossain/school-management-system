# 19 — OnlineAdmission

**Status:** ✅ Done · **Depends on:** Academic, Student · **Path:** `app/Modules/OnlineAdmission`

## Scope
This module supports public admission applications and their approval workflow. Applicants can submit an application and later check its status using a reference number and phone number.

## Tables
| Table | Purpose / key columns |
|---|---|
| `admission_applications` | public admission submissions and lifecycle status |

## API Endpoints
- Public: `POST /v2/admission-applications`
- Public status check endpoint using reference and phone number
- Admin approval workflow endpoints

## Services & Business Rules
- Public applications are stored as admission records and can be approved later.
- Approval uses `StudentService::enrol()` to create the student record.
- The workflow is designed to be idempotent and status-driven.

## Integration Points
- Depends on Academic for class selection and Student for enrollment creation.
- Works as a public front-door to the student onboarding flow.
