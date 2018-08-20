<?php
/**
 * Hook into WooCommerce Account Fields
 *
 * @package WholesaleTaxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define registration fields for Name, Company, and Tax ID
 */
function wt_get_account_fields() {
	return apply_filters(
		'wt_account_fields',
		array(
			'wt-register-name'                   => array(
				'type'                 => 'text',
				'label'                => __( 'Name', 'wt' ),
				'hide_in_account'      => false,
				'hide_in_admin'        => false,
				'hide_in_checkout'     => false,
				'hide_in_registration' => false,
				'required'             => false,
			),
			'wt-register-company'                => array(
				'type'                 => 'text',
				'label'                => __( 'Company', 'wt' ),
				'hide_in_account'      => false,
				'hide_in_admin'        => false,
				'hide_in_checkout'     => false,
				'hide_in_registration' => false,
				'required'             => false,
			),
			// Start with shipping_method_ to trigger recalculation if entered at checkout.
			'shipping_method_wt_register_tax_id' => array(
				'type'        => 'text',
				'label'       => __( 'Tax ID', 'wt' ),
				'placeholder' => __( 'Enter your tax ID to purchase wholesale tax free.', 'wt' ),
				'required'    => false,
			),
		)
	);
}

/**
 * Add post values to account fields if set
 *
 * @param array $fields Account fields added to user's account.
 *
 * @return array $fields
 */
function wt_add_post_data_to_account_fields( $fields ) {

	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ) ) {
		return $fields;
	}

	foreach ( $fields as $key => $field_args ) {
		if ( empty( $_POST[ $key ] ) ) {
			$fields[ $key ]['value'] = '';
			continue;
		}

		$fields[ $key ]['value'] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}

	return $fields;
}

add_filter( 'wt_account_fields', 'wt_add_post_data_to_account_fields', 10, 1 );

/**
 * Add fields to registration form and account area.
 */
function wt_print_user_frontend_fields() {
	$fields            = wt_get_account_fields();
	$is_user_logged_in = is_user_logged_in();

	foreach ( $fields as $key => $field_args ) {
		$value = null;

		if ( $is_user_logged_in && ! empty( $field_args['hide_in_account'] ) ) {
			continue;
		}

		if ( ! $is_user_logged_in && ! empty( $field_args['hide_in_registration'] ) ) {
			continue;
		}

		if ( $is_user_logged_in ) {
			$user_id = wt_get_edit_user_id();
			$value   = get_user_meta( $user_id, $key, true );
		}

		$value = isset( $field_args['value'] ) ? $field_args['value'] : $value;

		woocommerce_form_field( $key, $field_args, $value );
	}
}

add_action( 'woocommerce_register_form', 'wt_print_user_frontend_fields', 10 ); // register form
add_action( 'woocommerce_edit_account_form', 'wt_print_user_frontend_fields', 10 ); // my account

/**
 * Modify checkboxes/radio fields.
 *
 * @param string $field Field being modified.
 * @param string $key Array key.
 * @param string $args Arguments.
 * @param string $value Value to corresponding key.
 *
 * @return string
 */
function wt_form_field_modify( $field, $key, $args, $value ) {
	ob_start();
	wt_print_list_field( $key, $args, $value );
	$field = ob_get_clean();

	if ( $args['return'] ) {
		return $field;
	} else {
		echo $field;
	}
}

add_filter( 'woocommerce_form_field_checkboxes', 'wt_form_field_modify', 10, 4 );
add_filter( 'woocommerce_form_field_radio', 'wt_form_field_modify', 10, 4 );

/**
 * Get currently editing user ID (frontend account/edit profile/edit other user).
 *
 * @return int
 */
function wt_get_edit_user_id() {
	return isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : get_current_user_id();
}

/**
 * Print a list field (checkboxes|radio).
 *
 * @param string $key Stored value key.
 * @param array  $field_args Arguments for stored field.
 * @param mixed  $value Stored value for corresponding key.
 */
