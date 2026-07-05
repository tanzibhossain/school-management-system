# 04 — Student

**Status:** ✅ Done · **Depends on:** Academic, User · **Path:** `app/Modules/Student`

## Scope
This module covers the full student lifecycle: enrollment, academic history, guardians, documents, waitlist handling, promotion, transfer, re-admission, and transfer certificates.

## Tables
| Table | Purpose / key columns |
|---|---|
| `students` | student profile with generated IDs, admission number, status, and linked user account |
| `student_academics` | per-year academic history including year/class/section/roll number and current-status flags |
| `student_guardians` | guardians and parent links for each student |
| `student_siblings` | bidirectional sibling links |
| `student_addresses` | present/permanent address records |
| `student_documents` | uploaded student documents |
| `student_waitlists` | applicant queue and waitlist status |
| `student_subjects` | subject enrollment records used by Mark and GPA logic |
| `student_id_configs` | configurable student ID generation rules |
| `transfer_certificate_templates`, `transfer_certificates` | transfer-certificate templates and issued certificates |

## API Endpoints
- `GET/POST/PUT/DELETE /v2/students` for student CRUD
- `GET /v2/students/{studentId}/academics` and `POST /v2/students/{studentId}/academics/promote`
- `GET/POST/DELETE /v2/students/{studentId}/documents`
- `GET /v2/students/{studentId}/tcs`, `POST /v2/students/tcs/{id}/issue`, `GET /v2/students/tcs/{id}/preview`
- `GET/POST/PUT/DELETE /v2/students/tc-templates`
- `GET/POST/PUT/POST /v2/waitlist` for waitlist management
- `GET/POST /v2/settings/student-id-config`

## Services & Business Rules
- `StudentService::enrol()` is the canonical enrollment entry point and is reused by DataImport and OnlineAdmission.
- Promotion closes the previous academic record and opens a new one.
- Transfer and re-admission update status and re-admission counters.
- Student ID generation is sequence-based and can be configured per school.
- Transfer certificates are rendered via the shared PDF service and stored through the configured filesystem.

## Integration Points
- Creates invoices in Payment and contributes enrollment data to Attendance, Mark, Examination, Certificate, ID Card, SMS, Report, and OnlineAdmission.
- Optional-subject enrollment feeds GPA and mark calculations.
- Plan caps are enforced from this service through the Platform integration.
