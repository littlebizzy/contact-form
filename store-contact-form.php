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

// display contact form shortcode
add_shortcode( 'store_contact_form', 'store_contact_form_display' );
function store_contact_form_display() {
	// only show form to logged-in users
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
    	return '<p>' . esc_html__( 'You must be logged in to contact us.', 'store-contact-form' ) . '</p>';
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
	$phone_value = ! empty( $billing_phone ) ? $billing_phone : __( 'Not Available', 'store-contact-form' );

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
	<form id="store-contact-form" method="post">
		<p>
			<label for="store-contact-name"><?php esc_html_e( 'Name', 'store-contact-form' ); ?></label>
			<input type="text" id="store-contact-name" readonly value="<?php echo esc_attr( $name_value ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label for="store-contact-email"><?php esc_html_e( 'Email', 'store-contact-form' ); ?></label>
			<input type="email" id="store-contact-email" readonly value="<?php echo esc_attr( $email ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label for="store-contact-phone"><?php esc_html_e( 'Phone', 'store-contact-form' ); ?></label>
			<input type="text" id="store-contact-phone" readonly value="<?php echo esc_attr( $phone_value ); ?>" style="background-color: #f5f5f5;">
		</p>
		<p>
			<label for="store-contact-url"><?php esc_html_e( 'URL', 'store-contact-form' ); ?></label>
			<input type="url" id="store-contact-url" name="contact_url">
		</p>
		<p>
			<label for="store-contact-subject"><?php esc_html_e( 'Subject', 'store-contact-form' ); ?></label>
			<input type="text" id="store-contact-subject" name="contact_subject" required>
		</p>
		<p>
			<label for="store-contact-message"><?php esc_html_e( 'Message', 'store-contact-form' ); ?></label>
			<textarea id="store-contact-message" name="contact_message" rows="10" cols="40" required></textarea>
		</p>
        <?php if ( ! empty( $orders ) || ! empty( $subscriptions ) ) : ?>
            <p>
                <label for="store-contact-reference"><?php esc_html_e( 'Order or Subscription', 'store-contact-form' ); ?></label>
                <select id="store-contact-reference" name="contact_reference">
                    <option value=""><?php esc_html_e( 'Select Order or Subscription', 'store-contact-form' ); ?></option>
                    <?php foreach ( $orders as $order ) : ?>
                        <option value="order_<?php echo esc_attr( $order->get_id() ); ?>">
                            <?php printf(
                                esc_html__( 'Order #%1$s – %2$s', 'store-contact-form' ),
                                esc_html( $order->get_id() ),
                                esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) )
                            ); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ( $subscriptions as $subscription ) : ?>
                        <option value="subscription_<?php echo esc_attr( $subscription->get_id() ); ?>">
                            <?php printf(
                                esc_html__( 'Subscription #%1$s – %2$s', 'store-contact-form' ),
                                esc_html( $subscription->get_id() ),
                                esc_html( $subscription->get_date_created()->date_i18n( get_option( 'date_format' ) ) )
                            ); ?>
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
	<?php
	return ob_get_clean();
}

// enqueue js only if shortcode exists and user is logged in
add_action( 'wp_enqueue_scripts', 'store_contact_form_enqueue_js' );
function store_contact_form_enqueue_js() {
	// skip if user not logged in
	if ( ! is_user_logged_in() ) {
		return;
	}

    // get current queried post to reliably detect shortcode presence
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content ?? '', 'store_contact_form' ) ) {
        return;
    }

	// enqueue contact form script
	wp_enqueue_script(
		'store-contact-form',
		plugin_dir_url( __FILE__ ) . 'store-contact-form.js',
		array( 'jquery' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'store-contact-form.js' ),
		true
	);

	// pass ajax url and nonce to js
	wp_localize_script(
		'store-contact-form',
		'storeContactForm',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'store_contact_form_nonce' ),
		)
	);
}

// handle ajax submission for contact form
add_action( 'wp_ajax_store_contact_form_submit', 'store_contact_form_submit' );
function store_contact_form_submit() {
	// verify nonce
	check_ajax_referer( 'store_contact_form_nonce', 'nonce' );

	// ensure user is logged in
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		wp_send_json_error( __( 'Not logged in', 'store-contact-form' ) );
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
	$phone_value = ! empty( $billing_phone ) ? $billing_phone : __( 'Not Available', 'store-contact-form' );

	// sanitize user inputs
	$subject = sanitize_text_field( $_POST['contact_subject'] ?? '' );
	$url = esc_url_raw( $_POST['contact_url'] ?? '' );
	$message = sanitize_textarea_field( $_POST['contact_message'] ?? '' );
	$reference = sanitize_text_field( $_POST['contact_reference'] ?? '' );

	// check required fields
	if ( empty( $subject ) || empty( $message ) ) {
		wp_send_json_error( __( 'Subject and message are required', 'store-contact-form' ) );
	}

	// build email body
	$email_body  = "Name: {$name_value}\n";
	$email_body .= "Email: {$email}\n";
	$email_body .= "Phone: {$phone_value}\n";
	$email_body .= "URL: {$url}\n";
	$email_body .= "Reference: {$reference}\n";
	$email_body .= "Subject: {$subject}\n";
	$email_body .= "Message: {$message}\n";

	// send email to admin
    $sent = wp_mail(
        get_option( 'admin_email' ),
        sprintf( __( 'Contact Form: %s', 'store-contact-form' ), $subject ),
        $email_body
    );

	// return response
	if ( $sent ) {
		wp_send_json_success( __( 'Message sent successfully.', 'store-contact-form' ) );
	}

	wp_send_json_error( __( 'Failed to send message.', 'store-contact-form' ) );
}

// Ref: ChatGPT
