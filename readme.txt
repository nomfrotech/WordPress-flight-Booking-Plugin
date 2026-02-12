=== WordPress Flight Booking Plugin ===
Contributors: nomfro
Tags: flights, travel, duffel, booking, payments, currency
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Commercial-grade flight booking engine integrating Duffel API with multi-currency and payment handoff.

== Description ==
WordPress Flight Booking Plugin delivers a modular booking platform designed for production-grade flight search and order processing.

= Key Features =
* Duffel API integration for offer requests and order creation.
* Multi-currency display and checkout conversion from EUR base settlement.
* Payment provider handoff: PayPal, Paystack, Stripe, Flutterwave, and bank transfer.
* REST API endpoints for offers, orders, webhooks, and admin passthrough operations.
* Secure webhook signature verification.
* Modern frontend booking interface with airport autocomplete, offer cards, traveler/account stage, and checkout handoff.
* Elementor “Flight Search” widget.
* Feature flags for roundtrip, multi-city, ancillaries, and traveler profiles.
* Custom database schema for offers, orders, and transactions.

== Installation ==
1. Upload `wordpress-flight-booking-plugin` to `/wp-content/plugins/`.
2. Activate through **Plugins**.
3. Visit **Settings > Flight Booking** and configure Duffel/API/payment settings.
4. Place shortcode `[wfbp_search]` in any page for booking flow.
5. Place `[wfbp_currency_switcher]` in your header or preferred global area.

== Frequently Asked Questions ==
= Is Duffel token required? =
Yes, flight search and order creation need an API token.

= Does this support multiple payment gateways? =
Yes. Enable and configure any supported provider in plugin settings.

= Can I change display currency? =
Yes. Display and checkout currency can be configured independently.

== Changelog ==
= 1.0.1 =
* Improved airport autocomplete reliability with Duffel + fallback lookup service and upgraded booking templates/UX.

= 1.0.0 =
* Initial release with Duffel integration, payments, REST API, shortcode UI, Elementor widget, and developer tooling.

== Upgrade Notice ==
= 1.0.0 =
Initial stable release.
