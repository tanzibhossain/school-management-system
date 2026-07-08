# 26 — Messaging

**Status:** ✅ Done · **Depends on:** User · **Path:** `app/Modules/Messaging`

Optional module (gated behind `module.enabled:messaging`) and the **final backend module** — with it, the
26-module backend is complete. Full design rationale is in
[`26-messaging-spec.md`](./26-messaging-spec.md); this page reflects what shipped. Self-contained: no
shared-file edits, no provider changes.

## Scope
Two-way, in-app, threaded messaging between users of a school — distinct from Announcement (one-way broadcast)
and Sms (external, to phones). 1:1 and group conversations, with read tracking, attachments, and admin
oversight.

## Tables
| Table | Purpose / key columns |
|---|---|
| `message_threads` | `type` enum(`direct`,`group`), `subject`, `direct_key` (unique per school — 1:1 dedupe), `created_by`, `last_message_at`, `is_locked` |
| `message_participants` | `thread_id`, `user_id`, `last_read_message_id`, `left_at` — unique(thread,user) |
| `messages` | `thread_id`, `sender_id`, `body`, soft-deletes |
| `message_attachments` | `message_id`, `file_path` (MinIO), `original_name`, `mime_type`, `size_bytes` |

## API Endpoints
- Participant surface (`auth:sanctum` + `module.enabled:messaging`): inbox, start thread, view thread, add/remove participants, list/send messages (`?after={id}` incremental), mark read, delete own message, attachment download, global unread count.
- Admin moderation (`role:admin`): list all school threads, read any thread, lock/unlock.

## Services & Business Rules
- **`MessagingPolicyService`** — the guardrails. Non-staff users (`student`, `parent`) may only share a thread with staff (`admin`/`super_admin`/`teacher`/`accountant`/`librarian`/`receptionist`); every thread must always keep at least one staff participant. Blocks parent↔parent, student↔student, and staffless groups while allowing all legitimate staff↔family conversations, 1:1 or group. Enforced on create and add-participant; removal can't drop the last staff member.
- **1:1 dedupe by schema** — direct threads carry a canonical `direct_key` (`"{minId}:{maxId}"`) with a unique index, so re-messaging the same person reuses the existing thread (idempotent, race-safe).
- **Delivery is REST polling.** Clients poll the inbox and `?after={id}`; the server tracks each participant's `last_read_message_id`. Unread counts are computed live (never cached). Schema is delivery-agnostic — websockets can be layered on later with no migration.
- **Attachments** go to MinIO (mirroring the Announcement pattern); download is gated to participants or admins. Messages are soft-deleted (sender deletes own; thread order preserved).
- **Admin oversight uses `role:admin`** (real Spatie role, not `ability:*`) — admins read and lock but are never auto-participants and never inject messages.

## Integration Points
- Depends only on User (Spatie roles drive the policy).
- Fires a `MessageSent` event on every send — the clean seam for the deferred SMS/in-app unread notification and for realtime broadcasting, neither of which is in v1.
