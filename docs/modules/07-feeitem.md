# 07 — FeeItem

**Status:** ✅ Done · **Depends on:** Academic · **Path:** `app/Modules/FeeItem`

## Scope
This module defines the fee structure used by the school before billing occurs. It handles fee categories, fee items, and reusable discounts that are later consumed by the Payment module when invoices are generated.

## Tables
| Table | Purpose / key columns |
|---|---|
| `fee_categories` | fee grouping definitions with per-school uniqueness and active state |
| `fee_items` | fee lines scoped by academic year, optional class, amount, frequency, due day, and mandatory/active flags |
| `fee_discounts` | reusable discounts with percentage/fixed rules and an optional maximum amount |

## API Endpoints
Admin and accountant endpoints:
- `GET/POST/PUT/DELETE /v2/fee-categories`
- `GET/POST/PUT/DELETE /v2/fee-discounts`
- `GET/POST/PUT/DELETE /v2/fee-items`
- `POST /v2/fee-items/duplicate` to copy a fee structure from one academic year to another

## Services & Business Rules
- `FeeItemService` creates, updates, and deactivates fee items and discounts.
- `duplicateToYear()` duplicates fee structures between academic years.
- Fee items are not hard-deleted if they are already referenced by invoices; instead they are deactivated.

## Integration Points
- The Payment module builds invoices from fee items and applies discounts.
- School-level finance settings such as `fine_per_day` and `auto_due_enabled` influence due-date handling in downstream billing workflows.
