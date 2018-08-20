<?php
/**
 * Plugin Name: Wholesaler Tax ID for WooCommerce
 * Plugin URI: https://
 * Description: Add Tax ID field to WooCommerce Registration, Account, and Checkout. If Tax ID is set, remove taxes from cart and checkout.
 * Author: WolfEsq
 * Version: 1.0.0
 * Author URI: https://
 *
 * @package WholesaleTaxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// incorporate new tax fields.
require dirname( __FILE__ ) . '/includes/tax-id.php';


/**
 * Remove taxes at checkout if Tax ID is entered.
 */
function wt_taxexempt_checkout_update_order_review() {

	global $woocommerce;

	// Set exempt status as false.
	$woocommerce->customer->set_is_vat_exempt( false );

	// Check to see if the Tax ID has been entered, then remove taxes if it has.
	if ( isset( $shipping_method_wt_register_tax_id ) && '' !== $shipping_method_wt_register_tax_id ) {
		$woocommerce->customer->set_is_vat_exempt( true );
	}
}
add_action( 'woocommerce_checkout_update_order_review', 'wt_taxexempt_checkout_update_order_review' );



/**
 * Remove taxes from WooCommerce cart if user is logged in and has a Tax ID saved.
 */
function update_cart_for_tax_exemption() {

	// Check if user is logged in.
	if ( is_user_logged_in() === true ) {

		// Check if user has a Tax ID.
		$tax_id = get_user_meta( get_current_user_id(), 'shipping_method_wt_register_tax_id', true );
		if ( '' !== $tax_id ) {

			// Set as tax exempt.
			global $woocommerce;
			$woocommerce->customer->set_is_vat_exempt( true );
		}
	}
}
add_action( 'woocommerce_cart_actions', 'update_cart_for_tax_exemption' );



/**
 * Remove taxes from WooCommerce checkout page if user is logged in and has a Tax ID saved.
 */
function update_checkout_for_tax_exemption() {

	// Check if user is logged in.
	if ( is_user_logged_in() === true ) {

		// Check if user has a Tax ID.
		$tax_id = get_user_meta( get_current_user_id(), 'shipping_method_wt_register_tax_id', true );
		if ( '' !== $tax_id ) {

			// Set as tax exempt.
			global $woocommerce;
			$woocommerce->customer->set_is_vat_exempt( true );

		}
	}
}
add_action( 'woocommerce_checkout_update_order_review', 'update_checkout_for_tax_exemption' );



/**
 * Add notice to WooCommerce checkout page if user doesn't have a Tax ID.
 */
function wt_add_checkout_notice() {

	// Check if user is logged in.
	if ( is_user_logged_in() === true ) {

		// Check if user has a Tax ID.
		$tax_id = get_user_meta( get_current_user_id(), 'shipping_method_wt_register_tax_id', true );
		if ( '' !== $tax_id ) {

			// Do nothing if user has a Tax ID.
			return;
		} else {
			// User doesn't have a Tax ID.
			// Print notice prompting user to save a Tax ID.
			wc_print_notice( __( 'Want to shop tax-free? <a href="/my-account/edit-account/">Click here to save a Tax ID to your account.</a>', 'woocommerce' ), 'notice' );
		}
	} else {
		// User is not logged in.
		// Print notice prompting user to register and save a Tax ID.
		wc_print_notice( __( 'Want to shop tax-free? <a href="/my-account/edit-account/">Click here to register an account with your Tax ID.</a>', 'woocommerce' ), 'notice' );
	}
}
add_action( 'woocommerce_before_checkout_form', 'wt_add_checkout_notice', 11 );


/**
 * Add notice to WooCommerce shopping cart page if user doesn't have a Tax ID.
 */
function wt_add_cart_notice() {

	// Check if user is logged in.
	if ( is_user_logged_in() === true ) {

		// Check if user has a Tax ID.
		$tax_id = get_user_meta( get_current_user_id(), 'shipping_method_wt_register_tax_id', true );
		if ( '' !== $tax_id ) {

			// Do nothing if user has a Tax ID.
			return;
		} else {
			// User doesn't have a Tax ID.
			// Print notice prompting user to save a Tax ID.
			wc_print_notice( __( 'Want to shop tax-free? <a href="/my-account/edit-account/">Click here to save a Tax ID to your account.</a>', 'woocommerce' ), 'notice' );
		}
	} else {
		// User is not logged in.
		// Print notice prompting user to register and save a Tax ID.
		wc_print_notice( __( 'Want to shop tax-free? <a href="/my-account/edit-account/">Click here to register an account with your Tax ID.</a>', 'woocommerce' ), 'notice' );
	}
}
add_action( 'woocommerce_cart_totals_after_order_total', 'wt_add_cart_notice', 11 );
