# Payment Gateway Architecture

**Goal:** an open-source-friendly payment layer where adding a gateway is *one driver
class + one config entry* — no core edits, no migration, no UI changes.

This document has two halves:

1. **As-built** — what actually ships today (four working gateways).
2. **Next step** — formalizing the shared `PaymentGateway` contract so the four
   flow patterns collapse into a single app-side code path, plus a "how to add a
   gateway" guide and an incremental migration path that keeps tests green.

---

## Part 1 — As-built (current state)

### What ships

Four gateways are implemented and covered by feature tests:

| Slug | Driver class | Flow pattern | Currencies | Countries |
|---|---|---|---|---|
| `bkash` | `BkashGateway` | Create → authorize → execute (tokenized) | BDT | BD |
| `sslcommerz` | `SslcommerzGateway` | Hosted redirect (+ IPN) | BDT | BD |
| `stripe` | `StripeGateway` | Hosted Checkout redirect | USD, EUR, GBP, AUD, CAD, SGD, INR, AED, JPY, NZD | default (non-BD) |
| `paypal` | `PayPalGateway` | Hosted redirect (Orders v2) | USD, EUR, GBP, AUD, CAD, SGD, JPY, NZD | default (non-BD) |

Availability per school comes from `config/payment_gateways.php`
(`by_country` → slugs, falling back to `default`), filtered to gateways whose
`implemented` flag is `true`.

### The three pieces already in place

**a) Generic credential storage (no per-gateway migration).**
`payment_configs.gateways` is a single encrypted JSON column:

```json
{ "bkash": { "enabled": true, "credentials": { "app_key": "…", "app_secret": "…" } } }
```

`PaymentConfig` reads it through `gatewayEnabled($slug)`, `credential($slug, $key)`,
`availableGatewayDefs()`, and `enabledGateways()` (online + enabled + all required
credentials present). Legacy `bkash_*` / `sslcommerz_*` columns remain only as a
read fallback. Adding a gateway never touches the schema.

**b) Registry (`config/payment_gateways.php`).**
Single source of truth: each gateway's `label`, `icon`, `currencies`,
`implemented` flag, and `fields` (un-prefixed keys with `label` / `secret` /
`required`). Drives the settings UI, request validation, and availability. This is
the contribution surface.

**c) String method/gateway columns.**
`payments.method`, `refunds.method`, and `payment_gateway_logs.gateway` were widened
from enums to `string(30)`, so any new slug fits with no further migration. The set
of allowed values is governed at the application layer (registry + validation).

### How a payment flows today

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

`PaymentService::updateInvoiceAfterPayment()` and `PaymentNumberGeneratorService`
are shared by all gateways; `PaymentGatewayLog` records every gateway call.

### Where the four drivers actually differ (as coded)

| Concern | bKash | SSLCommerz | Stripe | PayPal |
|---|---|---|---|---|
| Start call | `grantToken` + `createPayment` | `initSession` | `createCheckoutSession` | `createOrder` |
| Confirm call | `executePayment` (`queryPayment` fallback) | `validatePayment` | `retrieveSession` | `captureOrder` |
| Refund | `refund` | `refund` | `refund` | `refund` |
| Browser return key | `paymentID` (cached) | `tran_id` = invoice_number | `session_id` (cached) | `token` (cached) |
| Amount units | major (BDT) | major (BDT) | minor (cents) | major decimal string |
| Durable ref recorded | `trxID` | `val_id` | PaymentIntent id | capture id |

### The friction this leaves

The differences above are already normalized *by hand* in two places that grow
linearly with each gateway:

- `PaymentService` has a dedicated `initiateX` / `verifyX` pair per gateway.
- `Portal\PaymentController::initiate()` has an `if ($gateway === '…')` branch per
  gateway, and there is one bespoke return route/handler per gateway.

Nothing shares a common type, so the compiler can't guarantee a new driver is
"complete," and a contributor must touch the service, controller, and routes to add
one. Part 2 removes that.

---

## Part 2 — Formalizing the `PaymentGateway` contract

### Why

Ignoring branding, real integrations fall into **four flow patterns** — the
abstraction only has to cover these four, not N gateways:

| Pattern | How it works | Built example |
|---|---|---|
| **A. Hosted redirect** | create session → redirect → return → confirm via return/webhook | SSLCommerz, PayPal, Stripe Checkout |
| **B. Create → authorize → execute** | two server calls with a user auth step between | bKash |
| **C. Intent + client SDK** | server creates an intent → browser confirms with the gateway's JS SDK → webhook | (Stripe PaymentIntents, Adyen — future) |
| **D. Wallet push / STK** | server initiates → user approves in wallet app → async callback | (M-Pesa, bKash-URL variants — future) |

