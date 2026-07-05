# 27 — Frontend Platform (Planned)

**Status:** ⬜ Planned · **Depends on:** All backend modules · **Path:** `apps/`

## Scope
Planned monorepo frontend layer for the product, including the marketing site, school public site, and the authenticated dashboard used by school admins, teachers, students, and guardians.

## Planned Applications
| App | Purpose | Audience |
|---|---|---|
| `apps/marketing` | vendor/public marketing site | visitors, prospects |
| `apps/school-site` | per-school public website | parents, students, general visitors |
| `apps/dashboard` | per-school authenticated dashboard | admin, teacher, student, guardian |

## Planned Roles / Experience Areas
- `admin` dashboard: school operations, finance, attendance, exams, marks, reports, users, announcements
- `teacher` dashboard: class routines, attendance, marks, leave, announcements
- `student` dashboard: profile, attendance, marks, invoices, announcements, leave requests
- `guardian` dashboard: child profile, attendance, marks, fees, announcements
- `super_admin` portal: platform-level plan and school provisioning management

## Planned Frontend Responsibilities
- Consume the Laravel API via Sanctum-authenticated sessions
- Support tenant routing by school/subdomain
- Share design system, form patterns, and API clients across apps
- Provide public pages for admissions, school site content, and product marketing

## Notes
This frontend layer is explicitly called out in the project plan but is not yet built in the current workspace. It should be treated as a major planned module with its own app-level architecture and delivery milestones.
