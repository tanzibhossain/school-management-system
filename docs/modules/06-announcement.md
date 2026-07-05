# 06 — Announcement

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/Announcement`

## Scope
This module manages school-wide announcements and notices. It supports audience targeting, publication windows, pinning, attachments, and per-user read tracking.

## Tables
| Table | Purpose / key columns |
|---|---|
| `announcements` | notice content and metadata such as type, audience, priority, publish and expiry dates, pinning, and creator |
| `announcement_targets` | optional narrowing of an announcement to a class or section |
| `announcement_attachments` | file attachments stored via the configured filesystem |
| `announcement_reads` | read receipts tracking who has opened a notice |

## API Endpoints
- Portal users: `GET /v2/announcements/feed` and `POST /v2/announcements/{id}/read`
- Admin users: full CRUD plus `POST /v2/announcements/{id}/publish`, `POST /v2/announcements/{id}/schedule`, and `POST /v2/announcements/{id}/expire`

## Services & Business Rules
- `AnnouncementService` creates announcements with targets and attachments.
- Publish, schedule, and expiry actions update the effective lifecycle state.
- The feed respects visibility rules, publish/expiry windows, and pinning.
- Audience filtering is role-aware and can be narrowed to specific classes or sections.

## Integration Points
- The Website module surfaces published announcements on the public school site.
- The feed is consumed by authenticated users based on their role and school context.