Two things are **universal**: an authoritative async **webhook/IPN** (never trust
only the browser return) and **idempotent verification** before recording. Refunds
are always a separate call keyed by the gateway's payment id.

So `initiate()` must be able to return *any* of: a **redirect URL** (A/B), a
**client payload** for a JS SDK (C), or a **"pending, await callback"** instruction
(D) — and every driver must expose a return handler, a webhook handler, `verify()`,
and `refund()`.

### The contract

```php
interface PaymentGateway
{
    /** @return list<string> ISO-4217 codes, e.g. ['BDT'] or ['USD','EUR',…] */
    public function supportedCurrencies(): array;

    /** Start a payment. Returns a normalized instruction for the frontend. */
    public function initiate(PaymentIntent $intent): GatewayResponse;

    /** Handle the browser return (redirect back). */
    public function handleReturn(Request $request): GatewayResult;

    /** Handle the async webhook / IPN — the authoritative result. */
    public function handleWebhook(Request $request): GatewayResult;

    /** Re-check a payment's status out of band. */
    public function verify(string $reference): GatewayResult;

    public function refund(string $gatewayPaymentId, float $amount, string $reason): GatewayResult;
}
```

### Value objects (normalize the differences)

- **`PaymentIntent`** — `invoiceId`, `invoiceNumber`, `amount`, `currency`,
  `customerRef`, `returnUrl`, `cancelUrl`, `webhookUrl`.
- **`GatewayResponse`** — `action`: `redirect` | `render` | `pending`, plus the data
  for that action (`redirectUrl`, or `clientPayload` for an SDK, or `reference`).
- **`GatewayResult`** — `status`: `paid` | `failed` | `pending`, `amount`,
  `currency`, `transactionRef`, `gatewayPaymentId`, and the raw payload.

The portal only ever deals with these three types, so **the four flow patterns
collapse into one code path** on the app side.

### How the four shipped drivers map onto it

| Contract method | bKash | SSLCommerz | Stripe | PayPal |
|---|---|---|---|---|
| `initiate()` | `grantToken` → `createPayment` → `GatewayResponse::redirect(bkashURL)` | `initSession` → `redirect(GatewayPageURL)` | `createCheckoutSession` → `redirect(url)` | `createOrder` → `redirect(approveUrl)` |
| `handleReturn()` | read `paymentID` → `executePayment` → `GatewayResult` | read `tran_id`/`val_id` → `validatePayment` | read `session_id` → `retrieveSession` | read `token` → `captureOrder` |
| `handleWebhook()` | (bKash: n/a — synchronous execute) | IPN `validatePayment` | Checkout webhook `retrieveSession` | webhook `captureOrder`/lookup |
| `verify()` | `queryPayment` | `validatePayment` | `retrieveSession` | order lookup |
| `refund()` | `refund` | `refund` | `refund` | `refund` |

The gateway-specific amount/ref normalization already living in `verifyX()`
(minor→major for Stripe, `trxID`/`val_id`/capture-id selection, replay + amount
guards) moves *into* each driver's `handleReturn()`/`verify()`, which return a
uniform `GatewayResult`. `PaymentService` keeps only the shared recording logic.

### A driver manager + generic routes

`PaymentGatewayManager::driver($slug)` resolves a driver from the registry. The
per-gateway portal branches and bespoke return routes collapse to:

```
POST /portal/pay/initiate            → manager->driver($gw)->initiate($intent)      → act on GatewayResponse
GET|POST /payments/return/{gateway}  → manager->driver($gw)->handleReturn($request) → record if paid
POST /payments/webhook/{gateway}     → manager->driver($gw)->handleWebhook($request)→ record if paid  (CSRF-exempt)
```

`recordFromResult(GatewayResult)` on `PaymentService` becomes the single, gateway-
agnostic recording path (idempotent by `transactionRef`, transactional, fires
`PaymentRecorded`), replacing the four `verifyX` methods.

### Refunds

`RefundService` currently auto-calls the gateway only for bKash and SSLCommerz.
Under the contract it calls `manager->driver($payment->method)->refund(...)` for any
gateway — closing the gap where a Stripe/PayPal refund records a `Refund` row but
doesn't hit the gateway. (This is the smallest immediately-shippable slice and can
land before the full contract refactor.)

---

## How to add a gateway (target workflow)

1. **Add a registry entry** in `config/payment_gateways.php`: `label`, `icon`,
   `currencies`, `fields` (credential keys + `label`/`secret`/`required`), and place
   the slug under the right `by_country` list or `default`. Leave `implemented`
   `false` until the driver exists.
