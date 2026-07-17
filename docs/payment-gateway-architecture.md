# Payment Gateway Architecture — Recommendation (open-source friendly)

**Question:** which option lets me open-source this and implement *all* gateways,
given each works differently?

**Answer:** a **driver-based gateway system** — a common contract, one driver class
per gateway, generic (schema-less) credential storage, and a country→gateway
registry. Adding a gateway becomes: **one driver class + one config entry. No core
edits, no migration, no UI changes.** That is exactly what makes it contributable
and self-hostable.

The per-gateway-columns approach (what I started) is the opposite: every new gateway
needs a migration + model + controller + hand-written UI. It does not scale and is
hostile to outside contributors.

---

## 1. How gateways actually differ (analysis of the docs)

Ignoring branding, real integrations fall into **four flow patterns**. The
abstraction only has to cover these four — not N gateways.

| Pattern | How it works | Examples |
|---|---|---|
| **A. Hosted redirect** | Server creates a payment session → you redirect the browser to the gateway's page → gateway redirects back to your `return_url` → confirm via webhook/IPN | SSLCommerz, PayPal (Orders v2), Razorpay Hosted, Paystack, Flutterwave, PayTabs, Telr, iyzico, Mercado Pago, VNPay, Midtrans Snap, Xendit Invoice, Fawry |
| **B. Create → authorize → execute** | Two server calls with a user authorization step between (tokenized) | **bKash** (tokenized checkout) |
| **C. Intent + client SDK** | Server creates an "intent" → browser confirms it with the gateway's JS SDK (card never touches your server) → webhook confirms | Stripe (PaymentIntents), Adyen (Sessions/Components), Checkout.com, Paymob |
| **D. Wallet push / STK** | Server initiates → user approves in their wallet app / on phone → async callback | M-Pesa (Daraja STK Push), JazzCash, Easypaisa, MoMo, GCash |

Two things are **universal** across all four: an **async webhook/IPN** for the
authoritative result (never trust only the browser return), and **idempotent
verification** before recording a payment. Refunds are always a separate API call
keyed by the gateway's payment id.

**Design implication:** `initiate()` must be able to return *any* of:
- a **redirect URL** (pattern A/B),
- a **client payload** to hand to a JS SDK (pattern C),
- a **"pending, poll/await callback"** instruction (pattern D).

…and every driver must expose `handleWebhook()` + `verify()`.

---

## 2. Recommended structure

### a) A gateway contract (interface)

```php
interface PaymentGateway
{
    /** @return list<string> ISO-4217 codes, e.g. ['BDT'] or ['USD','EUR',...] */
    public function supportedCurrencies(): array;

    /** Start a payment. Returns a normalized instruction for the frontend. */
    public function initiate(PaymentIntent $intent): GatewayResponse;

    /** Handle the browser return (redirect back). */
    public function handleReturn(Request $request): GatewayResult;

    /** Handle the async webhook / IPN (the authoritative result). */
    public function handleWebhook(Request $request): GatewayResult;

    /** Re-check a payment's status out of band. */
    public function verify(string $reference): GatewayResult;

    public function refund(string $paymentId, float $amount, string $reason): GatewayResult;
}
```

### b) Small value objects (normalize the differences)

- `PaymentIntent` — invoice id, amount, currency, customer, `return_url`, `webhook_url`.
- `GatewayResponse` — `action`: `redirect` | `render` | `pending`, plus the data for
  that action (`redirect_url`, or `client_payload` for the SDK, or `reference`).
- `GatewayResult` — `status`: `paid` | `failed` | `pending`, `amount`, `transaction_ref`,
  `gateway_payment_id`, raw payload.

The controller/portal only ever deals with these three types, so **the four flow
patterns collapse into one code path** on the app side.

### c) A driver manager (Laravel-idiomatic)

`PaymentGatewayManager::driver('bkash')` resolves the class for a slug (from
`config/payment_gateways.php`). One generic webhook route:
`POST /payments/webhook/{gateway}` → `manager->driver($gateway)->handleWebhook()`.

### d) Schema-less credential storage (no per-gateway migration)

Replace the `bkash_*`, `sslcommerz_*`, `stripe_*`, `paypal_*` columns with **one**
generic structure — pick one:

- **Option 1 (simplest):** a JSON column on `payment_configs`:
  `gateways = { "bkash": {"enabled": true, "mode": "live", "credentials": {"app_key": "...", ...}} }`
  (the whole column encrypted).
- **Option 2 (cleaner for many gateways):** a `payment_gateway_settings` table
  (`school_id`, `gateway` slug, `enabled`, `mode`, `credentials` json-encrypted),
  one row per school+gateway.

Either way, **adding a gateway never changes the schema again**. The field labels
come from `config/payment_gateways.php`, which already drives the UI and validation.

### e) Registry (already built)

`config/payment_gateways.php`: `country_code → [slugs]` + each gateway's field
definitions + supported currencies. This stays; it's the contribution surface.

### f) Reuse what exists

`PaymentGatewayLog` already tracks async state; `PaymentService` already records
`Payment` rows and updates invoices. Drivers call into those — no reinvention.

---

## 3. Why this is the open-source option

- **Add a gateway = 1 file + 1 config block.** No migration, no core edits, no UI
  edits → outside contributors can PR a gateway in isolation.
- **Schema is stable forever** (generic credentials).
- **License:** AGPL-3.0 (your earlier pick) is fine — gateway SDKs are MIT/Apache,
  compatible. Ship gateways as first-party drivers; the community adds the rest.
- **A "How to add a gateway" guide** + the contract is all a contributor needs.

---

## 4. Suggested phasing (all patterns covered by reference drivers)

1. **Foundation:** contract + value objects + manager + generic credential storage;
   refactor the existing **bKash** code into a `BkashGateway` driver (pattern B).
2. **Global reference drivers:** **Stripe** (pattern C) + **PayPal** (pattern A) —
   proves the abstraction across the two hardest patterns.
3. **Redirect batch (cheap to add):** SSLCommerz, Razorpay, Paystack, Flutterwave
   (all pattern A — mostly config + a thin driver each).
4. **Wallet push:** M-Pesa Daraja (pattern D).
5. **Everything else:** community, via the contract + guide.

"Implement *all* gateways" is dozens of integrations — not a single sprint — but with
this design each is small and independent, and the first ~6 drivers prove all four
patterns end to end.

---

## My recommendation

Go with the **driver system + generic (Option 1 JSON) credential storage**. First
step: **revert the Stripe/PayPal per-gateway columns**, add the generic `gateways`
JSON column, refactor bKash into the first driver against the new contract, then add
Stripe + PayPal as the global reference drivers. Tell me your **primary local
gateway** (bKash? Nagad? SSLCommerz?) and I'll start there.
