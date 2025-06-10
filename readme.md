# Contact Form

Intuitive WordPress contact form

## Changelog

### 1.0.0
- initial release
- supports PHP 7.0 to 8.3
- no settings page
- uses `[contact_form]` shortcode only
- displays simple contact form for logged-in users
- automatically pre-fills full name and and phone from WooCommerce billing data (and email from WordPress user data)
- falls back to using display name if full name is unavailable or empty
- optional drop-down select of WooCommerce orders and subscriptions to reference
- optional URL field
- validates fields with AJAX and HTML5
- sends submission to site admin email using `wp_mail()`
- includes nonce security verification and `$_POST` field whitelisting
- includes AJAX error handling and status messages
