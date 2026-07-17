# Payment Gateways by Country — Reference

A working list of **local payment gateways** (and the global fallbacks) with links to
their developer documentation, to help decide the gateway architecture for the
school-management platform.

> **Design note (per your direction):** don't add per-gateway columns/migrations.
> Store gateway config generically — e.g. one JSON `credentials` column on
> `payment_configs` keyed by gateway slug, plus `enabled`/`mode` flags — and drive
> the available options from `config/payment_gateways.php` (country → gateway list).
> Adding a new gateway then needs **no migration**: a config entry + a gateway class.

> URLs and availability change — verify each before integrating. "Local" = domestic
> rails / wallets popular in that market; "Global aggregators" cover many countries
> through a single integration.

---

## Global aggregators (one integration, many local methods)

| Gateway | Docs | Notes |
|---|---|---|
| Stripe | https://stripe.com/docs | 46+ countries, 135+ currencies, many local methods |
| PayPal / Braintree | https://developer.paypal.com · https://developer.paypal.com/braintree | Global wallet + local methods via Braintree |
| Adyen | https://docs.adyen.com | Enterprise, huge local-method coverage |
| Checkout.com | https://www.checkout.com/docs | Global + MENA strength |
| Rapyd | https://docs.rapyd.net | 100+ countries, local payout/collect |
| dLocal | https://docs.dlocal.com | Emerging markets (LatAm, Africa, Asia) |
| EBANX | https://developers.ebanx.com | LatAm-focused |
| 2Checkout / Verifone | https://verifone.cloud/docs | Global |
| Worldpay | https://developer.worldpay.com | Global acquiring |

---

## South Asia

### Bangladesh
| Gateway | Docs |
|---|---|
| bKash (Checkout/Tokenized) | https://developer.bka.sh |
| Nagad | https://nagad.com.bd (merchant/PGW onboarding) |
| SSLCommerz | https://developer.sslcommerz.com |
| aamarPay | https://aamarpay.readme.io |
| ShurjoPay | https://shurjopay.com.bd |
| Portpos / UddoktaPay | https://uddoktapay.com (docs on request) |

### India
| Gateway | Docs |
|---|---|
| Razorpay | https://razorpay.com/docs |
| PayU India | https://devguide.payu.in |
| Cashfree | https://docs.cashfree.com |
| Instamojo | https://docs.instamojo.com |
| CCAvenue | https://www.ccavenue.com (integration kit) |
| Paytm Business | https://business.paytm.com/docs |
| PhonePe | https://developer.phonepe.com |
| UPI (rails) | https://www.npci.org.in/what-we-do/upi |

### Pakistan
| Gateway | Docs |
|---|---|
| JazzCash | https://sandbox.jazzcash.com.pk (developer portal) |
| Easypaisa | https://easypaisa.com.pk |
| PayFast (PK) | https://apidocs.payfast.pk |
| Safepay | https://docs.getsafepay.com |

### Sri Lanka
| Gateway | Docs |
|---|---|
| PayHere | https://support.payhere.lk/api-&-mobile-sdk |
| WebXPay | https://www.webxpay.com |
| Genie (Dialog) | https://www.geniebiz.lk |

### Nepal
| Gateway | Docs |
|---|---|
| eSewa | https://developer.esewa.com.np |
| Khalti | https://docs.khalti.com |
| ConnectIPS / IMEPay | https://connectips.com |

---

## Southeast Asia

### Indonesia
| Gateway | Docs |
|---|---|
| Midtrans | https://docs.midtrans.com |
| Xendit | https://developers.xendit.co |
| DOKU | https://developers.doku.com |
| Wallets: GoPay, OVO, DANA, ShopeePay | (via the above aggregators) |

### Malaysia
| Gateway | Docs |
|---|---|
| iPay88 | https://www.ipay88.com |
| Billplz | https://www.billplz.com/api |
| ToyyibPay | https://toyyibpay.com |
| Razer Merchant Services (MOLPay) | https://docs.merchant.razer.com |
| senangPay | https://senangpay.my |

