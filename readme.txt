=== Mydybox Taiwan for WooCommerce ===
Contributors: mydymaibox
Tags: woocommerce, taiwan, checkout, shipping, social-login
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Localization toolkit for WooCommerce in Taiwan: checkout fields, tax ID, postcode autofill, CVS pickup, and a visual rule engine.

== Description ==

Mydybox Taiwan for WooCommerce is a localization toolkit built for stores selling to customers in Taiwan. It adapts the WooCommerce checkout to Taiwanese conventions, integrates with local logistics and payment providers, and provides a visual rule engine for common business logic.

= Core Features =
* **Taiwan Checkout Optimization** — City/District cascading dropdowns and 3+2 digit postcode auto-fill.
* **Tax ID Fields** — Collect Unified Business Number (UBN), Company Name, Mobile Barcode, Citizen Digital Certificate, and Donation Code at checkout.
* **Optional Tax ID Lookup** — When the site owner explicitly opts in, an entered 8-digit Tax ID can be looked up against the Taiwan GCIS open-data API to pre-fill the company name. Disabled by default.
* **Visual Rule Engine** — Manage payment, shipping, and cart rules with a visual interface; no coding required.
* **Custom Order Numbers** — Sequential number formats (e.g., `Prefix + YYYYMMDD + Sequence`).
* **Checkout Countdown** — Optional reservation timer.
* **Mobile Sticky Bar** — Sticky "Buy" button for mobile product pages.
* **Social Login** — One-click login with LINE, Google, and Facebook (each optional and disabled by default).
* **Convenience-Store Pickup** — 7-11 / FamilyMart / Hi-Life / OK pickup via ECPay or NewebPay logistics.
* **HPOS Ready** — Compatible with WooCommerce High-Performance Order Storage.

== Installation ==

