# 03 — User/Auth

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/User`

## Scope
This module handles authentication, user accounts, role management, password workflows, and audit trails. It is the shared identity layer for the entire system and is used by nearly every other module.

## Tables
| Table | Purpose / key columns |
|---|---|
| `users` | school-scoped users with role-based access, active status, phone, avatar, and (since v1.0.1) `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `pending_email`, `pending_email_token`, `pending_email_expires_at` |
| `personal_access_tokens` | Sanctum tokens extended with `ip_address` and `user_agent` |
| `login_histories` | login attempts and successful/failed sessions for auditing; `session_id` (added v1.0.1) links a row to a live Blade session for revocation |

## API Endpoints
- Public: `POST /v2/auth/login`
- Authenticated: `GET /v2/auth/me`, `POST /v2/auth/logout`, `POST /v2/auth/logout-all`, `PUT /v2/auth/password`, `GET /v2/auth/devices`, `DELETE /v2/auth/devices/{tokenId}`, `GET /v2/auth/login-history`
- Admin user management under `/v2/admin`: list/create/show/update/delete users, change roles, and view login histories

## Self-Service Account & Security (Blade, session auth — added v1.0.1)
A shared `AccountController` + `resources/views/partials/account-settings.blade.php`
partial is mounted at `{portal}/account` in all three session-based portals
(admin, staff, family), giving every logged-in user:
- Name and password changes (password requires the current password).
- Email address changes — held in `pending_email` until confirmed via a
  signed, 24-hour link (`AccountEmailChangeMail`) sent to the new address;
  the live `email` column never changes until the link is clicked.
- Two-factor authentication (TOTP via `pragmarx/google2fa`), with a
  client-side QR code for setup and one-time recovery codes for account
  recovery. Login redirects a 2FA-enabled user through a dedicated
  `two-factor.challenge` step before completing `Auth::login()`.
- Session/device management — `SessionDeviceService` reuses the existing
  `LoginHistory` table (rather than a new one) to list every active session
  with device/browser and IP, and can revoke one session or all sessions
  except the current one. Revocation calls Laravel's own session handler
  (`session()->driver()->getHandler()->destroy()`) so it works against
  whatever session driver is configured (this app uses Redis), not a
  hand-rolled store lookup.

This is entirely separate from the Sanctum/API device list above (`AuthService`,
`/v2/auth/devices`) — that endpoint set is unaffected and still serves the
mobile/API device-management use case.

## Services & Business Rules
- `AuthService` handles login, token issuance, device tracking, and session revocation (Sanctum/API).
- `AccountService` + `SessionDeviceService` handle the session-based self-service flows above.
- `UserService` manages creation, updates, role changes, deactivation, and password changes.
- Real roles are limited to `super_admin`, `admin`, `teacher`, `accountant`, `librarian`, `receptionist`, `student`, and `parent`.
- Because Sanctum abilities can be overly broad, the system uses Spatie role middleware for privileged checks such as the Super Admin portal.
- Account onboarding (admin password links) flows through this module.

## Integration Points
- All school-scoped routes rely on the auth middleware from this module.
- Student and Staff modules create linked user accounts for portal access.
- Admin onboarding (signed password links) flows through this module.
