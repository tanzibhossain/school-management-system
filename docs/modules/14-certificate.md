# 14 — Certificate

**Status:** ✅ Done · **Depends on:** Student, Mark · **Path:** `app/Modules/Certificate`

## Scope
This module generates academic certificates and related templates for students, including admit cards, testimonial templates, and transfer-related documentation. It is built around shared PDF rendering rather than Blade-based views.

## Tables
| Table | Purpose / key columns |
|---|---|
| `admit_cards` | admit card records generated for exams |
| `testimonial_templates` | reusable testimonial layout templates |
| `testimonials` | generated student testimonial records |

## API Endpoints
- Certificate generation and preview endpoints for students and admins
- Template CRUD and generation flows for testimonials and admit cards

## Services & Business Rules
- Uses the shared `App\Services\PdfRenderingService` for document generation.
- Certificate generation depends on student and mark data.
- The module is designed for generation and export rather than transactional editing.

## Integration Points
- Depends on Student and Mark for academic context and results.
- Uses the shared PDF rendering service for document output.
