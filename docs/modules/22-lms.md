# 22 — LMS

**Status:** ✅ Done · **Depends on:** Academic, Student · **Path:** `app/Modules/LMS`

## Scope
This optional module supports learning management features such as courses, lessons, assignments, submissions, and AI-based submission checks.

## Tables
| Table | Purpose / key columns |
|---|---|
| `lms_courses` | course definitions |
| `lms_lessons` | course lesson content |
| `lms_assignments` | assignments attached to lessons or courses |
| `lms_submissions` | student assignment submissions |
| `lms_submission_ai_checks` | AI evaluation results for submissions |

## API Endpoints
- Course, lesson, assignment, submission, and AI-check endpoints
- Module-enabled routes controlled by the shared module-setting middleware

## Services & Business Rules
- Integrates with Anthropic for AI submission checks.
- Uses the shared `module.enabled:{name}` middleware for optional enablement.
- Submission evaluation is asynchronous and external-service based.

## Integration Points
- Depends on Academic for course structure and Student for learner context.
- Works as an optional teaching and assessment extension.
