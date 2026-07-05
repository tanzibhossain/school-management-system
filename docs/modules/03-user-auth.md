# 03 — User/Auth

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/User`

## Scope
This module handles authentication, user accounts, role management, password workflows, and audit trails. It is the shared identity layer for the entire platform and is used by nearly every other module.

## Tables
| Table | Purpose / key columns |
|---|---|
| `users` | school-scoped users with role-based access, active status, phone, and avatar |
| `personal_access_tokens` | Sanctum tokens extended with `ip_address` and `user_agent` |
| `login_histories` | login attempts and successful/failed sessions for auditing |

## API Endpoints
- Public: `POST /v2/auth/login`
- Authenticated: `GET /v2/auth/me`, `POST /v2/auth/logout`, `POST /v2/auth/logout-all`, `PUT /v2/auth/password`, `GET /v2/auth/devices`, `DELETE /v2/auth/devices/{tokenId}`, `GET /v2/auth/login-history`
- Admin user management under `/v2/admin`: list/create/show/update/delete users, change roles, and view login histories

## Services & Business Rules
- `AuthService` handles login, token issuance, device tracking, and session revocation.
- `UserService` manages creation, updates, role changes, deactivation, and password changes.
- Real roles are limited to `super_admin`, `admin`, `teacher`, `accountant`, `librarian`, `receptionist`, `student`, and `parent`.
- Because Sanctum abilities can be overly broad, the platform uses Spatie role middleware for privileged checks such as the Super Admin portal.

## Integration Points
- All tenant-scoped routes rely on the auth middleware from this module.
- Student and Staff modules create linked user accounts for portal access.
- Platform provisioning uses this module for signed password links and admin onboarding.
