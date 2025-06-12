# Contact Form

Intuitive WordPress contact form

## Changelog

### 1.0.3
- added support for `[contact_form show_url="false"]` to optionally hide the URL field
- email body skips "URL" line if field is empty or hidden

### 1.0.2
- fixed response div element name to `#contact-form-response`
- added color styling for JS success and error messages

### 1.0.1
- moved order/subscription dropdown above subject field
- improved dropdown labels to include product or subscription names

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
