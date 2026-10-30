=== TWINT Payment for WooCommerce ===
Contributors: TWINT
Tags: TWINT, WooCommerce, payment gateway, express checkout
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.0.1-dev
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: twint-payment-extension
Domain Path: /languages

A seamless payment integration for WooCommerce, enabling TWINT Express Checkout for fast, secure transactions.

== Description ==
The TWINT Payment for WooCommerce plugin integrates the TWINT payment system into your WooCommerce store, enabling customers to pay via TWINT’s Express Checkout. The plugin provides a seamless and secure checkout experience for users who prefer using TWINT as a payment method.

Features include:
- Support for TWINT Express Checkout.
- Easy setup and configuration.
- Secure and fast payment processing.
- Multi-language support.
- Customizable settings for testing and live environments.

== Frequently Asked Questions ==

= Which database privileges does the plugin need? =

* SELECT, INSERT, UPDATE, DELETE: always.
* CREATE: on first installation, to create the plugin tables.
* ALTER: when upgrading, to add the `lock_version` and `order_settled` columns. Without it the plugin keeps working and re-checks unfinished transactions, but without optimistic locking and without automatically completing orders whose payment was captured but not recorded; it shows an admin notice and retries every hour.
* TRIGGER: not required. It is only used to remove the trigger created by version 1.7.12 and earlier; if it is missing, that trigger stays and is harmless unless its MySQL user is deleted (see the admin notices below).

= Do multilingual shops (WPML, Polylang) need any configuration? =

No, if all languages share one domain (e.g. `/fr/`, `/it/` paths). If each language has its own domain and the storefront calls the REST API on another domain, add the storefront domains to the `allowed_http_origins` filter:

    add_filter('allowed_http_origins', function ($origins) {
        $origins[] = 'https://shop.example.fr';
        $origins[] = 'https://shop.example.it';
        return $origins;
    });

WooCommerce session cookies are not shared across domains, so serving the REST API from the same domain as the storefront remains the supported setup.

= My theme or another plugin calls the TWINT REST API directly. What changes? =

Every request to a `twint/v1` route must send the `X-TWINT-Nonce` header. Create the nonce with `wp_create_nonce('woocommerce-twint-express')` and use the fresh nonce returned in the `X-TWINT-Nonce` header of each TWINT response for the next request. The plugin's own JavaScript already does this.

= How can I change the Express Checkout button label? =

The default label follows the site language. To use your own label:

    add_filter('twint_express_button_label', function ($label) {
        return __('Pay with TWINT', 'my-theme');
    });

The label is escaped when it is printed, so HTML is shown as text.

= What do the TWINT admin notices mean? =

* "Database migration could not complete": the database user lacks the ALTER privilege. Grant it; the plugin retries every hour.
* "A database trigger is causing errors": a trigger from an older version is still present and its MySQL user no longer exists. The plugin removes it automatically when it has the TRIGGER privilege; otherwise ask your hosting provider to run the SQL command shown in the notice.
* "… payments … captured by TWINT but the WooCommerce order is in an unexpected status": the customer paid, but the order was refunded, trashed or is in a custom status. Open the order, read the TWINT note and reconcile it with the TWINT Merchant Portal.
* "… cancelled orders were reopened …": the customer paid for an order that had been cancelled. Check stock and fulfilment for these orders.

= My site has little traffic or uses DISABLE_WP_CRON. =

Payment reconciliation runs through WP-Cron. Configure a system cron job that calls `wp-cron.php` every minute. When PHP CLI is not available, the Credentials tab warns if WP-Cron has not run in the last 15 minutes.

= What about orders that were stuck before the upgrade? =

Automatic reconciliation only covers transactions created after the upgrade. Check older unpaid TWINT orders once against the TWINT Merchant Portal.

== Changelog ==

= 1.7.13 =

**Breaking changes for custom integrations**

