# Module 26 — Messaging · Implementation Spec (for review, no code yet)

**Status:** 🟡 Spec / awaiting approval · **Depends on:** User · **Path:** `app/Modules/Messaging`
**Optional module** — gated behind `module.enabled:messaging` (already in `ModuleSetting::MODULES`). The
**last backend module** — after it, the 26-module backend is complete.

Review document. Nothing here is built. Records the decisions, exact schema, the permission policy, and the
build/test plan so implementation is a mechanical follow-through of existing conventions.

---

## 1. Decisions locked from review

1. **Permissions are role-restricted.** Non-staff users (`student`, `parent`) may only share a thread with
   staff (`admin`, `super_admin`, `teacher`, `accountant`, `librarian`, `receptionist`). No parent↔parent,
   student↔student, or parent↔other-student DMs. Enforced in a `MessagingPolicyService` (§3).
2. **1:1 and group threads.** `message_threads` + `message_participants` (2+) + `messages`. A group is just a
   thread with more participants; the policy check applies to every non-staff participant.
3. **Delivery is REST polling + unread counts** (my call — no preference given). Fits the REST-only backend
   (`BROADCAST_CONNECTION=null`, no websocket layer). Schema is delivery-agnostic, so realtime can be layered
   on later with zero migrations (§6).
4. **v1 includes file attachments and admin moderation** (my call). Attachments reuse the Announcement/MinIO
   pattern; admins get read-only oversight + thread lock for safeguarding. **The SMS/notification hook is
   deferred** — shipped later behind a `MessageSent` event, so Messaging never couples to Sms in v1 (§7).

---

## 2. What the module manages

Threads (a conversation between 2+ users of the same school), participants (with per-user read position), and
messages (append-only posts, optionally with attachments). Every authenticated user can use it; the
*who-can-talk-to-whom* guardrails live in the policy, not the route ability.

Distinct from **Announcement** (one-way broadcast, no replies) and **Sms** (external, to phones). Messaging is
two-way, in-app, threaded.

---

## 3. Permission policy — `MessagingPolicyService`

Staff set = the six staff roles above; non-staff = `student`, `parent` (via Spatie `getRoleNames()`).

**Rules, enforced on thread creation AND add-participant:**
- All participants must belong to the **same school** (`school_id`) — threads are school-scoped.
- A thread must contain **at least one staff participant** at all times.
- If the **initiator is non-staff**, every other participant must be **staff** (a parent can open a thread to
  a teacher/admin, never to another parent or a student).
- If the initiator is staff, any same-school participants are allowed (a teacher may group-message a class's
  guardians — each guardian is paired with the staff member, satisfying the "≥1 staff" rule).
- Removing participants may not drop the last staff member from a thread.

This blocks the DM graphs a school doesn't want while allowing every legitimate staff↔family and
staff↔student conversation, 1:1 or group.

---

## 4. Schema

### `message_threads`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| type | enum(`direct`,`group`) default `direct` | |
| subject | string(150) nullable | group threads only |
| direct_key | string(40) nullable | for `direct`: canonical `"{minUserId}:{maxUserId}"`; **unique(school_id, direct_key)** dedupes 1:1 threads (null for groups; MySQL allows many nulls) |
| created_by | unsignedBigInteger | user id |
| last_message_at | timestamp nullable | inbox sort key |
| is_locked | boolean default false | admin moderation — locked = no new messages |
| timestamps | | |

### `message_participants`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| thread_id | fk message_threads cascade | |
| user_id | unsignedBigInteger | |
| last_read_message_id | unsignedBigInteger nullable | read position — powers unread counts |
| last_read_at | timestamp nullable | |
| left_at | timestamp nullable | group leave; null = active |
| timestamps | | **unique(thread_id, user_id)** |

### `messages`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| thread_id | fk message_threads cascade | |
| sender_id | unsignedBigInteger | |
| body | text | |
| deleted_at | timestamp nullable | soft delete (sender may delete own; body blanked in responses, row kept for thread integrity) |
| timestamps | | index(thread_id, id) |

### `message_attachments`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| message_id | fk messages cascade | |
| file_path | string | MinIO object path (`messaging/{school}/{thread}/...`) |
| original_name | string(255) | |
| mime_type | string(100) nullable | |
| size_bytes | unsignedInteger nullable | |
| timestamps | | mirrors `announcement_attachments` |

Unread per thread = `messages.id > participant.last_read_message_id AND sender_id != me AND deleted_at IS
NULL`. Global unread = that, summed across the user's active threads — computed live (never cached).

---

## 5. Endpoints (all `auth:sanctum` + `module.enabled:messaging`)

