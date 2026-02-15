<?php
/*
Plugin Name: Contact Form
Plugin URI: https://www.littlebizzy.com/plugins/contact-form
Description: Intuitive WordPress contact form
Version: 1.2.1
Requires PHP: 7.0
Tested up to: 6.9
Author: LittleBizzy
Author URI: https://www.littlebizzy.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Update URI: false
GitHub Plugin URI: littlebizzy/contact-form
Primary Branch: master
Text Domain: contact-form
*/

// prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// override wordpress.org with git updater
add_filter( 'gu_override_dot_org', function( $overrides ) {
	$overrides[] = 'contact-form/contact-form.php';
	return $overrides;
}, 999 );

// display contact form shortcode
add_shortcode( 'contact_form', 'contact_form_display' );
function contact_form_display( $atts = array() ) {
	// parse shortcode attributes
	$args = shortcode_atts(
		array(
			'show_url' => 'true',
		),
		$atts,
		'contact_form'
	);

	$show_url = ( $args['show_url'] === 'true' );

	// enqueue js when shortcode renders
	wp_enqueue_script(
		'contact-form',
		plugin_dir_url( __FILE__ ) . 'contact-form.js',
		array( 'jquery' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'contact-form.js' ),
		true
	);

	// pass ajax url and nonce to js
	wp_localize_script(
		'contact-form',
		'contactForm',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'contact_form_nonce' ),
		)
	);

	// only show form to logged-in users
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return '<p>' . esc_html__( 'You must be logged in to contact us.', 'contact-form' ) . '</p>';
	}

	// get current user data
	$user_id = $user->ID;
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	$full_name = trim( $first_name . ' ' . $last_name );
	$name_value = ! empty( $full_name ) ? $full_name : $user->display_name;
	$email = $user->user_email;

	// get billing phone if woocommerce active
	$billing_phone = class_exists( 'WooCommerce' ) ? get_user_meta( $user_id, 'billing_phone', true ) : '';
	$phone_value = ! empty( $billing_phone ) ? $billing_phone : __( 'Not Available', 'contact-form' );

	// fetch recent orders if woocommerce is active
	$orders = class_exists( 'WooCommerce' ) ? wc_get_orders( array(
		'customer_id' => $user_id,
		'status' => array(
			'wc-pending',
			'wc-processing',
			'wc-on-hold',
			'wc-completed',
			'wc-cancelled',
			'wc-refunded',
			'wc-failed',
		),
		'type' => 'shop_order',
		'orderby' => 'date',
		'order' => 'DESC',
		'limit' => 15,
		'return' => 'objects',
	) ) : array();

	// fetch recent subscriptions if subscriptions plugin is active
	$subscriptions = ( class_exists( 'WC_Subscriptions' ) && function_exists( 'wcs_get_subscriptions_for_user' ) ) ? wcs_get_subscriptions_for_user( $user_id, array(
		'post_status' => array(
			'wc-pending',
			'wc-active',
			'wc-on-hold',
			'wc-pending-cancel',
			'wc-cancelled',
			'wc-expired',
			'wc-switched',
		),
		'orderby' => 'date',
		'order' => 'DESC',
		'limit' => 15,
		'return' => 'subscriptions',
	) ) : array();

	ob_start(); ?>
	<form id="contact-form" method="post">
		<p>
			<label for="contact-name"><?php esc_html_e( 'Name', 'contact-form' ); ?></label>
			<input type="text" id="contact-name" readonly value="<?php echo esc_attr( $name_value ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label for="contact-email"><?php esc_html_e( 'Email', 'contact-form' ); ?></label>
			<input type="email" id="contact-email" readonly value="<?php echo esc_attr( $email ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label for="contact-phone"><?php esc_html_e( 'Phone', 'contact-form' ); ?></label>
			<input type="text" id="contact-phone" readonly value="<?php echo esc_attr( $phone_value ); ?>" style="background-color: #f5f5f5;">
		</p>
		<?php if ( $show_url ) : ?>
		<p>
			<label for="contact-url"><?php esc_html_e( 'URL', 'contact-form' ); ?></label>
			<input type="url" id="contact-url" name="contact_url">
		</p>
		<?php endif; ?>
		<?php if ( ! empty( $orders ) || ! empty( $subscriptions ) ) : ?>
		<p>
			<label for="contact-reference"><?php esc_html_e( 'Order or Subscription', 'contact-form' ); ?></label>
			<select id="contact-reference" name="contact_reference">
				<option value=""><?php esc_html_e( 'Select Order or Subscription', 'contact-form' ); ?></option>
				<?php foreach ( $orders as $order ) : ?>
					<?php
					$product_names = array();
					foreach ( $order->get_items() as $item ) {
						$product_names[] = $item->get_name();
					}
					$product_list = implode( ', ', $product_names );
					?>
					<option value="order_<?php echo esc_attr( $order->get_id() ); ?>">
						<?php printf(
							esc_html__( 'Order #%1$s – %2$s – %3$s', 'contact-form' ),
							esc_html( $order->get_id() ),
							esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) ),
							esc_html( $product_list )
						); ?>
					</option>
				<?php endforeach; ?>
				<?php foreach ( $subscriptions as $subscription ) : ?>
					<?php
					$product_names = array();
					foreach ( $subscription->get_items() as $item ) {
						$product_names[] = $item->get_name();
					}
					$product_list = implode( ', ', $product_names );
					?>
					<option value="subscription_<?php echo esc_attr( $subscription->get_id() ); ?>">
						<?php printf(
							esc_html__( 'Subscription #%1$s – %2$s – %3$s', 'contact-form' ),
							esc_html( $subscription->get_id() ),
							esc_html( $subscription->get_date_created()->date_i18n( get_option( 'date_format' ) ) ),
							esc_html( $product_list )
						); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php endif; ?>
		<p>
			<label for="contact-subject"><?php esc_html_e( 'Subject', 'contact-form' ); ?></label>
			<input type="text" id="contact-subject" name="contact_subject" required>
		</p>
		<p>
			<label for="contact-message"><?php esc_html_e( 'Message', 'contact-form' ); ?></label>
			<textarea id="contact-message" name="contact_message" rows="10" cols="40" required></textarea>
		</p>
		<input type="hidden" name="action" value="contact_form_submit">
		<?php wp_nonce_field( 'contact_form_nonce', 'nonce' ); ?>
		<p><input type="submit" value="<?php esc_attr_e( 'Send Message', 'contact-form' ); ?>"></p>
		<div id="contact-form-response"></div>
	</form>
	<?php
	return ob_get_clean();
}

