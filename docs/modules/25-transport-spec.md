# Module 25 — Transport · Implementation Spec (for review, no code yet)

**Status:** 🟡 Spec / awaiting approval · **Depends on:** Student, Payment (FeeItem, Invoice), Sms, Academic · **Path:** `app/Modules/Transport`
**Optional module** — gated behind `module.enabled:transport` (same pattern as Payroll/LMS/Library).

Review document. Nothing here is built. Records the decisions, exact schema, Payment + Sms integrations, and
the build/test plan so implementation is a mechanical follow-through of existing conventions.

---

## 1. Decisions locked from review

1. **Billing auto-links to FeeItem/Payment** — a route's fee is a `FeeItem` tied to the route, billed only to
   students with an active assignment (§4).
2. **A vehicle serves a whole route**; students assign to the route and inherit its vehicle. A swap moves
   everyone at once (§5, §6).
3. **Driver is a separate entity and stays with the route** — a vehicle swap never changes the driver (§5).
4. **Vehicle pool + swap is admin-driven** — broken vehicle → `out_of_service`; admin picks a replacement from
   the pool (`available` **and** capacity ≥ current riders) (§6.1).
5. **Swap notifies riders by SMS to BOTH the student and the primary guardian**, via the Sms module, using a
   new **`transport_alert`** purpose (§6.2).
6. **A route with no operational vehicle blocks new assignments** — a defined interim state, not an error
   (§6.3).
7. **Academic's `transports` table is NOT dropped** — `transport_routes` becomes the billing/ops source of
   truth and syncs the public fare down to Academic (revised — §3).

---

## 2. What the module manages

