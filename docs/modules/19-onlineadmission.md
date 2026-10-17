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
- Approval uses `StudentService::enrol()` to create the student record, then carries over what the student
  record has a place for: religion, photo, the primary guardian (`is_primary`) plus father/mother when neither is
  already the primary guardian, present/permanent addresses, and custom file uploads as `other` documents. Files
  are copied to the Student module's `minio` disk before the transaction and deleted again if approval fails.
  Birth certificate no., NIDs, previous school, GPA and custom text answers stay on the application
  (`created_student_id` links the two; both admin pages link to each other).
- The workflow is designed to be idempotent and status-driven.

## Website admission form
- The Website `admission_form` block posts to `POST /admission` with the id of the page it was rendered on (`page_id`).
- The server loads that published page's form config (`PageRenderService::admissionFormFields()`) and builds
  `AdmissionFormSchema` from it: rules for each enabled custom field (by type; select = one of its options,
  file = JPG/PNG ≤ 1 MB, required checkbox = must be ticked) plus the admin's Required switches on the optional
  standard fields. Config never comes from the request; with no valid `page_id` only standard fields are accepted.
- Answers are stored in `form_data.custom` as `{key: {label, type, value}}`, keeping the label as submitted.
- The photo and custom file uploads go on the private `local` disk (`admissions/{school_id}/`). Admins open them
  through `admin.admissions.file` (`role:admin`, same as the review page); the path is always read from the
  application, never the request. Photos from before this change are still read from the `public` disk.

## Integration Points
- Depends on Academic for class selection and Student for enrollment creation.
- Works as a public front-door to the student onboarding flow.
