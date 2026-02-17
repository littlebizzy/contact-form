# Contact Form

Intuitive WordPress contact form

## Changelog

### 1.2.3
- validated reference field against expected order/subscription format
- moved required field checks immediately after input sanitization
- added fallback to admin email if site domain cannot generate valid noreply address
- improved internal validation flow and code ordering

### 1.2.2
- added strict server-side validation for user email using `sanitize_email()` and `is_email()`
- stripped CR/LF characters from subject and reply-to fields to prevent header injection
- switched `From` address to domain-aligned noreply@site-domain for improved SPF/DMARC compatibility
- preserved user email in `Reply-To` header
- enforced UTF-8 content-type header for outgoing mail
- standardized RFC-compliant CRLF line endings in email body for SMTP compliance

### 1.2.1
- added `do_action` hook after successful submission
- added granular filters for success and error messages

### 1.1.1
- tweaked order of plugin headers

### 1.1.0
- changed how frontend scripts are loaded so the contact form JavaScript is enqueued when the shortcode actually renders, instead of trying to detect it from page content
- ensures the contact form works reliably when used in pages, custom templates, blocks, widgets, or via `do_shortcode()`
- removes brittle shortcode detection logic based on parsing post content
- no changes to access control; the contact form is still restricted to logged-in users only
- bumped `Tested up to:` to 6.9

### 1.0.5
- added `Reply-To` email header using the logged-in user's address

### 1.0.4
- fixed slashes in input fields using `wp_unslash()` before sanitizing

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
