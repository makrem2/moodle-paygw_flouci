# Flouci payment gateway for Moodle (`paygw_flouci`)

Accept payments in **Tunisian dinars (TND)** through [Flouci](https://docs.flouci.com) with any Moodle
component that uses the Payments API (for example *Enrolment on payment* / `enrol_fee`).

Developed against Moodle 5.2 (the `public/` directory layout); requires Moodle 4.5 or later.
Uses the current **Flouci Payment API v2** (`Authorization: Bearer <PUBLIC>:<PRIVATE>`).

## Installation

1. Copy this folder to `<moodle>/public/payment/gateway/flouci` (Moodle 5.1+) or
   `<moodle>/payment/gateway/flouci` (older layouts). The folder must be named `flouci`.
   Or zip the folder and use *Site administration > Plugins > Install plugins*.
2. Visit *Site administration > Notifications* to run the install (creates the `paygw_flouci` table).
3. *Site administration > Plugins > Payment gateways > Manage payment gateways*: enable **Flouci**.

## Configuration

1. *Site administration > Payments > Payment accounts*: create (or edit) an account.
2. In the **Flouci** row enter the **Public key** and **Private key** of your Flouci application
   (Flouci developer dashboard) and tick *Enabled*. Keys are per payment account, as for every Moodle gateway.
3. Sell something in TND, e.g. add an *Enrolment on payment* method to a course with currency **TND**
   and that payment account.

Optional: *Site administration > Plugins > Payment gateways > Flouci* has the standard **surcharge** setting.

### Testing

Flouci gives every developer account a **TEST APP**. Use its keys first; there is no separate test URL, the keys
decide the environment. Test cards are listed on Flouci's *Test Environment* page. Switch to the production keys
when you go live. *Site administration > Plugins > Payment gateways > Flouci transactions* shows every session
and its status.

### Webhook

Moodle tells Flouci to call `https://<your-site>/payment/gateway/flouci/webhook.php` when a payment finishes.
This requires a **publicly reachable HTTPS site**; it will not work on `localhost`. If the webhook cannot be
reached nothing breaks: the customer's browser return, and a cron task that runs every 5 minutes, complete
the payment too. **Make sure Moodle cron is running.**

## How it works

```
Pay button -> start_payment (web service)   server computes amount, creates Flouci session, stores it
           -> browser redirected to Flouci  customer pays
           -> Flouci redirects to return.php  AND  calls webhook.php   (either, both, or neither)
           -> processor::process()          asks the Flouci API for the real status
              SUCCESS and amount/reference match -> save payment -> deliver order (once)
```

Security properties:

* The amount is **always computed on the server** from the component's payable; the browser sends no price.
* A session is tied to the user, the item and one Flouci `payment_id`; it cannot be replayed on another item or user.
* A payment is accepted **only after asking the Flouci API**, and only if the status is `SUCCESS` for exactly the
  requested amount and our own random reference. Query strings and webhook bodies are never trusted.
  A `SUCCESS` that does not match is stored as `mismatch`, logged as an event, and **not delivered**.
* Processing is locked per session and idempotent: return page, webhook and cron can race; the order is delivered once.
* If delivery fails after money was confirmed the session stays `paid` and cron retries it.
* The webhook never triggers an outbound call for unknown or finished sessions (no amplification / rate-limit abuse).
* API calls use Moodle's `curl` wrapper (proxy and blocked-host rules apply), time out, never follow redirects,
  and the checkout URL must be `https://*.flouci.com`.
* Only an amount and a random reference are sent to Flouci; no personal data. Privacy API implemented.

## Known limitations

* Refunds are done in the Flouci dashboard; Moodle is not notified.
* Flouci's webhook payload is not documented in detail; the handler accepts a `payment_id` from the query string,
  form body or JSON body and then verifies through the API, so the exact shape does not matter.
* Sessions that stay `paid` because the component keeps failing to deliver are retried every 5 minutes
  and show their last error in the transactions page.

## Development

JavaScript sources are in `amd/src`; rebuild `amd/build` with `npx grunt amd` from a Moodle checkout.
PHPUnit tests are in `tests/` (`vendor/bin/phpunit --testsuite paygw_flouci_testsuite`).

## License

GNU GPL v3 or later.
