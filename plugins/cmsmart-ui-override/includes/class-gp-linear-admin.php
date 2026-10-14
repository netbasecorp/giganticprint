<?php
/**
 * Admin page "Banner extras pricing" (WooCommerce menu): edits the gp_linear_rates option.
 *
 * @package cmsmart-ui-override
 */

defined( 'ABSPATH' ) || exit;

class GP_Linear_Admin {

	const SLUG = 'gp-linear-rates';

	/**
	 * Hook the class into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_gp_linear_rates_save', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Register the submenu page.
	 */
	public static function add_menu() {
		add_submenu_page(
			'woocommerce',
			'Banner extras pricing',
			'Banner extras pricing',
			'manage_woocommerce',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Save the posted rates, then redirect back with a success flag.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'cmsmart-ui-override' ), 403 );
		}
		check_admin_referer( 'gp_linear_rates_save' );
		$posted = ( isset( $_POST['gp_linear_rates'] ) && is_array( $_POST['gp_linear_rates'] ) ) ? wp_unslash( $_POST['gp_linear_rates'] ) : array();
		update_option( 'gp_linear_rates', GP_Linear_Pricing::sanitize_rates( $posted ) );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the settings page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$rates  = GP_Linear_Pricing::rates();
		$fields = array(
			'pole_rate'  => array( 'Pole pocket: rate per linear ft', 'Charged per linear foot of banner width, per pocket side (Top only / Bottom only = 1 side, Top & Bottom = 2 sides).' ),
			'pole_setup' => array( 'Pole pocket: setup fee', 'One-time fee added whenever a pole pocket is chosen.' ),
			'rope_rate'  => array( 'Rope reinforcement: rate per linear ft', 'Charged per linear foot of banner perimeter.' ),
		);
		echo '<div class="wrap"><h1>Banner extras pricing</h1>';
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Banner extras pricing saved.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="gp_linear_rates_save" />';
		wp_nonce_field( 'gp_linear_rates_save' );
		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $fields as $key => $meta ) {
			printf(
				'<tr><th scope="row"><label for="gp-%1$s">%2$s</label></th><td><input type="number" step="0.01" min="0" max="9999" class="small-text" id="gp-%1$s" name="gp_linear_rates[%1$s]" value="%3$s" /><p class="description">%4$s</p></td></tr>',
				esc_attr( $key ),
				esc_html( $meta[0] ),
				esc_attr( number_format( (float) $rates[ $key ], 2, '.', '' ) ),
				esc_html( $meta[1] )
			);
		}
		echo '</tbody></table>';
		submit_button( 'Save changes' );
		echo '</form></div>';
	}
}
