# 08 — Payment

**Status:** ✅ Done · **Depends on:** Student, FeeItem · **Path:** `app/Modules/Payment`

> **See also:** [`docs/payment-gateway-architecture.md`](../payment-gateway-architecture.md)
> for the gateway driver model, the `PaymentGateway` contract, and the incremental
> migration plan; and [`docs/payment-gateways-by-country.md`](../payment-gateways-by-country.md)
> for the full country→gateway reference used to decide availability.

## Scope

Fee collection and settlement for invoices generated from fee items. The module
handles invoice creation, manual payments, four gateway integrations (bKash,
SSLCommerz, Stripe, PayPal), cheque lifecycle, refunds, and student credit
balances.

## Tables

| Table | Purpose / key columns |
|---|---|
| `payment_configs` | per-school payment gateway settings — generic encrypted JSON `gateways` column keyed by slug (`{ slug: { enabled, credentials, fee_pct } }`); no per-gateway columns |
| `invoices` | invoice header per student/academic year/month, amounts due/paid, status, due date |
| `invoice_items` | line items generated from fee items and discounts |
| `payments` | payment records — `method` is a `string(30)`, governed by the registry (bKash, SSLCommerz, Stripe, PayPal, cash, bank, cheque, waiver) |
| `payment_gateway_logs` | gateway request/response audit trail; `gateway` is `string(30)` so any slug fits |
| `student_credits` | stored student wallet balance |
| `credit_transactions` | debit/credit history for student credits |
| `refunds` | refund requests and gateway refund status |

## Gateways (as shipped)

Four gateways are implemented and covered by feature tests. Availability per
school comes from `config/payment_gateways.php` (`by_country` → slugs, falling
back to `default`), filtered to gateways whose `implemented` flag is `true`.

| Slug | Driver class | Flow pattern | Currencies | Countries |
|---|---|---|---|---|
| `bkash` | `BkashGateway` | Create → authorize → execute (tokenized) | BDT | BD |
| `sslcommerz` | `SslcommerzGateway` | Hosted redirect (+ IPN) | BDT | BD |
| `stripe` | `StripeGateway` | Hosted Checkout redirect + webhook | USD, EUR, GBP, AUD, CAD, SGD, INR, AED, JPY, NZD | default (non-BD) |
| `paypal` | `PayPalGateway` | Hosted redirect (Orders v2) + webhook | USD, EUR, GBP, AUD, CAD, SGD, JPY, NZD | default (non-BD) |

### How a payment flows

```
Portal fees page
   └─ POST /portal/pay/initiate {invoice_id, gateway}
        └─ Portal\PaymentController::initiate()
             ├─ guards: online enabled? gateway in enabledGateways()? invoice owned?
             └─ per-gateway branch → PaymentService::initiateX(...)
                  └─ new XGateway($config)->create…()  ── redirect the browser away
                                                             (bKash/Stripe/PayPal also
                                                              cache ref → {school, invoice})

Gateway hosted page  ──►  browser returns to a public route
   ├─ bkash:      GET  /portal/pay/bkash/callback        (?paymentID&status)
   ├─ stripe:     GET  /portal/pay/stripe/return         (?session_id)
   ├─ paypal:     GET  /portal/pay/paypal/return         (?token | ?cancel=1)
   └─ sslcommerz: GET|POST /portal/pay/sslcommerz/{result} (CSRF-exempt; POST cross-site)
        └─ Portal\PaymentController::xReturn()
             └─ PaymentService::verifyX(ref, invoiceId, schoolId)
                  ├─ re-fetch/validate with the gateway (execute/retrieve/capture/validate)
                  ├─ replay + amount guards
                  ├─ idempotent by transaction_ref
                  └─ DB::transaction → Payment + updateInvoiceAfterPayment + event
```

### Authoritative webhooks (async confirmation)

`WebhookController` (`POST /payments/webhook/{stripe,paypal}`, public +
CSRF-exempt) is the authoritative confirmation, so an invoice settles even if
the payer never returns to the browser. Stripe verifies the `Stripe-Signature`
HMAC against the stored `webhook_secret`; PayPal verifies via the
verify-webhook-signature API using a `webhook_id` credential. Both reuse the
idempotent `verifyStripe` / `verifyPayPal`. SSLCommerz already had IPN; bKash
captures synchronously.

### Refunds

`RefundService` calls `manager->driver($payment->method)->refund(...)` for any
gateway — so Stripe/PayPal refunds hit the gateway (not just a DB row). A
per-gateway `fee_pct` lives in the generic JSON store
(`gateways[slug].fee_pct`), and `PaymentConfig::feePct($slug)` reads it;
`calculateFee` applies it uniformly for every gateway.

### Credential storage (generic, no per-gateway migrations)

`payment_configs.gateways` is a single encrypted JSON column:

```json
{ "bkash": { "enabled": true, "credentials": { "app_key": "…", "app_secret": "…" } } }
```

`PaymentConfig` reads it through `gatewayEnabled($slug)`, `credential($slug, $key)`,
`availableGatewayDefs()`, and `enabledGateways()` (online + enabled + all required
credentials present). Adding a gateway never touches the schema — the `PaymentGateway`
contract + registry (`config/payment_gateways.php`) is the contribution surface.
See [`payment-gateway-architecture.md`](../payment-gateway-architecture.md) for
the "one driver + one config entry" workflow and the incremental contract
refactor plan.

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
- `POST /payments/webhook/stripe`
- `POST /payments/webhook/paypal`

## Services & Business Rules

- `InvoiceService`
  - Generates invoices for a single student or a whole class.
  - Prevents duplicate open invoices for the same student/academic year/month.
  - Builds invoice items from active fee items for the target class/year.
  - Applies fee discounts and auto-uses available student credit.
  - Supports cancellation and waiver.
- `PaymentService`
  - Records manual payments in a single database transaction.
  - Supports bKash, SSLCommerz, Stripe, and PayPal initiation and completion verification.
  - Updates invoice status automatically (`unpaid`, `partial`, `paid`) after every payment.
  - Converts overpayments into student credit.
- `RefundService`
  - Creates refund requests for payments.
  - For gateway payments, calls the gateway refund via `PaymentGatewayManager` for any of the four gateways; for cash/bank methods, leaves a manual pending refund.
  - Applies processing fees based on payment config.
- `CreditService`
  - Manages student credit balance and transaction history.
  - Used for overpayment crediting and automatic invoice offset.

## Gateway Policy
- Gateway support is country-aware (`config/payment_gateways.php` → `by_country`).
- Bangladesh schools get bKash and SSLCommerz; other countries default to Stripe and PayPal.
- Each gateway declares its supported currencies and the service rejects unsupported invoice currencies before calling the gateway.
- See [`payment-gateways-by-country.md`](../payment-gateways-by-country.md) for the full regional gateway landscape the availability mapping is based on.

## Important Implementation Notes
- All financial writes are wrapped in database transactions.
- Invoice/payment data is returned through resources, never as raw Eloquent models.
- Invoice generation is idempotent for open invoices in the same period.
- Gateway callbacks and webhooks are idempotent to avoid duplicate payment recording.
- Student credit is used before new payment amounts are due on a generated invoice.

## Integration Points
- Built from fee structure in the FeeItem module.
- Uses student records and academic year context from the Student/Academic modules.
- Payment events are emitted for invoice generation, payment recording, refund creation, cancellation, waiver, and overpayment crediting.
- The module is the billing backbone for the broader school finance workflow.
