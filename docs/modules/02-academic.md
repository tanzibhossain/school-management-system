# 02 — Academic

**Status:** ✅ Done · **Depends on:** School · **Path:** `app/Modules/Academic`

## Scope
This module defines the school’s academic structure: academic years, classes, sections, subjects, subject mappings, and routine scheduling. It also provides the reference data used throughout the system for admissions, teaching assignments, and examinations.

## Tables
| Table | Purpose / key columns |
|---|---|
| `academic_years` | academic-year records with `is_current` and soft-delete support |
| `classes` | class definitions and ordering weight |
| `sections` | class-level sections with optional `class_teacher_id` |
| `subjects` | subject master records |
| `subject_relations` | subject-to-class/group mapping used for enrollment and marks |
| `versions`, `shifts`, `groups`, `student_types`, `transports` | lightweight reference entities used by school workflows |
| `routine_periods`, `routine_rooms` | timetable structure for class routines |
| `class_routines` | day/period-based class timetable entries with conflict prevention |

## Models
AcademicYear, SchoolClass, Section, Subject, SubjectRelation, AcademicGroup, AcademicVersion, AcademicShift, StudentType, Transport, RoutinePeriod, RoutineRoom, ClassRoutine

## API Endpoints
- `GET /v2/public/academic/dropdowns` and related public endpoints for admissions and public school pages
- Admin endpoints under `/v2/academic` for years, classes, sections, shifts, versions, groups, transports, student-types, subjects, periods, rooms, and routines
- Special actions: `PATCH /v2/academic/years/{id}/set-current`, `GET /v2/academic/classes/{classId}/subjects`, and `POST /v2/academic/classes/{classId}/subjects/sync`

## Services & Business Rules
- `AcademicService` manages current-year selection, subject relation synchronization, and soft-delete/restore behavior.
- `RoutineSchedulingService` checks for room conflicts and section conflicts before creating a routine entry.
- The module enforces at least one current academic year per school and uses soft-trash semantics for reference data.

## Integration Points
- Student enrollment depends on academic year, class, section, and subject mappings.
- Examination, Mark, Attendance, and the public Website routine endpoints all consume this module’s data.
- `Section.class_teacher_id` links a class section to a teacher in the User/Staff system.
