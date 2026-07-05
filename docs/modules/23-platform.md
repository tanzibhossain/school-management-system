# 23 — Platform

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/Platform`

## Scope
The platform module powers the super-admin portal and vendor-side school provisioning flow. It manages plans, pending school signups, subscriptions, and reminder emails for schools created through self-service or manual onboarding.

## Tables
| Table | Purpose / key columns |
|---|---|
| `plans` | platform-level subscription plans |
| `pending_school_signups` | staging records for new school signups and Stripe round-trips |
| `subscription_reminders` | reminder milestones for school subscriptions |

## API Endpoints
- Super-admin portal endpoints for plans and school provisioning
- Public signup and checkout endpoints for new school registration
- Webhook endpoints for Stripe payment events

## Services & Business Rules
- Plan caps are enforced through the platform provisioning flow and the downstream `PlanLimitService` hook.
- Stripe integration is implemented with raw HTTP calls and webhook signature validation.
- Demo schools and trial provisioning follow the documented lifecycle flow.

## Integration Points
- Provides the school lifecycle foundation for the School module.
- Integrates with User/Auth for admin onboarding and password delivery.
- Affects Student and Staff plan-cap enforcement during enrollment and hiring.