```
# participant surface (any authenticated user; policy gates content)
GET    /v2/messaging/threads                     my threads, last_message_at desc, with per-thread unread
POST   /v2/messaging/threads                      start thread (participant_ids[], subject?, body?) — policy + direct dedupe
GET    /v2/messaging/threads/{id}                 thread + participants (must be a participant)
GET    /v2/messaging/threads/{id}/messages        paginated; ?after={id} for incremental polling
POST   /v2/messaging/threads/{id}/messages        send (body + optional attachments[]); blocked if locked / not a participant
POST   /v2/messaging/threads/{id}/read            mark read up to latest (sets last_read_message_id)
POST   /v2/messaging/threads/{id}/participants    add participant (policy-checked)
DELETE /v2/messaging/threads/{id}/participants/{userId}   remove / leave (can't drop last staff)
DELETE /v2/messaging/messages/{id}                soft-delete own message
GET    /v2/messaging/attachments/{id}             stream/download (participant or admin only)
GET    /v2/messaging/unread-count                 global unread badge

# admin moderation (role:admin — real Spatie role, NOT ability:*)
GET    /v2/messaging/admin/threads                all threads in the school
GET    /v2/messaging/admin/threads/{id}/messages  read any thread (oversight)
POST   /v2/messaging/admin/threads/{id}/lock      lock / unlock a thread
```

Admins are **not** auto-participants — oversight is read + lock only; they don't inject messages. `role:admin`
(Spatie) is used, not `ability:admin:*`, for the same reason Platform uses real roles: a bare `'*'` token
ability can't distinguish oversight from ordinary access. Every response is a `JsonResource`; every write a
`FormRequest` with `authorize()` + `rules()`.

---

## 6. Delivery — REST polling (and why not websockets in v1)

Clients poll `GET /threads` (inbox + unread) and `GET /threads/{id}/messages?after={lastId}` (incremental).
The server only stores messages and each participant's `last_read_message_id`. No persistent connection, no
broadcast process, trivial for school traffic. Websockets (Reverb) would add a server process, broadcast auth
per private channel, and testing infra the stack doesn't have — and the **schema is identical either way**, so
realtime is a pure later add-on, not a v1 requirement.

---

## 7. Concurrency, caching, events

- **Thread creation is transactional.** Create thread + participants + optional first message in one
  `DB::transaction`. For `direct` threads, the **unique(school_id, direct_key)** index makes 1:1 creation
  idempotent — two simultaneous "message this teacher" clicks can't spawn two threads; the loser catches the
  unique violation and returns the existing thread.
- **Message send** is a plain transactional write (message + attachments + `threads.last_message_at` bump).
  Not cached — message lists flush their thread's cache tag on send, unread counts are always computed live.
- **`MessageSent` event** fires on every send — the clean seam a later listener uses for the deferred
  SMS/in-app unread notification, with zero schema change.

---

## 8. Build plan — the standard 10 steps

1. Migrations: `message_threads`, `message_participants`, `messages`, `message_attachments`.
2. Models: `MessageThread`, `MessageParticipant`, `Message`, `MessageAttachment` (scopes: `forSchool`, a
   participant scope, soft-deletes on `Message`).
3. Repositories: cache-aside per aggregate (threads, messages), flushed on send/read.
4. Services: `MessagingPolicyService` (the guardrails), `ThreadService` (create + dedupe + participants),
   `MessageService` (send + attachments + read state), `MessagingModerationService` (admin oversight/lock).
5. Observers: cache flush (or in-service flush, Library-style).
6. FormRequests: `StoreThreadRequest`, `SendMessageRequest`, `AddParticipantRequest`, `MarkReadRequest`,
   `LockThreadRequest`.
7. Resources: `ThreadResource`, `ThreadListResource` (with unread), `MessageResource`,
   `ParticipantResource`, `MessageAttachmentResource`.
8. Controllers + `routes/api.php`; register default `school_module_settings` (already in `MODULES`).
9. Tests (§9).
10. Pint + docblocks.

No shared-file edits are required (Messaging depends only on User) — a clean, self-contained module.

---

## 9. Test plan (Feature)

- **Policy:** parent→teacher allowed; parent→parent, student→student, parent→other-student **blocked**;
  staff→anyone allowed; group with a staff member allowed; group with only non-staff **blocked**; removing the
  last staff participant blocked.
- **Direct dedupe:** two `POST /threads` for the same pair return the *same* thread (unique `direct_key`).
- **Send + unread:** recipient's `unread-count` rises on send and resets after `POST .../read`; sender never
  counts their own messages as unread.
- **Incremental poll:** `?after={id}` returns only newer messages.
- **Attachments:** upload stores to MinIO and lists on the message; download gated to participants/admin.
- **Moderation:** admin lists all school threads and reads a thread they're not in; **locking a thread rejects
  new messages** (422); non-admin can't hit `/admin/*`.
- **Isolation & gating:** non-participant is forbidden from a thread; cross-school access blocked;
  `module.enabled:messaging` off → 403; requires auth.
- **Soft delete:** sender deletes own message; it disappears from listings but thread ordering is intact.

---

## 10. Out of v1 (with upgrade paths)

- **Realtime/websockets** — deferred; layer Reverb broadcasting on the existing `MessageSent` event, no schema
  change.
- **SMS / in-app unread notifications** — deferred; a listener on `MessageSent` calls the Sms module (reusing
  the `transport_alert`-style pattern) or writes an in-app notification.
- **Typing indicators / presence / reactions / edit history** — out of scope; none affect the schema above.
- **Message search** — deferred; a later full-text index on `messages.body` covers it without structural
  change.
