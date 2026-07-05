# 17 — SMS

**Status:** ✅ Done · **Depends on:** Student, Payment · **Path:** `app/Modules/Sms`

## Scope
This module handles outbound school SMS communication, including batch messaging, message logging, per-school billing, and gateway abstraction.

## Tables
| Table | Purpose / key columns |
|---|---|
| `sms_batches` | batch metadata for outbound SMS sends |
| `sms_logs` | per-message delivery and status logs |

## API Endpoints
- SMS batch create and status endpoints
- SMS log and delivery report endpoints

## Services & Business Rules
- Per-school SMS billing is tracked through school settings.
- Segment calculation follows GSM-7 and Unicode rules using `SmsSegmentCalculator`.
- All gateway calls go through a `SmsGatewayContract` implementation and a stub `LogGateway`.

## Integration Points
- Uses Student and Payment data for recipient targeting and billing context.
- Works with school-level SMS configuration such as API key, sender ID, and cost-per-segment.