* All REST routes under `twint/v1` now require a valid `X-TWINT-Nonce` header. Requests that do not go through the plugin's own JavaScript are rejected with 401/403. Integrations that call these routes directly must send the header (nonce action `woocommerce-twint-express`); every TWINT response returns a fresh nonce in the same header.
* If the storefront runs on a different origin than the REST API (e.g. a WPML/Polylang domain per language), add the storefront origin(s) to the `allowed_http_origins` filter. Shops that serve all languages from one domain (e.g. `/fr/`, `/it/` paths) need no change. See the FAQ.

**Database and payment reliability**

* The MySQL TRIGGER privilege is no longer required. The `before_update_twint_pairing` trigger is no longer created. On upgrade, an existing trigger is removed after the schema migration has completed, if the database user is allowed to drop triggers. Otherwise it stays in place; it is compatible with the new locking and needs no action.
* Concurrent updates now use optimistic locking on a new `lock_version` column; a second new column, `order_settled`, records which payments have been reconciled with their order. Both are added automatically on upgrade (requires the ALTER privilege). The migration runs on the first request after the plugin is updated, without reactivation, and is retried every hour if it fails. Until it has completed, an admin notice is shown, optimistic locking is off and orders whose payment was captured but not recorded are not completed automatically; re-checking unfinished transactions with TWINT keeps working.
* Payment states only move forward: a pairing that has reached a final state can no longer be overwritten.
* Marking a WooCommerce order as paid is idempotent and locked per order, so the browser, the CLI poller and WP-Cron can no longer process the same payment twice.
* Automatic reconciliation: WP-Cron re-checks unfinished TWINT transactions (created after the upgrade, last 48 hours, up to 50 per run) and completes orders that TWINT has confirmed but that were not marked as paid (last 24 hours, up to 50 per run).
* If TWINT captured a payment but the order is in an unexpected status (refunded, trashed, custom), the order is not changed automatically: an order note is added and an admin notice asks for review.
* A cancelled order (without refund) whose payment TWINT captured is reopened and marked as paid, with an admin notice to check stock and fulfilment.
* If the legacy trigger is broken (its MySQL DEFINER user was deleted, error 1449), the plugin drops it automatically on the next request. If the database user may not drop triggers, the plugin retries hourly and an admin notice shows the SQL command to send to the hosting provider.
* New admin notices for: migration not completed, broken legacy trigger, payments that need review, reopened orders. The notices are translated into German, French and Italian.
* The TWINT cron event is re-scheduled automatically if it goes missing (site clone, database migration). When PHP CLI is not available, the Credentials tab warns if WP-Cron has not run in the last 15 minutes.

**Express Checkout**

* Shipping rates are stored when the customer opens TWINT (while the cart is complete) and reused when the order is finalised, instead of being recalculated. This removes fatal errors with shipping plugins such as Flexible Shipping when the order is finalised in the background, and, for payments started after the upgrade, the shipping fee always matches the amount shown in the TWINT app.
* If no shipping rate can be determined for the selected method, the payment fails immediately instead of being retried indefinitely.
* If the order total differs from the amount shown in the TWINT app, an order note is added (for payments started after the upgrade).
* Unfinished Express Checkout orders now stay in the `checkout-draft` status, hidden from the order list, instead of `pending` with placeholder customer data.
* Express Checkout orders are no longer attributed to `typein` / `(direct)`. Their origin is now shown as "Unknown".

**Translations**

* The "Express Checkout" button label is translatable (German, French and Italian, including the Swiss locales) and can be changed with the `twint_express_button_label` filter.

**Security**

* Frontend REST routes verify a dedicated nonce in their permission callback instead of allowing every request.
* WordPress cookie authentication errors are no longer bypassed on TWINT routes.
* CORS exposes the `X-TWINT-Nonce` header to trusted origins only.
* The plugin's JavaScript refreshes the nonce automatically when a cached page or a changed session makes it stale.

== Upgrade Notice ==

= 1.7.13 =
Requires the ALTER database privilege for the automatic migration; TRIGGER is no longer required. Custom code calling the twint/v1 REST routes must send the X-TWINT-Nonce header. Update outside peak hours.