### Philippines
| Gateway | Docs |
|---|---|
| PayMongo | https://developers.paymongo.com |
| Maya (PayMaya) | https://developers.maya.ph |
| Dragonpay | https://www.dragonpay.ph |
| Xendit | https://developers.xendit.co |
| GCash | (via PayMongo/Xendit/aggregators) |

### Thailand
| Gateway | Docs |
|---|---|
| Opn (Omise) | https://docs.opn.ooo |
| 2C2P | https://developer.2c2p.com |
| GB Prime Pay | https://www.gbprimepay.com |
| PromptPay (rails) | (via gateways) |

### Vietnam
| Gateway | Docs |
|---|---|
| VNPay | https://sandbox.vnpayment.vn/apis |
| MoMo | https://developers.momo.vn |
| ZaloPay | https://docs.zalopay.vn |
| OnePay / Payoo | https://onepay.vn |

### Singapore
| Gateway | Docs |
|---|---|
| HitPay | https://hit-pay.com (API docs) |
| 2C2P | https://developer.2c2p.com |
| Stripe / Adyen | (global) |
| PayNow (rails) | (via gateways) |

---

## East Asia

### China
| Gateway | Docs |
|---|---|
| Alipay (Global) | https://global.alipay.com/docs |
| WeChat Pay | https://pay.weixin.qq.com/index.php/public/wechatpay |
| UnionPay | https://www.unionpayintl.com |

### Japan
| Gateway | Docs |
|---|---|
| KOMOJU | https://docs.komoju.com |
| PAY.JP | https://pay.jp/docs |
| GMO Payment Gateway | https://www.gmo-pg.com |
| Wallets: PayPay, Rakuten Pay | (via aggregators) |

### South Korea
| Gateway | Docs |
|---|---|
| Toss Payments | https://docs.tosspayments.com |
| PortOne (Iamport) | https://developers.portone.io |
| KG Inicis / NHN KCP | https://www.inicis.com |

---

## Africa

### Nigeria
| Gateway | Docs |
|---|---|
| Paystack | https://paystack.com/docs |
| Flutterwave | https://developer.flutterwave.com |
| Interswitch | https://docs.interswitchgroup.com |
| Monnify | https://developers.monnify.com |
| Paga | https://developer.mypaga.com |

### Kenya
| Gateway | Docs |
|---|---|
| M-Pesa Daraja (Safaricom) | https://developer.safaricom.co.ke |
| Pesapal | https://developer.pesapal.com |
| Flutterwave / Paystack | (see above) |
| DPO Group | https://docs.dpopay.com |

### Ghana
| Gateway | Docs |
|---|---|
| Hubtel | https://developers.hubtel.com |
| Paystack / Flutterwave | (see above) |
| ExpressPay | https://expresspaygh.com |
| MTN MoMo | https://momodeveloper.mtn.com |

### South Africa
| Gateway | Docs |
|---|---|
| PayFast | https://developers.payfast.co.za |
| Yoco | https://developer.yoco.com |
| Peach Payments | https://developer.peachpayments.com |
| Ozow (instant EFT) | https://ozow.com |
| SnapScan | https://developer.snapscan.co.za |

### Egypt
| Gateway | Docs |
|---|---|
| Paymob | https://docs.paymob.com |
| Fawry | https://developer.fawrystaging.com |
| Kashier | https://developers.kashier.io |
| PayTabs | https://site.paytabs.com/en/developers |

