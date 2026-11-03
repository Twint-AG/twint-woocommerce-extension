=== TWINT Payment for WooCommerce ===
Contributors: TWINT
Tags: TWINT, WooCommerce, payment gateway, express checkout
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.8.0
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

== Changelog ==

= Unreleased =

* **Breaking change**: All REST routes under `twint/v1` now require a valid `X-TWINT-Nonce` header. Direct REST API calls without the plugin's JS middleware will be rejected. Integrations that call TWINT REST endpoints directly must send this header.
* **Cross-domain REST**: If the storefront is served from a different origin than the REST API (e.g. some WPML/Polylang domain-per-language setups), add the storefront origin(s) to the `allowed_http_origins` filter so the `X-TWINT-Nonce` header is allowed through CORS. Note that WooCommerce session cookies are not shared across domains; serving the REST API from the same origin as the storefront remains the supported setup.
* Fix: Express Checkout shipping calculation no longer calls `calculate_shipping()` at finalize. Shipping rates are snapshotted at check-in and reused, preventing fatal errors with third-party shipping plugins (e.g. Flexible Shipping) in background/cron contexts.
* Fix: When the selected shipping rate is not found, Express Checkout now fails immediately (`PaymentException`) instead of retrying indefinitely.
* Fix: When the order total differs from the amount shown in the TWINT app, an order note is added for merchant visibility.
* Fix: Abandoned Express Checkout orders now stay as `checkout-draft` (hidden from admin) instead of `pending` with placeholder data. WooCommerce auto-cleans drafts.
* Fix: Express Checkout button label is now translatable via `__()` and customizable via `twint_express_button_label` filter.
* Fix: Hardcoded order attribution (`typein`/`(direct)`) removed from Express Checkout orders.
* Fix: Dead code `ExpressOrderService::cancelOrder()` removed.
