# 25 — Transport

**Status:** ✅ Done · **Depends on:** Student, Payment (FeeItem, Invoice), Sms, Academic · **Path:** `app/Modules/Transport`

Optional module (gated behind `module.enabled:transport`). Full design rationale and decisions are in
[`25-transport-spec.md`](./25-transport-spec.md); this page reflects what shipped.

## Scope
School-bus operations: routes (a named path with a monthly fare, a serving vehicle, and a driver), a vehicle fleet with an operational status, drivers as their own records, and student→route assignments. A vehicle serves a whole route, so riders inherit its vehicle and a swap moves everyone at once.

## Tables
| Table | Purpose / key columns |
|---|---|
| `transport_vehicles` | fleet; `capacity`, `status` enum(`available`,`in_service`,`out_of_service`) — `available` is the pool |
| `transport_drivers` | driver records; `name`, `phone`, `license_no`, `status` |
| `transport_routes` | `name`, `fare`, `fee_item_id`, `academic_transport_id`, `current_vehicle_id`, `driver_id` |
| `student_transport_assignments` | `student_id`, `transport_route_id`, `starts_on`, `ends_on`, `status` enum(`active`,`ended`) |
| `fee_items` (+col) | `transport_route_id` — marks a fee item as a route's transport charge |
| `sms_batches` / `sms_logs` (enum) | `purpose` widened to include `transport_alert` |

## API Endpoints
- Admin CRUD for routes, vehicles (with `?status=available` pool filter + `PATCH /vehicles/{id}/status`), and drivers.
- `PUT /routes/{id}/vehicle` (attach/detach) and `POST /routes/{id}/swap-vehicle` (breakdown swap).
- Admin + receptionist: student assignment `POST`/`GET`/`PATCH .../end`.
- Admin + accountant: `GET /routes/{id}/roster`.

## Services & Business Rules
- **A vehicle serves a route; the driver stays with the route.** A swap marks the broken vehicle `out_of_service`, promotes an admin-chosen pool vehicle to `in_service`, and repoints the route — all in one `DB::transaction` with `lockForUpdate`, rejecting a replacement whose `capacity < current riders`. The driver is untouched.
- **Seat capacity, single-active-per-student, and the no-vehicle guard** are all enforced under the vehicle row lock (the Library-audit shared-counter lesson applied up front). A route with no operational vehicle blocks new assignments with a 422 rather than a stored error state.
- **"Expired" is derived, never stored** — `status='active' AND ends_on < today` (`scopeExpired`); only `active`/`ended` are persisted.
- **Billing** — creating a route auto-creates a non-mandatory `FeeItem` (current academic year); `InvoiceService` includes that fee **only** for students with an active assignment to the route (a guarded query, so it never leaks to the whole class). Fare changes update future invoices, never issued `invoice_items` snapshots.
- **Notifications** — a swap SMS-alerts every active rider through the Sms module's new `transport_alert` purpose, reaching **both** the student and the primary guardian (`sendAndLogDual`, student number best-effort), dispatched after the swap commits.

## Integration Points
- Depends on Student (riders), Payment/FeeItem (billing), Sms (swap alerts).
- Academic's `transports` reference table is retained for the public site; a route's canonical `fare` syncs one-way down to the linked `transports.fee` (no core→optional dependency, no table drop).