1. Upload the `mydybox-taiwan-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress "Plugins" screen.
3. Configure under **Mydybox** in the admin menu.

== Frequently Asked Questions ==

= What are the system requirements? =
PHP 8.1+, WordPress 6.5+, and WooCommerce 8.0+.

= Is it compatible with other checkout plugins? =
The plugin uses the standard WooCommerce Additional Checkout Fields API, so it is compatible with most modern themes and plugins. If you run into a conflict, check the Logs tab.

= Does the plugin send any data to external services? =
Only when you explicitly enable a feature that requires it (social login, payment gateways, logistics, or tax-ID lookup). See the **External services** section below for the complete list.

== External services ==

This plugin can connect to several third-party services. Each connection only happens when the corresponding feature is enabled in the plugin settings. By default, every external integration is **disabled**.

**1. Taiwan GCIS (Government Open Data) — Tax ID Lookup**

* What it is: The Ministry of Economic Affairs (Taiwan) open-data API that returns the registered company name for a given Unified Business Number.
* When data is sent: Only when the site owner has enabled "Tax ID Lookup" in **Checkout settings**, and a customer types an 8-digit Tax ID during checkout. The 8-digit Tax ID is then sent to `data.gcis.nat.gov.tw`. No other customer data is sent.
* Disabled by default; explicit opt-in is required both in the JS payload and on the server endpoint.
* Terms of use: https://data.gov.tw/license
* Privacy policy: https://data.gov.tw/about

**2. LINE Login (OAuth)**

* What it is: LINE Corp's OAuth 2.0 / OpenID Connect endpoint used to authenticate the customer with their LINE account.
* When data is sent: Only after the site owner enables LINE Login and a visitor clicks the "Login with LINE" button. The authorization code, your configured Channel ID/Secret, and the redirect URL are sent to `api.line.me`; the response (LINE user ID, display name, optional email, profile picture URL) is used to create or match a WordPress user.
* Terms of use: https://terms2.line.me/LINE_Developers_Agreement
* Privacy policy: https://line.me/en/terms/policy/

**3. LINE Messaging API — Abandoned-Cart Push Notifications**

* What it is: LINE Corp's Messaging API used to push a recovery message to a customer who linked their LINE account.
* When data is sent: Only when the site owner enables abandoned-cart LINE notifications and a customer has linked their LINE account and abandoned a cart. The configured channel access token and the recipient's LINE user ID + message body are sent to `api.line.me`.
* Terms of use: https://terms2.line.me/LINE_Official_Account_Terms_of_Use
* Privacy policy: https://line.me/en/terms/policy/

**4. Google OAuth**

* What it is: Google's OAuth 2.0 endpoints used to authenticate the customer with their Google account.
* When data is sent: Only after the site owner enables Google Login and a visitor clicks "Login with Google". The authorization code, your configured Client ID/Secret, and the redirect URL are sent to `oauth2.googleapis.com`; the returned ID token (Google user ID, name, email, picture URL) is used to create or match a WordPress user.
* Terms of use: https://policies.google.com/terms
* Privacy policy: https://policies.google.com/privacy

**5. Facebook Graph API (Login)**

* What it is: Meta's Graph API OAuth endpoints used to authenticate the customer with their Facebook account.
* When data is sent: Only after the site owner enables Facebook Login and a visitor clicks "Login with Facebook". The authorization code, your configured App ID/Secret, and the redirect URL are sent to `graph.facebook.com`; the returned profile (Facebook user ID, name, email, picture URL) is used to create or match a WordPress user.
* Terms of use: https://www.facebook.com/legal/terms
* Privacy policy: https://www.facebook.com/policy.php

**6. ECPay (Green World) — Payment Gateway and CVS Pickup**

* What it is: ECPay's online payment gateway and logistics (CVS pickup) APIs.
* When data is sent: Only when the site owner enables the ECPay payment gateway and/or CVS shipping method, and a customer chooses ECPay at checkout. The order summary (order ID, total amount, item names, customer name/email/phone, billing/shipping address as needed by the chosen ECPay service) is posted to `payment.ecpay.com.tw` (or the staging host in test mode) or `logistics.ecpay.com.tw` for store-pickup selection.
* Terms of use: https://www.ecpay.com.tw/About/Term
* Privacy policy: https://www.ecpay.com.tw/About/Privacy

**7. NewebPay (藍新金流) — CVS Pickup**

* What it is: NewebPay's convenience-store pickup map API.
* When data is sent: Only when the site owner enables the NewebPay CVS shipping method and a customer opens the store-selection popup. The configured Merchant ID and a return-URL nonce are posted to `cvsmap.newebpay.com`. NewebPay then returns the selected store's ID, name, and address to the plugin.
* Terms of use: https://www.newebpay.com/website/Page/content/term
* Privacy policy: https://www.newebpay.com/website/Page/content/privacy

== Changelog ==

= 1.0.7 =
* Renamed to **Mydybox Taiwan for WooCommerce** for wp.org distinctiveness.
* Removed the locked electronic-invoice module that was previously gated behind a Pro plugin (Guideline 5 compliance).
* GCIS Tax ID lookup is now **opt-in and off by default**, with a server-side guard.
* Hardened social-login OAuth flow with a random, browser-bound `state` cookie (login-CSRF fix).
* Upgraded bundled SweetAlert2 from 11.14.1 to 11.26.25 (out of the vulnerable range).
* Removed every inline `<script>` / `<style>` block in favor of `wp_enqueue_*` / `wp_print_inline_script_tag`.
* Sanitized the ECPay return-URL payload before any field is stored or displayed (MAC verification continues to run on the unmodified payload).
* Documented every external service in the new "External services" section.

= 1.0.6 =
* Added: ECPay convenience-store pickup map integration.
* Improved: Full admin UI redesign — fixed input widths and tab-bar overflow.
* Fixed: Invoice field description text display issue.

= 1.0.5 =
* Fixed: Mobile checkout flow and iOS compatibility issues.

= 1.0.4 =
* Improved: Localized SweetAlert2 for wp.org compliance.
* Improved: Upgraded rule-management notifications to SweetAlert2.
* Fixed: Removed redundant save buttons on log pages.
* Fixed: Sanitized user inputs; tightened nonce verification.

= 1.0.3 =
* Added: Social Login module (LINE / Google / Facebook).
* Added: Checkout countdown timer.
* Added: Mobile sticky buy bar.

= 1.0.0 =
* Initial release.