### Pan-African aggregators
Flutterwave · Paystack · DPO Group (https://docs.dpopay.com) · Cellulant · MFS Africa · Chipper.

---

## Middle East / MENA

### UAE & GCC
| Gateway | Docs |
|---|---|
| PayTabs | https://site.paytabs.com/en/developers |
| Telr | https://telr.com/support/knowledge-base/gateway-integration |
| Network International (N-Genius) | https://docs.ngenius-payments.com |
| Amazon Payment Services (PayFort) | https://paymentservices.amazon.com/docs |
| Checkout.com | https://www.checkout.com/docs |

### Saudi Arabia
| Gateway | Docs |
|---|---|
| Moyasar | https://moyasar.com/docs |
| HyperPay | https://docs.oppwa.com |
| Tap Payments | https://developers.tap.company |
| PayTabs | https://site.paytabs.com/en/developers |
| Mada / STC Pay | (via gateways) |

### Kuwait / Bahrain / Qatar / Oman
| Gateway | Docs |
|---|---|
| MyFatoorah | https://docs.myfatoorah.com |
| Tap Payments | https://developers.tap.company |
| KNET (KW) / Benefit (BH) | (via gateways) |

### Turkey
| Gateway | Docs |
|---|---|
| iyzico | https://dev.iyzipay.com |
| PayTR | https://www.paytr.com/entegrasyon |
| Craftgate | https://developer.craftgate.io |

---

## Latin America

### Brazil
| Gateway | Docs |
|---|---|
| Mercado Pago | https://www.mercadopago.com.br/developers |
| PagBank (PagSeguro) | https://dev.pagbank.uol.com.br |
| Pagar.me | https://docs.pagar.me |
| Cielo | https://developercielo.github.io |
| Pix (rails) | (via gateways) |

### Mexico
| Gateway | Docs |
|---|---|
| Mercado Pago | https://www.mercadopago.com.mx/developers |
| Conekta | https://developers.conekta.com |
| Openpay | https://www.openpay.mx/docs |
| Clip | https://developer.clip.mx |

### Colombia
| Gateway | Docs |
|---|---|
| Wompi | https://docs.wompi.co |
| PayU LatAm | https://developers.payulatam.com |
| ePayco | https://docs.epayco.co |
| PSE (rails) | (via gateways) |

### Chile
| Gateway | Docs |
|---|---|
| Transbank (Webpay) | https://www.transbankdevelopers.cl |
| Flow | https://www.flow.cl/docs/api.html |
| Khipu | https://khipu.com/page/api |

### Peru / Argentina
| Gateway | Docs |
|---|---|
| Culqi (PE) | https://docs.culqi.com |
| Niubiz / VisaNet (PE) | https://desarrolladores.niubiz.com.pe |
| Mercado Pago (AR) | https://www.mercadopago.com.ar/developers |
| dLocal (regional) | https://docs.dlocal.com |

### Regional aggregators
dLocal · EBANX (https://developers.ebanx.com) · PayU LatAm · Kushki (https://docs.kushkipagos.com) · AstroPay.

---

## Europe (local methods & gateways)

| Country / Method | Gateway / Docs |
|---|---|
| Netherlands — iDEAL | via Mollie https://docs.mollie.com / Adyen |
| Belgium — Bancontact | via Mollie / Adyen |
| Germany/Austria — SEPA, SOFORT, Klarna | Klarna https://docs.klarna.com · Unzer https://docs.unzer.com |
| Poland — Przelewy24, BLIK | https://developers.przelewy24.pl · Tpay |
| Nordics — Klarna, Vipps, Swish, MobilePay | Vipps MobilePay https://developer.vippsmobilepay.com |
| UK — Direct Debit, Open Banking | GoCardless https://developer.gocardless.com · TrueLayer |
| Pan-EU aggregators | Mollie · Adyen · Stripe · Nexi/Nets |

---

## Suggested approach for this project

1. **Generic storage:** a single JSON `credentials` column (encrypted) + `enabled` + `mode` per gateway on `payment_configs` — no per-gateway columns/migrations.
2. **Registry-driven options:** `config/payment_gateways.php` maps `country_code → [gateway slugs]` and defines each gateway's fields; the settings UI and checkout render from it.
3. **Gateway contract:** a small interface (`initiate()`, `verify()/execute()`, `SUPPORTED_CURRENCIES`) implemented per gateway — start with your **local** gateway, then add Stripe/PayPal as the global fallback.
