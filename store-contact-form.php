<?php
/*
Plugin Name: Store Contact Form
Plugin URI: https://www.littlebizzy.com/plugins/store-contact-form
Description: Easy contact form for WooCommerce
Version: 1.0.0
Author: LittleBizzy
Author URI: https://www.littlebizzy.com
Requires PHP: 7.0
Tested up to: 6.7
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Update URI: false
GitHub Plugin URI: littlebizzy/store-contact-form
Primary Branch: master
Text Domain: store-contact-form
*/

// prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// override wordpress.org with git updater
add_filter( 'gu_override_dot_org', function( $overrides ) {
	$overrides[] = 'store-contact-form/store-contact-form.php';
	return $overrides;
}, 999 );

// display contact form
add_shortcode( 'store_contact_form', 'store_contact_form_display' );
function store_contact_form_display() {
	// only show form to logged-in users
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'you must be logged in to contact us.', 'store-contact-form' ) . '</p>';
	}

	// get user data
	$user_id       = get_current_user_id();
	$user          = wp_get_current_user();
	$billing_phone = get_user_meta( $user_id, 'billing_phone', true );

	// fetch recent orders if WooCommerce is active
	$orders = class_exists( 'WooCommerce' )
		? wc_get_orders( array(
			'customer_id' => $user_id,
			'limit'       => 10,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) )
		: array();

	// fetch subscriptions if Subscriptions plugin is active
	$subscriptions = ( function_exists( 'wcs_get_subscriptions_for_user' ) && class_exists( 'WC_Subscriptions' ) )
		? wcs_get_subscriptions_for_user( $user_id, array( 'order_type' => 'any' ) )
		: array();

	// build form
	ob_start(); ?>
	<form id="store-contact-form">
		<label><?php _e( 'name', 'store-contact-form' ); ?></label>
		<input type="text" readonly value="<?php echo esc_attr( $user->display_name ); ?>"><br>

		<label><?php _e( 'email', 'store-contact-form' ); ?></label>
		<input type="email" readonly value="<?php echo esc_attr( $user->user_email ); ?>"><br>

		<label><?php _e( 'phone', 'store-contact-form' ); ?></label>
		<input type="text" readonly value="<?php echo esc_attr( $billing_phone ); ?>"><br>

		<label><?php _e( 'url', 'store-contact-form' ); ?></label>
		<input type="url" name="contact_url"><br>

		<label><?php _e( 'subject', 'store-contact-form' ); ?></label>
		<input type="text" name="contact_subject"><br>

		<label><?php _e( 'message', 'store-contact-form' ); ?></label>
		<textarea name="contact_message"></textarea><br>

		<?php if ( ! empty( $orders ) || ! empty( $subscriptions ) ) : ?>
			<label><?php _e( 'order or subscription', 'store-contact-form' ); ?></label>
			<select name="contact_reference">
				<option value=""><?php _e( 'select', 'store-contact-form' ); ?></option>
				<?php foreach ( $orders as $order ) : ?>
					<option value="order_<?php echo esc_attr( $order->get_id() ); ?>">
						<?php
						echo sprintf(
							__( 'Order #%1$s – %2$s', 'store-contact-form' ),
							esc_html( $order->get_id() ),
							esc_html( $order->get_date_created()->date( 'Y-m-d' ) )
						);
						?>
					</option>
				<?php endforeach; ?>
				<?php foreach ( $subscriptions as $subscription ) : ?>
					<option value="subscription_<?php echo esc_attr( $subscription->get_id() ); ?>">
						<?php
						echo sprintf(
							__( 'Subscription #%1$s – %2$s', 'store-contact-form' ),
							esc_html( $subscription->get_id() ),
							esc_html( $subscription->get_date_created()->date( 'Y-m-d' ) )
						);
						?>
					</option>
				<?php endforeach; ?>
			</select><br>
		<?php endif; ?>

		<input type="hidden" name="action" value="store_contact_form_submit">
		<?php wp_nonce_field( 'store_contact_form_nonce', 'nonce' ); ?>
		<input type="submit" value="<?php esc_attr_e( 'Send', 'store-contact-form' ); ?>">
		<div id="store-contact-response"></div>
	</form>
	<?php
	return ob_get_clean();
}

// enqueue js only if shortcode exists and user logged in
add_action( 'wp_enqueue_scripts', 'store_contact_form_enqueue_js' );
function store_contact_form_enqueue_js() {
	// skip if user not logged in
	if ( ! is_user_logged_in() ) {
		return;
	}

	// check for shortcode in post content
	global $post;
	if ( empty( $post ) || ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	// load js only if contact form shortcode is found
	if ( has_shortcode( $post->post_content, 'store_contact_form' ) ) {
		wp_enqueue_script(
			'store-contact-form',
			plugin_dir_url( __FILE__ ) . 'store-contact-form.js',
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'store-contact-form.js' ),
			true
		);

		// pass AJAX URL to script
		wp_localize_script( 'store-contact-form', 'storeContactForm', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}
}

// handle ajax form submission
add_action( 'wp_ajax_store_contact_form_submit', 'store_contact_form_submit' );
function store_contact_form_submit() {
	// verify nonce
	check_ajax_referer( 'store_contact_form_nonce', 'nonce' );

	// ensure user is logged in
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		wp_send_json_error( __( 'not logged in', 'store-contact-form' ) );
	}

	// sanitize input fields
	$subject      = sanitize_text_field( $_POST['contact_subject'] ?? '' );
	$url          = esc_url_raw( $_POST['contact_url'] ?? '' );
	$message_body = sanitize_textarea_field( $_POST['contact_message'] ?? '' );
	$reference    = sanitize_text_field( $_POST['contact_reference'] ?? '' );

	// validate required fields
	if ( empty( $subject ) || empty( $message_body ) ) {
		wp_send_json_error( __( 'subject and message are required', 'store-contact-form' ) );
	}

	// build message
	$message  = "Subject: {$subject}\n";
	$message .= "URL: {$url}\n";
	$message .= "Message: {$message_body}\n";
	$message .= "Reference: {$reference}\n";
	$message .= "User: {$user->display_name} ({$user->user_email})\n";

	// send email
	$sent = wp_mail(
		get_option( 'admin_email' ),
		__( 'Store Contact Form Submission', 'store-contact-form' ),
		$message
	);

	// return json result
	if ( $sent ) {
		wp_send_json_success( __( 'Message sent successfully.', 'store-contact-form' ) );
	} else {
		wp_send_json_error( __( 'Failed to send message.', 'store-contact-form' ) );
	}
}

// Ref: ChatGPT