function wt_print_list_field( $key, $field_args, $value = null ) {
	$value = empty( $value ) && $field_args['type'] === 'checkboxes' ? array() : $value;
	?>
	<div class="form-row">
		<?php if ( ! empty( $field_args['label'] ) ) { ?>
			<label>
				<?php echo $field_args['label']; ?>
				<?php if ( ! empty( $field_args['required'] ) ) { ?>
					<abbr class="required" title="<?php echo esc_attr__( 'required', 'woocommerce' ); ?>">*</abbr>
				<?php } ?>
			</label>
		<?php } ?>
		<ul>
			<?php
			foreach ( $field_args['options'] as $option_value => $option_label ) {
				$id         = sprintf( '%s_%s', $key, sanitize_title_with_dashes( $option_label ) );
				$option_key = $field_args['type'] === 'checkboxes' ? sprintf( '%s[%s]', $key, $option_value ) : $key;
				$type       = $field_args['type'] === 'checkboxes' ? 'checkbox' : $field_args['type'];
				$checked    = $field_args['type'] === 'checkboxes' ? in_array( $option_value, $value ) : $option_value == $value;
				?>
				<li>
					<label for="<?php echo esc_attr( $id ); ?>">
						<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $option_key ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $checked ); ?>>
						<?php echo $option_label; ?>
					</label>
				</li>
			<?php } ?>
		</ul>
	</div>
	<?php
}

/**
 * Save registration fields.
 *
 * @param int $customer_id
 */
function wt_save_account_fields( $customer_id ) {
	$fields = wt_get_account_fields();

	foreach ( $fields as $key => $field_args ) {
		$sanitize = isset( $field_args['sanitize'] ) ? $field_args['sanitize'] : 'wc_clean';
		$value    = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, $_POST[ $key ] ) : '';
		update_user_meta( $customer_id, $key, $value );
	}
}

add_action( 'woocommerce_created_customer', 'wt_save_account_fields' ); // register/checkout.
add_action( 'personal_options_update', 'wt_save_account_fields' ); // edit own account admin.
add_action( 'edit_user_profile_update', 'wt_save_account_fields' ); // edit other account.
add_action( 'woocommerce_save_account_details', 'wt_save_account_fields' ); // edit WC account.

/**
 * Add fields to admin area.
 */
function wt_print_user_admin_fields() {
	$fields = wt_get_account_fields();
	?>
	<h2><?php _e( 'Additional Information', 'wt' ); ?></h2>
	<table class="form-table" id="wt-additional-information">
		<tbody>
		<?php foreach ( $fields as $key => $field_args ) { ?>
			<?php
			if ( ! empty( $field_args['hide_in_admin'] ) ) {
				continue;
			}

			$user_id = wt_get_edit_user_id();
			$value   = get_user_meta( $user_id, $key, true );
			?>
			<tr>
				<th>
					<label for="<?php echo $key; ?>"><?php echo $field_args['label']; ?></label>
				</th>
				<td>
					<?php $field_args['label'] = false; ?>
					<?php woocommerce_form_field( $key, $field_args, $value ); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php
}

add_action( 'show_user_profile', 'wt_print_user_admin_fields', 30 ); // admin: edit profile
add_action( 'edit_user_profile', 'wt_print_user_admin_fields', 30 ); // admin: edit other users

/**
 * Validate fields on frontend.
 *
 * @param WP_Error $errors Thrown error.
 *
 * @return WP_Error
 */
function wt_validate_user_frontend_fields( $errors ) {
	$fields = wt_get_account_fields();

	foreach ( $fields as $key => $field_args ) {
		if ( empty( $field_args['required'] ) ) {
			continue;
		}

		if ( ! isset( $_POST['register'] ) && ! empty( $field_args['hide_in_account'] ) ) {
			continue;
		}

		if ( isset( $_POST['register'] ) && ! empty( $field_args['hide_in_registration'] ) ) {
			continue;
		}

		if ( empty( $_POST[ $key ] ) ) {
			$message = sprintf( __( '%s is a required field.', 'wt' ), '<strong>' . $field_args['label'] . '</strong>' );
			$errors->add( $key, $message );
		}
	}

	return $errors;
}

add_filter( 'woocommerce_registration_errors', 'wt_validate_user_frontend_fields', 10 );
add_filter( 'woocommerce_save_account_details_errors', 'wt_validate_user_frontend_fields', 10 );

/**
 * Show fields at checkout.
 */
function wt_checkout_fields( $checkout_fields ) {
	$fields = wt_get_account_fields();

	foreach ( $fields as $key => $field_args ) {
		if ( ! empty( $field_args['hide_in_checkout'] ) ) {
			continue;
		}

		$checkout_fields['account'][ $key ] = $field_args;
	}

	return $checkout_fields;
}

add_filter( 'woocommerce_checkout_fields', 'wt_checkout_fields', 10, 1 );
