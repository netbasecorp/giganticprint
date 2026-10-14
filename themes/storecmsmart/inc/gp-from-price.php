<?php
/**
 * "From" price for products priced by NBDesigner option sets.
 *
 * Products have a base price of 0, so the catalog would show "From 0,00 $".
 * We compute the cheapest real configuration with the core pricing code
 * (default sizes, qty 1) and replace the price HTML.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Price of one configuration, via core option_processing().
 *
 * @param object $inst          NBD_FRONTEND_PRINTING_OPTIONS instance.
 * @param array  $row           Option set DB row.
 * @param array  $option_fields Decoded option set.
 * @param array  $fields        Flat field_id => value map.
 * @param float  $base          Product base price.
 * @param object $product       WC_Product.
 * @return float|null Null when it could not be computed.
 */
function gp_from_price_eval( $inst, $row, $option_fields, $fields, $base, $product ) {
	$valid = $inst->validate_before_processing( $option_fields, $fields );
	$use   = $row;
	if ( class_exists( 'GP_Linear_Pricing' ) && method_exists( 'GP_Linear_Pricing', 'patch_options' ) ) {
		$use = GP_Linear_Pricing::patch_options( $row, $valid );
	}
	$res = $inst->option_processing( $use, $base, $valid, 1, null, $product );
	if ( ! is_array( $res ) || ! isset( $res['total_price'] ) ) {
		return null;
	}
	$orig     = isset( $res['original_price'] ) ? (float) $res['original_price'] : $base;
	$discount = isset( $res['discount_price'] ) ? (float) $res['discount_price'] : 0.0;
	return $orig + (float) $res['total_price'] - $discount;
}

/**
 * Cheapest configuration price for a product (cached).
 *
 * @param WC_Product $product Product.
 * @return float 0 when unknown.
 */
function gp_from_price_compute( $product ) {
	if ( ! class_exists( 'NBD_FRONTEND_PRINTING_OPTIONS' ) ) {
		return 0.0;
	}
	$set_id = NBD_FRONTEND_PRINTING_OPTIONS::get_product_option( $product->get_id() );
	if ( ! $set_id ) {
		return 0.0;
	}
	$row = NBD_FRONTEND_PRINTING_OPTIONS::get_option( $set_id );
	if ( ! is_array( $row ) || empty( $row['fields'] ) ) {
		return 0.0;
	}
	$base      = (float) $product->get_price( 'edit' );
	$stamp     = isset( $row['modified'] ) ? $row['modified'] : md5( (string) $row['fields'] );
	$cache_key = 'gp_from_' . $product->get_id() . '_' . md5( $stamp . '|' . $base );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return (float) $cached;
	}

	$from = 0.0;
	try {
		$inst = NBD_FRONTEND_PRINTING_OPTIONS::instance();
		$raw  = $row['fields'];
		if ( function_exists( 'nbd_is_base64_string' ) && nbd_is_base64_string( $raw ) ) {
			$raw = base64_decode( $raw );
		}
		$option_fields = maybe_unserialize( $raw );
		if ( is_array( $option_fields ) ) {
			$option_fields = NBD_FRONTEND_PRINTING_OPTIONS::recursive_stripslashes( $option_fields );
		}
		if ( is_array( $option_fields ) && ! empty( $option_fields['fields'] ) ) {
			$fields = array();
			$multi  = array(); // field_id => list of option keys.
			foreach ( $option_fields['fields'] as $field ) {
				$g = isset( $field['general'] ) ? $field['general'] : array();
				if ( ! isset( $field['id'], $g['enabled'] ) || 'y' !== $g['enabled'] ) {
					continue;
				}
				$fid       = $field['id'];
				$data_type = isset( $g['data_type'] ) ? $g['data_type'] : 'm';
				if ( isset( $field['nbd_type'] ) && 'dimension' === $field['nbd_type'] ) {
					$w = isset( $g['default_width'] ) && '' !== $g['default_width'] ? $g['default_width'] : ( isset( $g['min_width'] ) ? $g['min_width'] : 0 );
					$h = isset( $g['default_height'] ) && '' !== $g['default_height'] ? $g['default_height'] : ( isset( $g['min_height'] ) ? $g['min_height'] : 0 );
					$fields[ $fid ] = $w . 'x' . $h;
				} elseif ( 'i' === $data_type ) {
					$input_type = isset( $g['input_type'] ) ? $g['input_type'] : '';
					$min        = isset( $g['input_option']['min'] ) ? $g['input_option']['min'] : '';
					$fields[ $fid ] = 't' !== $input_type ? ( '' !== $min ? $min : 0 ) : '';
				} else {
					$opts = isset( $g['attributes']['options'] ) && is_array( $g['attributes']['options'] ) ? $g['attributes']['options'] : array();
					$val  = 0;
					foreach ( $opts as $key => $op ) {
						if ( isset( $op['selected'] ) && 'on' === $op['selected'] ) {
							$val = $key;
						}
					}
					$fields[ $fid ] = (string) $val;
					if ( count( $opts ) > 1 ) {
						$multi[ $fid ] = array_keys( $opts );
					}
				}
			}

			$best  = gp_from_price_eval( $inst, $row, $option_fields, $fields, $base, $product );
			$evals = 1;
			foreach ( $multi as $fid => $keys ) {
				$best_key = $fields[ $fid ];
				foreach ( $keys as $key ) {
					if ( (string) $key === (string) $best_key || $evals >= 200 ) {
						continue;
					}
					$try         = $fields;
					$try[ $fid ] = (string) $key;
					$price       = gp_from_price_eval( $inst, $row, $option_fields, $try, $base, $product );
					$evals++;
					if ( null === $price || $price <= 0 ) {
						continue;
					}
					if ( null === $best || $best <= 0 || $price < $best ) {
						$best     = $price;
						$best_key = (string) $key;
					}
				}
				$fields[ $fid ] = $best_key;
			}
			if ( null !== $best && $best > 0 ) {
				$from = round( $best, 2 );
			}
		}
	} catch ( \Throwable $e ) {
		$from = 0.0;
	}

	set_transient( $cache_key, $from, 12 * HOUR_IN_SECONDS );
	return (float) $from;
}

add_filter( 'woocommerce_get_price_html', function ( $price, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price;
	}
	if ( ! $product instanceof WC_Product || ! class_exists( 'NBD_FRONTEND_PRINTING_OPTIONS' ) ) {
		return $price;
	}
	if ( ! NBD_FRONTEND_PRINTING_OPTIONS::get_product_option( $product->get_id() ) ) {
		return $price;
	}
	$from = gp_from_price_compute( $product );
	if ( $from > 0 ) {
		$class = 'simple' === $product->get_type() ? 'nbo-base-price-html' : 'nbo-base-price-html-var';
		return '<span class="' . esc_attr( $class ) . '">' . esc_html__( 'From', 'web-to-print-online-designer' ) . '</span> ' . wc_price( $from );
	}
	return $price;
}, 20, 2 );
