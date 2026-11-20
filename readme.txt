=== EpiFive Website Privacy Kit ===
Contributors: epifive
Tags: dpdp, consent, privacy, cookie banner, india
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

DPDP consent banner, script gating and privacy hub links for WordPress, connected to your own EpiFive Website Privacy Kit server.

== Description ==

EpiFive Website Privacy Kit helps Indian websites organise the tasks of the Digital Personal Data Protection Act, 2023 and the DPDP Rules, 2025: an itemised privacy notice in English and Hindi, a purpose-wise consent banner, consent records with a hash chain, a privacy hub with a rights-request inbox, a breach register and a retention schedule.

This plugin connects your WordPress site to **your own** Privacy Kit server (the open-source Free edition, or the Pro edition, both self-hosted):

* **Consent banner** on every page, loaded from your server: Accept all, Reject all and Choose with equal weight, nothing pre-ticked, English and Hindi.
* **Script gating:** list script handles (for example `google-analytics=analytics`) and those scripts run only after the visitor agrees to that purpose. Scripts in your theme can use `type="text/plain" data-wpk-purpose="analytics"`.
* **Google Consent Mode v2 defaults** printed before any Google tag.
* **Privacy hub link:** `[wpk_privacy_link]` shortcode or the "Privacy hub link" block (notice, rights requests, withdraw consent, grievance contact).
* **Privacy choices link:** `[wpk_privacy_choices]` reopens the preference centre.
* **Form consent checkboxes:** `[wpk_form_consent purposes="marketing_email" field="email"]` in any form, a `[wpk_consent marketing_email field:your-email]` form tag for Contact Form 7, or automatic checkboxes in every Contact Form 7 or WPForms form. Each choice is recorded in the consent log with the email address.
* Translation-ready, with Hindi included.

**Not legal advice.** The plugin and the Privacy Kit help you organise DPDP tasks; they do not make your website compliant on their own and are not a substitute for a lawyer. You (the business) are the Data Fiduciary. Legal texts are drafts pending lawyer review. The Privacy Kit is **not a Consent Manager** registered with the Data Protection Board of India.

= What you need =

A running EpiFive Website Privacy Kit server:

* Free edition (open source, MIT): https://github.com/epifive/website-privacy-kit
* Pro edition (many websites, team roles, white-label, API and webhooks): https://www.epifive.com/products/website-privacy-kit-pro

Hosted plans are coming soon.

== External services ==

This plugin connects to **one server only: the Privacy Kit server address you enter in Settings → Website Privacy Kit** (usually your own server). Nothing is sent anywhere else, and no tracking is added.

* **What is loaded:** the banner script `w.js` from your server on public pages, and the banner settings (`/api/v1/sites/<site key>/config`) when you press "Test connection" or when the privacy hub address is looked up (cached for 12 hours).
* **What is sent, and when:** visitors' browsers send their consent choices (purpose keys, notice version, language, page address, a random browser id, and the email address entered in a form where you added consent checkboxes) to your server when they make a choice. The WordPress server sends only your public site key when it looks up the banner settings.
* The server's terms and privacy notice are your own (it is your installation). For the software itself see https://github.com/epifive/website-privacy-kit and https://www.epifive.com.

== Installation ==

1. Install and set up the Privacy Kit on your server (see its README), publish your notice and add this website under Dashboard → Websites. Add your WordPress domain to the website's domain or allowed origins.
2. Install this plugin from Plugins → Add New (or upload the ZIP) and activate it.
3. Go to Settings → Website Privacy Kit, paste the server address (https://…) and the site key (starts with `pk_`), and press "Test connection".
4. Optional: list script handles to gate, add `[wpk_privacy_link]` and `[wpk_privacy_choices]` (or the block) to your footer, and add consent checkboxes to your forms.

== Frequently Asked Questions ==

= Does this plugin make my site DPDP compliant? =

No software can do that on its own. It helps you meet DPDP requirements by showing a clear banner, recording consent with proof and linking to your notice and rights request form. Check your notice with a lawyer.

= Is this a Consent Manager? =

No. A Consent Manager is a company registered with the Data Protection Board of India. This is a consent banner and consent records for your own website; your records stay on your server.

= Which scripts should I gate? =

Analytics, advertising and marketing tags (Google Analytics, Meta Pixel, remarketing tags). Use the purpose keys you set up in the Privacy Kit dashboard, for example `analytics` or `ads`. Scripts needed for the site to work should not be gated.

= My form's email field has another name =

Use `field="your-field-name"` in the shortcode, `field:your-field-name` in the Contact Form 7 tag, or the field-name settings. Without a match, the first email input of the form is used.

= Does it work with caching plugins? =

Yes. The banner runs in the visitor's browser, so cached pages work. After changing the gated handles, clear your page cache.

== Screenshots ==

1. Settings → Website Privacy Kit: server address, site key, gated scripts and form checkboxes.
2. The consent banner on a WordPress site.

== Changelog ==

= 1.0.0 =
* First release: banner loader, script gating, Consent Mode v2 defaults, privacy hub link shortcode and block, privacy choices link, form consent checkboxes (shortcode, Contact Form 7 tag and integration, WPForms), Hindi translation.

== Upgrade Notice ==

= 1.0.0 =
First release.
