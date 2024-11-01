=== ValuePay for Gravity Forms ===
Contributors:      valuepaymy
Tags:              valuepay, gravity forms, payment
Requires at least: 4.6
Tested up to:      6.0
Stable tag:        1.0.3
Requires PHP:      7.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Accept payment on Gravity Forms using ValuePay.

== Description ==

Allows user to made payment on Gravity Forms using ValuePay.

= Notes: =
- It is required to create and map field for Identity Type, Identity Value, Bank and Payment Type if recurring payment is enabled (mandate ID is filled).
- Identity Value field must be Text field while Identity Type, Bank and Payment Type field must be Drop Down field.
- Recurring payment only creates one payment record in Gravity Forms with "Authorized" status.

== Installation ==

1. Log in to your WordPress admin.
2. Search plugins "ValuePay for Gravity Forms" and click "Install Now".
3. Activate the plugin.

== Changelog ==

= 1.0.3 - 2022-04-21 =
- Modified: Improve instant payment notification response data sanitization
- Modified: Sanitize return URL

= 1.0.2 - 2022-04-10 =
- Modified: Improve instant payment notification response data sanitization

= 1.0.1 - 2022-03-09 =
- Modified: Minor improvements

= 1.0.0 - 2022-02-18 =
- Initial release of the plugin
