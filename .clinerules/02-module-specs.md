# Detailed Module Specs

Load this file when working on Attendance, Mark, or Platform specifically (or reusing their patterns). For
everything else, `01-build-status.md`'s one-line notes are usually enough.

## Attendance Module Spec (Module 10)
- Student attendance: once-daily enum `present|absent|late|half_day|leave`, bulk upsert per class/section,
  unique (school_id, student_id, date).
- Staff attendance: punch-based (check_in/check_out, `source` enum(manual|rfid), `is_auto_closed`).
- Auto clock-out: scheduled job after each school's closing time (school timezone) — check_out = closing time
  from `school_opening_hours`, NEVER job-run time. Policy: close_at_closing_time (default) | max_shift_hours | off.
- Tables: `student_attendances`, `staff_attendances`, `attendance_settings` (auto_close_policy,
  max_shift_hours, edit_window_days, late_threshold_minutes).
- Attendance % denominator = working days in enrollment period; retroactive closure = "void day" excludes a
  date from all % calcs.
- Corrections: editable within `edit_window_days` (default 7) by recording teacher; older = admin only, audited.
- Leave integration: approved leave auto-sets `leave`, overriding `absent` only.
- Mark integration: attendance-division marks SNAPSHOTTED at entry time — later edits never change results.
- RFID: first punch = check_in, last = check_out. Dates always school-local. No cache on writes.

## Mark Module Spec (Module 11)
- Grade defaults (bd_national): A+ 80–100(5.00), A 70–79(4.00), A− 60–69(3.50), B 50–59(3.00), C 40–49(2.00),
  D 33–39(1.00), F 0–32(0.00).
- Divisions per exam subject (not per class): `mark_divisions` (exam_id, exam_subject_id, name, max_marks,
  pass_mark, display_order).
- Tables: `mark_divisions`, `mark_settings` (class_id, mode enum(mark|grade), result_strategy),
  `grade_boundaries`, `marks` (marks_obtained, is_absent, entered_by, locked_at), `exam_results` (total_marks,
  percentage, grade, gpa, is_pass, merit_position, is_locked).
- `exam_results` written on calculation, locked after Moderator approval — never recompute a locked result.
  Tabulation cached under `Cache::tags(['tabulation'])`.
- Must support: absent≠zero ("Ab"), optional/4th-subject GPA bonus (bd_national: GPA=(Σcompulsory GP +
  max(0,optional GP−2.00))/compulsory count, cap 5.00), combined subjects (shared pass mark), merit tie-break
  (GPA→total→percentage, failed ranked after passed), N/A for non-enrolled.
- Division/exam-weight templates are seed data (`config/grading.php`), not code.
- Grace marks: separate audited `grace_marks` column (never mixed into `marks_obtained`), per-school cap.
- No cache on mark writes.

## Platform Module Spec (Module 23)
Added outside the original 25-module list — powers the marketing site's "buy a package → pay → get logged in"
flow (a Super Admin Portal the DevPlan had but CLAUDE.md silently dropped). **Platform-level, not
tenant-scoped** — Super Admin sees every school; public signup/checkout run before a school exists.

Plans (seed data in `config/platform.php`, editable by Super Admin, placeholders not final pricing):
| Plan | Price | Caps | Notes |
|---|---|---|---|
| Demo | Free, not purchasable | 20 students/10 staff | ONE shared `is_demo=true` school; prefilled public login page; resets every 14h |
| Trial | Free, 30 days | 100/15 | Self-serve, no payment |
| Basic | $19/mo, $190/yr | 500/40 | Stripe Checkout |
| Pro | $49/mo, $490/yr | Unlimited | Stripe Checkout |

Schema: `plans` (platform-level), `schools` gets `subdomain`, `plan_id` (nullable=legacy/uncapped),
`trial_ends_at`, `subscription_expires_at`, `is_demo`, `provisioning_type` enum(self_service/offline_manual/
super_admin), `stripe_customer_id`, `stripe_subscription_id`, `subscription_status`. `pending_school_signups`
(platform-level staging row for the Stripe round-trip, same pattern as `AdmissionApplication` but
webhook-triggered). `subscription_reminders` (school-scoped, milestone enum(day_7/day_1), unique per school+
milestone — idempotent).

Key decisions: Stripe globally for vendor billing (separate from Payment module's per-school student
gateways); Super Admin can also create offline-paid schools with explicit expiry + reminders; credentials
delivered via signed "set your password" link, never plaintext; Demo replaces any "request a demo" form
entirely; plan caps ENFORCED via `PlanLimitService` hook in `StudentService::enrol()`/`StaffService::hire()`
(schools with `plan_id=null` never capped); `role:super_admin` middleware (real Spatie role check) gates the
Super Admin portal — NOT `ability:super_admin:*`, since `admin`/`super_admin` both carry a bare Sanctum `'*'`
which would satisfy any ability check. Stripe integration is raw Http-facade calls (no SDK), webhook signature
via `hash_hmac('sha256', ...)`. Provisioning idempotent on webhook retry.

Known gaps: no `DemoSchoolSeeder` (demo school's academic structure + fixed-password admin must be created
once, manually or via a seeder not yet written); demo reset (`platform:demo-reset`, runs ~every 14h via
00:00/14:00 cron) only wipes/reseeds Student/Staff; subscription reminders email only the first admin found
per school; pricing/caps are placeholders (no currency conversion).
