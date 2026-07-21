# 01 — School

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/School`

## Scope
The school record. This module owns the school identity, contact details, locale settings, opening hours, payment configuration, and optional module toggles. Every school-scoped module depends on the school context through `school_id`.

## Tables
| Table | Purpose / key columns |
|---|---|
| `schools` | core school profile: name, institution code, school code, address, email, logo, `country_code`, locale fields (`currency`, `timezone`, `locale`, `academic_year_pattern`), SMS settings, and finance flags such as `auto_due_enabled` and `fine_per_day` |
| `school_phones` | one-to-many phone numbers for a school, including a primary flag |
| `school_opening_hours` | per-day opening/closing times used by Attendance auto clock-out |
| `school_payment_settings` | per-school gateway credentials and online payment flags |
| `school_module_settings` | module enable/disable flags for optional modules such as Payroll and LMS |

## Models
School, SchoolPhone, SchoolOpeningHour, SchoolPaymentSetting, ModuleSetting

## API Endpoints
| Method & path | Notes |
|---|---|
| `GET /v2/public/school` | public profile for the school |
| `GET /v2/school`, `PUT /v2/school` | authenticated school profile read/update |
| `POST /v2/school/phones/sync` | replaces the full phone set for the school |
| `PUT /v2/school/hours/{day}` | updates one weekday’s opening hours |
| `GET /v2/school/modules`, `PUT /v2/school/modules/{module}` | admin-only toggle for optional modules |

## Services & Business Rules
- `SchoolService` handles profile updates, phone synchronization, and opening-hour changes.
- `ModuleSettingService` exposes `isEnabled()` and powers the `CheckModuleEnabled` middleware.
- Opening hours are the source of truth for Attendance auto-close behavior and should not be inferred from the job run time.

## Integration Points
- Provides locale, currency, timezone, and academic-year settings to Payment, Attendance, and Academic.
- Supplies module enablement flags for optional modules such as Payroll, LMS, Library, Transport, and Messaging.
- The school record is the central anchor for all school-scoped data.
