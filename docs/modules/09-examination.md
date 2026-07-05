# 09 — Examination

**Status:** ✅ Done · **Depends on:** Academic, Student · **Path:** `app/Modules/Examination`

## Scope
Examination configuration and result management, including exam types, exam schedules, subject-level marks, hall seating, and exam result calculation.

## Tables
| Table | Purpose / key columns |
|---|---|
| `exam_types` | reusable exam category definitions |
| `exams` | exam session metadata and academic context |
| `exam_subjects` | subject-level exam configuration |
| `mark_divisions` | division/weighting rules per exam subject |
| `exam_halls` | exam venue definitions |
| `exam_hall_seats` | seat inventory for halls |
| `exam_seatings` | assigned student seating with anti-adjacency logic |

## API Endpoints
- Admin CRUD for exam configuration, exam types, halls, seating, and result setup
- Subject-level mark entry and result publication endpoints

## Services & Business Rules
- Exam setup is driven by academic year, class, and subject data.
- Seating rules enforce anti-adjacency and blank-seat policies.
- Result calculation uses stored divisions and boundaries rather than hard-coded assumptions.
- Mark entry can be marked absent without treating it as zero.

## Integration Points
- Depends on Academic for school classes, sections, and subjects.
- Depends on Student for enrolment and exam participation.
- Feed data into the Mark module for computed results and grading.
