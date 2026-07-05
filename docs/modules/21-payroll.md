# 21 — Payroll

**Status:** ✅ Done · **Depends on:** Staff, Loan · **Path:** `app/Modules/Payroll`

## Scope
This module manages salary components, salary values, payroll runs, payroll entries, and salary certificate requests for school staff. It also integrates with the Loan module for deferred deductions.

## Tables
| Table | Purpose / key columns |
|---|---|
| `salary_components` | earning/deduction component definitions |
| `staff_salary_values` | per-staff component values |
| `payroll_runs` | payroll batch metadata |
| `payroll_entries` | generated salary entries per staff |
| `salary_certificate_requests` | requests for salary verification documents |

## API Endpoints
- Payroll configuration and salary-component endpoints
- Payroll run creation and entry listing endpoints
- Salary certificate request endpoints

## Services & Business Rules
- Salary components are summed flatly; attendance-based proration is not used.
- Payroll entries integrate with deferred staff loan repayments.
- The module uses the shared module-settings middleware for optional enablement.

## Integration Points
- Depends on Staff for employee identity and salary-related values.
- Integrates with Loan for repayment schedule handling.
