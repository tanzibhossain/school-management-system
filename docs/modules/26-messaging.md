# 26 — Messaging

**Status:** ⬜ Planned · **Depends on:** User · **Path:** `app/Modules/Messaging`

## Scope
Planned optional module for school communications such as internal messaging, notifications, and message threading between users.

## Planned Tables
| Table | Purpose / key columns |
|---|---|
| `messages` | conversation and message records |
| `message_threads` | grouping of related messages |
| `message_participants` | participants in a thread |

## Planned API Endpoints
- Conversation and message send/list endpoints
- Thread creation and participant management endpoints
- Notification delivery hooks

## Notes
This module is referenced in the optional module configuration and build-order plan but is not implemented in the current codebase.