Routes (named path, monthly fare, current vehicle, driver), vehicles (registration + capacity + operational
status; the non-serving ones form the **pool**), drivers (name/phone/licence/availability, assigned to
routes), and student→route assignments (seat consumption counted against the route's current vehicle).

Roles (real roles only): `admin` for route/vehicle/driver CRUD and swaps; `receptionist` for student
assignments; `accountant` gets billing-linked reads.

---

## 3. Academic `transports` table — REVISED decision + analysis

**Blast-radius analysis (actual grep).** Every backend reference to the `transports` table lives inside the
**Academic** module plus one test — nothing else reads it:

- `Models/Transport`, `TransportController` (admin CRUD), `AcademicPublicController::transports()` (public
  read), `TransportResource`, `Store/UpdateTransportRequest`, `AcademicRepository` (`getActiveTransports`, its
  cache key, and the **combined reference-data payload** it bundles with classes/shifts/versions/…),
  `Academic/routes/api.php` (public GET + `apiResource` CRUD), and `tests/Feature/Academic/AcademicTest.php`
  (asserts a `transports` key in the reference-data response). Frontend is a separate repo and may also read
  the public endpoint.

**Why the earlier "absorb & drop" is the wrong call.** Academic is core module #2; Transport is an *optional,
late* module (#25). Repointing Academic's reference-data/public read at `transport_routes` would make a core
module depend on an optional one that may be disabled — a dependency inversion. Dropping the table is also
irreversible and risks the (out-of-repo) frontend contract.

**Recommended fix — link + one-way sync, no drop, no inversion.**

- `transport_routes` (Transport module) is the **single source of truth** for fare + billing.
- A route optionally links to an Academic `transports` row via nullable `academic_transport_id`.
- On route create / fare change, `TransportRouteService` writes the fare **down** to the linked
  `transports.fee` through Academic's `Transport` model (Transport → Academic; the *correct* dependency
  direction, precedent: Payroll writing to Loan's `LoanSchedule`). Public site keeps showing the real, billed
  fare — **fare-drift eliminated** without Academic knowing Transport exists.
- Academic's `transports` table, its public endpoint, the reference-data key, and the test all stay
  **unchanged and green**. Creating a route can optionally create+link a `transports` row so the route appears
  on the public site.

Net: single source of truth, zero irreversible steps, zero core→optional coupling, zero public-contract
break. The physical table can still be retired in a much later dedicated cleanup once the frontend is
confirmed off it — but that is explicitly **not** part of this module.

---

## 4. Payment integration

`InvoiceService::generate()` pulls all active `FeeItem`s for a student's class/year. To bill transport only to
riders: add nullable **`transport_route_id`** to `fee_items`.

- `NULL` → normal fee (unchanged). `= X` → route X's charge, kept in sync by the Transport module
  (`frequency = monthly`, `is_mandatory = false`, `class_id = NULL`).

One guarded change to the fee-item query in `generate()` (shared-file edit, bundled in Transport's commits):

```php
->where(function ($q) use ($studentId) {
    $q->whereNull('transport_route_id')                       // normal fees, unchanged
      ->orWhereIn('transport_route_id', function ($sub) use ($studentId) {
          $sub->select('transport_route_id')
              ->from('student_transport_assignments')
              ->where('student_id', $studentId)->where('status', 'active');
      });
})
```

Discounts, credit, currency, numbering, the `InvoiceGenerated` event, and the `invoice_items` snapshot are
reused untouched (a later fare change never rewrites an issued invoice). Cross-year fee rollover is out of v1.

---

## 5. Schema

### `transport_routes`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| name | string(100) | |
| description | string(255) nullable | |
| fare | decimal(10,2) default 0 | **canonical**; mirrored into FeeItem + synced to `transports.fee` |
| fee_item_id | unsignedBigInteger nullable | auto-created transport FeeItem |
| academic_transport_id | unsignedBigInteger nullable | link to Academic `transports` row for public display (one-way fare sync) |
| current_vehicle_id | fk transport_vehicles nullable | serving vehicle; **null = no operational vehicle** (§6.3) |
| driver_id | fk transport_drivers nullable | stays with route across swaps |
| is_active | boolean default true | |
| timestamps | | |

### `transport_vehicles`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| registration_no | string(50) | unique per school |
| capacity | unsignedSmallInteger | locked seat counter |
| status | enum(`available`,`in_service`,`out_of_service`) default `available` | `available` = pool |
| notes | string(255) nullable | maintenance note |
| timestamps | | |

Unique `(school_id, registration_no)`; index `(school_id, status)`. A vehicle serves at most one route at a time.

### `transport_drivers`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| name | string(100) | |
| phone | string(30) nullable | |
| license_no | string(50) nullable | |
| status | enum(`active`,`on_leave`,`inactive`) default `active` | |
| timestamps | | |

### `student_transport_assignments`
| Column | Type | Notes |
|---|---|---|
| id | bigint pk | |
| school_id | fk schools cascade | |
| student_id | fk students cascade | |
| transport_route_id | fk transport_routes cascade | vehicle inherited from route |
| pickup_point | string(150) nullable | |
| starts_on | date | |
| ends_on | date nullable | null = ongoing |
| status | enum(`active`,`ended`) default `active` | "expired" derived from `ends_on` (`scopeExpired`), never stored |

At most one active assignment per student (service-enforced). Indexes `(school_id, transport_route_id, status)`,
`(school_id, student_id, status)`.

### `fee_items` (existing — one added column)
`transport_route_id` unsignedBigInteger nullable, indexed. Migration lives in the Transport module.

### `sms_batches` (existing — enum extended)
`purpose` enum gains **`transport_alert`** → `['manual','due_reminder','transport_alert']` (§6.2).

---

## 6. Vehicle status, pool, swap, notifications, interim state

### 6.1 Seat capacity + swap concurrency (audit lesson applied up front)

Assignment locks the route's vehicle and counts riders inside the lock; the swap runs in one transaction:

```php
DB::transaction(function () {
    $route = lock route;
    $old   = $route->current_vehicle_id ? lock old vehicle : null;
    $new   = lock replacement vehicle;
    assert $new->status === 'available';
    assert $new->capacity >= active rider count on route;      // no seatless students
    if ($old) $old->status = 'out_of_service';
    $new->status = 'in_service';
    $route->current_vehicle_id = $new->id;                     // driver unchanged
});
// notify AFTER commit (§6.2)
```

Without the lock, two swaps of the same route (or two assignments to the last seat) race exactly like the
Library `available_copies` oversell. No cache on these writes. Repair via
`PATCH /vehicles/{id}/status` (`out_of_service → available`) returns a vehicle to the pool.

### 6.2 Rider notification — SMS to student + primary guardian, `transport_alert`

**Finding:** the Sms module's `sendAndLog()` currently resolves **only** the primary guardian's phone, and
`students` has no phone column (a student's only number is their optional `User.phone`). So "both" requires a
small, contained Sms extension:

1. **Add `transport_alert`** to the `sms_batches.purpose` enum (ALTER migration) and a thin
   `SmsBatchService::requestTransportAlert(int $schoolId, array $studentIds, string $body, ?User $user)`
   wrapper that stamps `purpose = 'transport_alert'`.
2. **Dual-recipient resolution** for this purpose: for each rider student, collect **[primary-guardian phone,
   student `User.phone`]**, dedupe (skip if equal/empty), and emit one `SmsLog` per distinct number
   (`guardian_id` set for the guardian row, null for the student row). Student phone is **best-effort** — many
   students have no `User`/phone, so a missing student number is a silent skip, not a failed log.

The Transport swap service calls `requestTransportAlert($schoolId, $riderStudentIds, $body, $user)` **after**
the swap transaction commits, so a gateway hiccup can't roll back the vehicle change (and `SendSmsBatchJob`
already swallows its own exceptions). Segment calc, cost, and logging are reused.

### 6.3 Route briefly without an operational vehicle (defined interim state)

Between a breakdown (vehicle → `out_of_service`, `current_vehicle_id` cleared) and the admin picking a
replacement, the route has no vehicle. Rule: `StudentTransportAssignmentService::assign()` **blocks** new
assignments when `current_vehicle_id IS NULL` (or the vehicle isn't `in_service`) with a 422 *"route has no
operational vehicle."* Existing riders are retained (and were SMS'd on the breakdown); assignments resume the
moment a vehicle is attached/swapped in. This is a guard, never a stored error status.

---

## 7. Endpoints (all `auth:sanctum` + `module.enabled:transport`)

```
# routes — admin
POST/GET/PUT/DELETE /v2/transport/routes[/{id}]        create syncs FeeItem + optional transports link
PUT   /v2/transport/routes/{id}/vehicle                attach/detach serving vehicle (normal ops)
POST  /v2/transport/routes/{id}/swap-vehicle           breakdown swap → replacement + SMS
GET   /v2/transport/routes/{id}/roster                 admin + accountant

# vehicles — admin
POST/GET/PUT/DELETE /v2/transport/vehicles[/{id}]      GET ?status=available → the pool
PATCH /v2/transport/vehicles/{id}/status               e.g. out_of_service → available (repaired)

# drivers — admin
POST/GET/PUT/DELETE /v2/transport/drivers[/{id}]       GET ?status=active

# assignments — admin + receptionist
POST  /v2/transport/assignments                        seat-locked; blocked if route has no vehicle
GET   /v2/transport/assignments                        filters: route, status
PATCH /v2/transport/assignments/{id}/end
```

Every response a `JsonResource`; every write a `FormRequest` with `authorize()` + `rules()`.

---

## 8. Build plan — standard 10 steps

1. Migrations: `transport_vehicles`, `transport_drivers`, `transport_routes`,
   `student_transport_assignments`, `add transport_route_id to fee_items`, `add transport_alert to
   sms_batches.purpose`.
2. Models (scopes: `forSchool`, vehicle pool scope, derived `scopeExpired` on assignments).
3. Repositories: cache-aside per model.
4. Services: `TransportRouteService` (FeeItem sync, `transports.fee` sync, vehicle attach/detach, swap+notify),
   `TransportVehicleService`, `TransportDriverService`, `StudentTransportAssignmentService` (seat lock,
   single-active guard, no-vehicle guard).
5. Observers: cache flush.
6. FormRequests (incl. `SwapVehicleRequest`, `ChangeVehicleStatusRequest`, `AssignStudentRequest`).
7. Resources (+ collections).
8. Controllers + routes; register in `bootstrap/app.php` + default `school_module_settings`.
9. Tests (§9).
10. Pint + docblocks.

Shared-file edits — `InvoiceService`, `SmsBatchService` + `sms_batches` migration, Academic `Transport` model
write (fare sync), `bootstrap/app.php` — are committed **with** the Transport commits.

---

## 9. Test plan (Feature)

- FeeItem sync on route CRUD; assigned student's invoice gets the transport line, a classmate's doesn't; fare
  change updates future invoices + the public `transports.fee`, but not issued snapshots.
- Seat capacity enforced on assignment; **swap** promotes a pool vehicle to `in_service`, demotes old to
  `out_of_service`, repoints route, **driver unchanged**; replacement with capacity < riders rejected.
- **SMS on swap goes to student + primary guardian** under `purpose='transport_alert'` (Sms faked → assert
  two recipients, guardian always, student when `User.phone` present; deduped when equal).
- **No-vehicle guard**: assignment blocked while `current_vehicle_id` is null.
- Single active assignment per student; "expired" derived, never stored.
- Vehicle repair returns to pool; `?status=available` lists the pool.
- `module.enabled:transport` off → blocked; role matrix.
- Academic public reference-data still returns `transports` unchanged after the module lands.

---

## 10. Remaining out-of-v1 — analysis + recommendation

**Per-stop fares.** *Recommend deferring.* One fare per route covers most schools; distance/stop pricing is a
minority need. When required: a `transport_stops` child (`route_id, name, order, fare`); assignment references
a stop; billing reads stop fare with route fare as fallback. Non-breaking — `route.fare` stays the default, so
adding stops later doesn't touch existing rows.

**Driver ↔ Staff linkage.** *Recommend keeping drivers standalone in v1.* Linking drivers to `Staff` would
pull them into `PlanLimitService` staff caps, Payroll, and Attendance — a large, mostly-unwanted coupling
(school-bus drivers are frequently contractors, not payroll staff). Clean later path: a nullable
`transport_drivers.staff_id` for schools that *do* employ their drivers, with no forced dependency.

**Auto-reassignment when a driver goes `on_leave`.** *Recommend keeping it manual in v1.* Auto-swapping a
driver mirrors the vehicle pool/swap and is a reasonable symmetric enhancement, but it needs a driver-pool +
availability model and its own notification. For v1, marking a driver `on_leave` surfaces affected routes
(a filtered roster) and the admin reassigns via route update. When built later: a `swap-driver` endpoint
parallel to `swap-vehicle`, picking from `active` drivers not currently assigned, reusing the same SMS path.
