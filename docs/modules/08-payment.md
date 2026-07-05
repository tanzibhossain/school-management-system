# 08 — Payment

**Status:** ✅ Done · **Depends on:** Student, FeeItem · **Path:** `app/Modules/Payment`

## Scope
Fee collection and settlement for invoices generated from fee items. The module handles invoice creation, manual payments, gateway payments (bKash and SSLCommerz), cheque lifecycle, refunds, and student credit balances.

## Tables
| Table | Purpose / key columns |
|---|---|
| `payment_configs` | per-school payment gateway settings and fee percentages |
| `invoices` | invoice header per student/academic year/month, amounts due/paid, status, due date |
| `invoice_items` | line items generated from fee items and discounts |
| `payments` | payment records for cash, cheque, bank transfer, waiver, bKash, SSLCommerz |
| `payment_gateway_logs` | gateway request/response audit trail |
| `student_credits` | stored student wallet balance |
| `credit_transactions` | debit/credit history for student credits |
| `refunds` | refund requests and gateway refund status |

## API Endpoints

### Admin / accountant
- `GET /v2/payment-config`
- `PUT /v2/payment-config`
- `GET /v2/invoices`
- `POST /v2/invoices/generate`
- `GET /v2/invoices/{id}`
- `POST /v2/invoices/{id}/cancel`
- `POST /v2/invoices/{id}/waive`
- `POST /v2/payments/invoices/{invoiceId}/record`
- `POST /v2/payments/invoices/{invoiceId}/bkash/initiate`
- `POST /v2/payments/invoices/{invoiceId}/sslcommerz/initiate`
- `GET /v2/payments/{id}`
- `GET /v2/cheques`
- `POST /v2/cheques/{id}/clear`
- `POST /v2/cheques/{id}/bounce`
- `GET /v2/refunds`
- `POST /v2/refunds/payments/{paymentId}`
- `GET /v2/refunds/{id}`
- `GET /v2/credits/students/{studentId}`
- `GET /v2/credits/students/{studentId}/transactions`

### Student portal
- `GET /v2/my-invoices`

### Webhooks / callbacks
- `GET /v2/payments/bkash/callback`
- `POST /v2/payments/sslcommerz/ipn`
- `GET|POST /v2/payments/sslcommerz/success`
- `GET|POST /v2/payments/sslcommerz/fail`
- `GET|POST /v2/payments/sslcommerz/cancel`

## Services & Business Rules
- `InvoiceService`
  - Generates invoices for a single student or a whole class.
  - Prevents duplicate open invoices for the same student/academic year/month.
  - Builds invoice items from active fee items for the target class/year.
  - Applies fee discounts and auto-uses available student credit.
  - Supports cancellation and waiver.
- `PaymentService`
  - Records manual payments in a single database transaction.
  - Supports bKash and SSLCommerz initiation and completion verification.
  - Updates invoice status automatically (`unpaid`, `partial`, `paid`) after every payment.
  - Converts overpayments into student credit.
- `RefundService`
  - Creates refund requests for payments.
  - For gateway payments, attempts gateway refund immediately; for cash/bank methods, leaves a manual pending refund.
  - Applies processing fees based on payment config.
- `CreditService`
  - Manages student credit balance and transaction history.
  - Used for overpayment crediting and automatic invoice offset.

## Gateway Policy
- Gateway support is country-aware.
- Bangladesh schools use bKash and SSLCommerz.
- Other countries use Stripe and PayPal in the broader product policy, but this module implements the local gateway adapters for bKash and SSLCommerz.
- Each gateway declares its supported currencies and the service rejects unsupported invoice currencies before calling the gateway.

## Important Implementation Notes
- All financial writes are wrapped in database transactions.
- Invoice/payment data is returned through resources, never as raw Eloquent models.
- Invoice generation is idempotent for open invoices in the same period.
- Gateway callbacks are designed to be idempotent to avoid duplicate payment recording.
- Student credit is used before new payment amounts are due on a generated invoice.

## Integration Points
- Built from fee structure in the FeeItem module.
- Uses student records and academic year context from the Student/Academic modules.
- Payment events are emitted for invoice generation, payment recording, refund creation, cancellation, waiver, and overpayment crediting.
- The module is the billing backbone for the broader school finance workflow.
