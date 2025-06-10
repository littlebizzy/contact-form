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
		return '<p>' . esc_html__( 'You must be logged in to contact us.', 'store-contact-form' ) . '</p>';
	}

	// get user data
	$user = wp_get_current_user();
	$user_id = $user->ID;
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	$full_name = trim( $first_name . ' ' . $last_name );
	$name_value = ! empty( $full_name ) ? $full_name : $user->display_name;
	$billing_phone = class_exists( 'WooCommerce' ) ? get_user_meta( $user_id, 'billing_phone', true ) : '';
	$phone_value = ! empty( $billing_phone ) ? $billing_phone : __( 'Not Available', 'store-contact-form' );

    // fetch recent orders if woocommerce is active
    $orders = class_exists( 'WooCommerce' )
        ? wc_get_orders( array(
            'customer_id' => $user_id,
            'limit'       => 15,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) )
        : array();

    // fetch subscriptions if subscriptions plugin is active
    $subscriptions = (
        function_exists( 'wcs_get_subscriptions_for_user' )
        && class_exists( 'WC_Subscriptions' )
    ) ? wcs_get_subscriptions_for_user( $user_id, array(
            'order_type' => 'any',
        ) )
        : array();

	ob_start(); ?>
	<form id="store-contact-form">
		<p>
			<label><?php _e( 'Name', 'store-contact-form' ); ?></label>
			<input type="text" readonly value="<?php echo esc_attr( $name_value ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label><?php _e( 'Email', 'store-contact-form' ); ?></label>
			<input type="email" readonly value="<?php echo esc_attr( $user->user_email ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label><?php _e( 'Phone', 'store-contact-form' ); ?></label>
			<input type="text" readonly value="<?php echo esc_attr( $phone_value ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label><?php _e( 'URL', 'store-contact-form' ); ?></label>
			<input type="url" name="contact_url">
		</p>
		<p>
			<label><?php _e( 'Subject', 'store-contact-form' ); ?></label>
			<input type="text" name="contact_subject">
		</p>
		<p>
			<label><?php _e( 'Message', 'store-contact-form' ); ?></label>
			<textarea name="contact_message" rows="10" cols="40"></textarea>
		</p>
		<?php if ( ! empty( $orders ) || ! empty( $subscriptions ) ) : ?>
			<p>
				<label><?php _e( 'Order or subscription', 'store-contact-form' ); ?></label>
				<select name="contact_reference">
					<option value=""><?php _e( 'Select Related Order', 'store-contact-form' ); ?></option>
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
				</select>
			</p>
		<?php endif; ?>
		<input type="hidden" name="action" value="store_contact_form_submit">
		<?php wp_nonce_field( 'store_contact_form_nonce', 'nonce' ); ?>
		<p><input type="submit" value="<?php esc_attr_e( 'Send Message', 'store-contact-form' ); ?>"></p>
		<div id="store-contact-response"></div>
	</form>
	<?php return ob_get_clean();
}

// enqueue js only if shortcode exists and user is logged in
add_action( 'wp_enqueue_scripts', 'store_contact_form_enqueue_js' );
function store_contact_form_enqueue_js() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	global $post;
	if ( empty( $post ) || ! is_a( $post, 'WP_Post' ) ) {
		return;
	}
	if ( has_shortcode( $post->post_content, 'store_contact_form' ) ) {
		wp_enqueue_script(
			'store-contact-form',
			plugin_dir_url( __FILE__ ) . 'store-contact-form.js',
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'store-contact-form.js' ),
			true
		);
		wp_localize_script( 'store-contact-form', 'storeContactForm', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}
}

add_action( 'wp_ajax_store_contact_form_submit', 'store_contact_form_submit' );
function store_contact_form_submit() {
	check_ajax_referer( 'store_contact_form_nonce', 'nonce' );

	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		wp_send_json_error( __( 'Not logged in', 'store-contact-form' ) );
	}

	$user_id    = $user->ID;
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name  = get_user_meta( $user_id, 'last_name', true );
	$full_name  = trim( $first_name . ' ' . $last_name );
	$name       = ! empty( $full_name ) ? $full_name : $user->display_name;
	$email      = sanitize_email( $user->user_email );
	$phone      = get_user_meta( $user_id, 'billing_phone', true );
	$phone      = ! empty( $phone ) ? sanitize_text_field( $phone ) : __( 'Not Available', 'store-contact-form' );

	$subject   = sanitize_text_field( $_POST['contact_subject'] ?? '' );
	$url       = esc_url_raw( $_POST['contact_url'] ?? '' );
	$message   = sanitize_textarea_field( $_POST['contact_message'] ?? '' );
	$reference = sanitize_text_field( $_POST['contact_reference'] ?? '' );

	if ( empty( $subject ) || empty( $message ) ) {
		wp_send_json_error( __( 'Subject and message are required', 'store-contact-form' ) );
	}

	$email_body  = "Name: {$name}\n";
	$email_body .= "Email: {$email}\n";
	$email_body .= "Phone: {$phone}\n";
	$email_body .= "URL: {$url}\n";
	$email_body .= "Subject: {$subject}\n";
	$email_body .= "Message: {$message}\n";
	$email_body .= "Reference: {$reference}\n";

	$sent = wp_mail(
		get_option( 'admin_email' ),
		__( 'Store Contact Form Submission', 'store-contact-form' ),
		$email_body
	);

	if ( $sent ) {
		wp_send_json_success( __( 'Message sent successfully.', 'store-contact-form' ) );
	} else {
		wp_send_json_error( __( 'Failed to send message.', 'store-contact-form' ) );
	}
}

// Ref: ChatGPT