// handle ajax submission for contact form
add_action( 'wp_ajax_contact_form_submit', 'contact_form_submit' );
function contact_form_submit() {
	// verify nonce
	check_ajax_referer( 'contact_form_nonce', 'nonce' );

	// ensure user is logged in
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		wp_send_json_error( apply_filters( 'contact_form_error_not_logged_in', __( 'Not logged in', 'contact-form' ) ) );
	}

	// collect user data
	$user_id = $user->ID;
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	$full_name = trim( $first_name . ' ' . $last_name );
	$name_value = ! empty( $full_name ) ? $full_name : $user->display_name;
	$email = $user->user_email;

	// get billing phone if woocommerce active
	$billing_phone = class_exists( 'WooCommerce' ) ? get_user_meta( $user_id, 'billing_phone', true ) : '';
	$phone_value = ! empty( $billing_phone ) ? $billing_phone : __( 'Not Available', 'contact-form' );

	// sanitize user inputs
	$subject = sanitize_text_field( wp_unslash( $_POST['contact_subject'] ?? '' ) );
	$url = esc_url_raw( wp_unslash( $_POST['contact_url'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ?? '' ) );
	$reference = sanitize_text_field( wp_unslash( $_POST['contact_reference'] ?? '' ) );

	// check required fields
	if ( empty( $subject ) || empty( $message ) ) {
		wp_send_json_error( apply_filters( 'contact_form_error_validation', __( 'Subject and message are required', 'contact-form' ) ) );
	}

	// build email body
	$email_body  = "Name: {$name_value}\n";
	$email_body .= "Email: {$email}\n";
	$email_body .= "Phone: {$phone_value}\n";
	if ( ! empty( $url ) ) {
		$email_body .= "URL: {$url}\n";
	}
	$email_body .= "Reference: {$reference}\n";
	$email_body .= "Subject: {$subject}\n";
	$email_body .= "Message: {$message}\n";

	// send email to admin
	$headers = array( 'Reply-To: ' . $email );
	$sent = wp_mail(
		get_option( 'admin_email' ),
		sprintf( __( 'Contact Form: %s', 'contact-form' ), $subject ),
		$email_body,
		$headers
	);

	// return response
	if ( $sent ) {

		do_action( 'contact_form_sent', array(
			'name' => $name_value,
			'email' => $email,
			'phone' => $phone_value,
			'subject' => $subject,
			'message' => $message,
			'reference' => $reference,
			'url' => $url,
		) );

		wp_send_json_success( apply_filters( 'contact_form_success_message', __( 'Message sent successfully.', 'contact-form' ) ) );
	}

	wp_send_json_error( apply_filters( 'contact_form_error_send_failed', __( 'Failed to send message.', 'contact-form' ) ) );
}

// Ref: ChatGPT
