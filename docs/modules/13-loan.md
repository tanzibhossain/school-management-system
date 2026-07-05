# 13 — Loan

**Status:** ✅ Done · **Depends on:** Staff · **Path:** `app/Modules/Loan`

## Scope
Staff loan request, approval, and repayment scheduling. The module is designed to support interest-free loans with deferred repayment handling that later integrates with payroll.

## Tables
| Table | Purpose / key columns |
|---|---|
| `staff_loans` | loan request/approval metadata |
| `loan_schedules` | repayment schedule and status |

## API Endpoints
- Staff loan request and approval endpoints
- Loan schedule query endpoints

## Services & Business Rules
- Loan requests move from pending to approved state.
- Repayment tracking is deferred to payroll integration.
- The implementation is interest-free by design.

## Integration Points
- Depends on Staff for employee identities.
- Payroll can consume the loan schedule for deductions and settlement entries.
