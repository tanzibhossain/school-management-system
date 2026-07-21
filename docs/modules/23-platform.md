# 23 — Platform (NOT IMPLEMENTED)

**Status:** ⛔ Not implemented · **Path:** none

This module was planned for a multi-tenant SaaS model — super-admin portal,
subscription plans, school provisioning, and Stripe-based self-service onboarding.
That direction was abandoned in favour of a **single-school, self-hosted**
installation (see the README note). There is no `app/Modules/Platform`
directory and no `plans`, `pending_school_signups`, or `subscription_reminders`
tables.

The platform-level concerns that remain are handled elsewhere:

| Planned concern | Where it lives now |
|---|---|
| School identity & settings | `app/Modules/School` (module 01) |
| Admin onboarding & accounts | `app/Modules/User` (module 03) — admin accounts created directly, no self-service signup flow |
| Module enable/disable toggles | `school_module_settings` table + `CheckModuleEnabled` middleware (module 01) |

This file is kept so the module numbering stays contiguous (22 → 23 → 24) and
the historical context is preserved.