2. **Write one driver** implementing `PaymentGateway` (roughly 80–150 lines, using
   the `Http` facade — no SDK required). Map its calls to the four contract methods.
3. **Flip `implemented => true`.** The settings UI, validation, availability,
   portal button, initiate, return, webhook, and refund all work through the generic
   manager — no further edits.
4. **Add a driver test** (guard + a mocked round-trip via `Http::fake()`).

No migration, no controller edits, no route edits, no UI edits.

---

## Migration path (incremental, tests stay green)

The four gateways already work, so this is a refactor behind stable behavior — do it
in small, independently shippable steps:

1. **Value objects + interface** — add `PaymentGateway`, `PaymentIntent`,
   `GatewayResponse`, `GatewayResult`. No behavior change yet.
2. **Wire refunds through a manager** — add `PaymentGatewayManager`, give each driver
   a `refund()` that returns `GatewayResult`, and route `RefundService` through it.
   Ships the Stripe/PayPal auto-refund fix immediately.
3. **Adopt the contract one driver at a time** — implement `initiate`/`handleReturn`
   on `StripeGateway` first (cleanest), switch its portal branch to the generic path,
   keep the others on their existing `initiateX`/`verifyX` methods. Repeat per driver.
4. **Collapse the routes/branches** — once all four implement the contract, replace
   the per-gateway portal branches and return routes with the generic
   `return/{gateway}` + `webhook/{gateway}` routes and delete the `initiateX`/`verifyX`
   pairs.
5. **Add webhooks where missing** — Stripe Checkout + PayPal webhooks as the
   authoritative confirmation, so a dropped browser return still settles the invoice.

At every step the existing `PortalPaymentTest` / `tests/Feature/Payment` suites must
stay green; each step is one or two commits.

---

## Decision & consequences

**Decision:** keep the driver-per-gateway design and the generic JSON credential
store (both shipped); formalize the shared `PaymentGateway` contract + manager as the
next refactor, migrating incrementally.

**Consequences (positive):** adding a gateway becomes one file + one config block;
the schema is stable forever; contributors can PR a gateway in isolation; the four
flow patterns are provably covered by reference drivers; AGPL-3.0 is compatible with
the MIT/Apache gateway SDKs, though these drivers use none.

**Consequences (cost):** a one-time refactor of `PaymentService` and the portal
controller/routes; webhooks must be added for Stripe/PayPal to be fully robust; the
value-object layer adds a small indirection for readers used to the direct calls.

**Status:** Part 1 shipped. Part 2 step 2 (manager + Stripe/PayPal auto-refund) is
done — `PaymentGatewayManager` exists and refunds for all four gateways call the
gateway. Remaining Part 2 steps and the backlog below are not yet started.

---

## Backlog (deferred follow-ups)

Tracked here so they survive beyond a working session. None are happy-path bugs;
each is a robustness or consistency gap surfaced by the post-implementation audit.

1. ~~**Async webhooks for Stripe & PayPal.**~~ **Done.** `WebhookController`
   (`POST /payments/webhook/{stripe,paypal}`, public + CSRF-exempt) is the
   authoritative confirmation, so an invoice settles even if the payer never returns
   to the browser. Stripe verifies the `Stripe-Signature` HMAC against the stored
   `webhook_secret`; PayPal verifies via the verify-webhook-signature API using a new
   `webhook_id` credential. Both reuse the idempotent `verifyStripe` / `verifyPayPal`.
   `verifyPayPal` now GETs the order first so the browser return and the webhook can't
   double-capture. (SSLCommerz already had IPN; bKash captures synchronously.)

2. **Unify the dual credential system.** The Blade admin uses the generic JSON
   `payment_configs.gateways` store; the JSON-API surface
   (`PaymentConfigResource` + `UpdatePaymentConfigRequest`) still reads/writes the
   legacy per-gateway columns, so the API cannot configure Stripe/PayPal. The model
   falls back to the legacy columns, so nothing breaks today. Migrate the API onto
   the generic store, then (contract phase of expand-contract) drop the legacy
   `bkash_*` / `sslcommerz_*` columns once nothing reads them.

3. ~~**Stripe/PayPal refund processing fees.**~~ **Done.** A per-gateway `fee_pct`
   now lives in the generic JSON store (`gateways[slug].fee_pct`, editable per gateway
   in Payment settings); `PaymentConfig::feePct($slug)` reads it (falling back to the
   legacy `{slug}_fee_pct` columns for bKash/SSLCommerz), and `calculateFee` applies
   it uniformly for every gateway. No per-gateway column added.
