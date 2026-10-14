<?php
/**
 * Pole pocket and rope priced per linear foot, computed from the chosen banner size.
 *
 * Test note (server, retail, qty 1, 13oz banner; Rush is a separate +100% field):
 *   24x16 + Pole Top & Bottom          = 17.68 + 14.00   (2 x 2 ft x $1 + $10 setup)
 *   48x24 + Pole Top & Bottom          = 53.04 + 18.00   (2 x 4 ft x $1 + $10 setup)
 *   48x24 + Pole Top only              = 53.04 + 14.00   (1 x 4 ft x $1 + $10 setup)
 *   24x16 + Rope                       = 17.68 + 6.67    (2 x (24 + 16) / 12 ft x $1)
 *   24x16 + Pole T&B + Rope + Rush     = (17.68 + 14.00 + 6.67) * 2 = 76.70
 *
 * @package cmsmart-ui-override
 */

defined( 'ABSPATH' ) || exit;

class GP_Linear_Pricing {

	/**
	 * Hook the class into WordPress.
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'filter_cart_item_data' ), 11, 4 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'patch_cart' ), 0, 1 );
	}

	/**
	 * Pricing rules. Patterns are bare regex sources (no delimiters, case-insensitive) shared with the JS mirror.
	 *
	 * 'measure'  width     = banner width in feet
	 *            perimeter = 2 * (W + H) in feet
	 * 'options'  first matching pattern wins; factor = number of measures charged (0 = free, no setup).
	 *
	 * @return array
	 */
	public static function rules() {
		$rules = array(
			'pole' => array(
				'title'   => 'pole\s*pocket',
				'measure' => 'width',
				'rate'    => 1.00,
				'setup'   => 10.00,
				'options' => array(
					array( '^\s*no\b', 0 ),
					array( 'top\s*(?:&|&amp;|and)\s*bottom', 2 ),
					array( 'top\s*only', 1 ),
					array( 'bottom\s*only', 1 ),
				),
			),
			'rope' => array(
				'title'   => 'rope',
				'measure' => 'perimeter',
				'rate'    => 1.00,
				'setup'   => 0.00,
				'options' => array(
					array( '^\s*no\b', 0 ),
					array( 'add\s*rope', 1 ),
				),
			),
		);
		$filtered = apply_filters( 'gp_linear_pricing_rules', $rules );
		return is_array( $filtered ) ? $filtered : $rules;
	}

	/**
	 * Build a PHP regex from a bare pattern.
	 *
	 * @param string $pattern Bare pattern.
	 * @return string
	 */
	private static function rx( $pattern ) {
		return '~' . str_replace( '~', '\~', (string) $pattern ) . '~i';
	}

	/**
	 * Price of one option.
	 *
	 * @param string $kind        Rule key (pole|rope).
	 * @param float  $w_in        Width in inches.
	 * @param float  $h_in        Height in inches.
	 * @param string $option_name Option name as stored.
	 * @return float
	 */
	public static function compute( $kind, $w_in, $h_in, $option_name ) {
		$rules = self::rules();
		if ( ! isset( $rules[ $kind ] ) || ! is_array( $rules[ $kind ] ) ) {
			return 0.0;
		}
		$rule = $rules[ $kind ];
		$name = html_entity_decode( stripslashes( (string) $option_name ), ENT_QUOTES, 'UTF-8' );
		$w_in = (float) $w_in;
		$h_in = (float) $h_in;
		if ( $w_in <= 0 || $h_in <= 0 || empty( $rule['options'] ) || ! is_array( $rule['options'] ) ) {
			return 0.0;
		}

		$factor = 0;
		foreach ( $rule['options'] as $opt ) {
			if ( is_array( $opt ) && isset( $opt[0] ) && 1 === @preg_match( self::rx( $opt[0] ), $name ) ) {
				$factor = (float) ( isset( $opt[1] ) ? $opt[1] : 0 );
				break;
			}
		}
		if ( $factor <= 0 ) {
			return 0.0;
		}

		$feet = ( isset( $rule['measure'] ) && 'perimeter' === $rule['measure'] ) ? 2 * ( $w_in + $h_in ) / 12 : $w_in / 12;
		$rate = isset( $rule['rate'] ) ? (float) $rule['rate'] : 0.0;
		$setup = isset( $rule['setup'] ) ? (float) $rule['setup'] : 0.0;

		return round( $factor * $feet * $rate + $setup, 2 );
	}

	/**
	 * Find which rule (if any) a field title belongs to.
	 *
	 * @param string $title Field title.
	 * @param array  $rules Rules.
	 * @return string Rule key or ''.
	 */
	private static function kind_for_title( $title, $rules ) {
		$title = html_entity_decode( stripslashes( (string) $title ), ENT_QUOTES, 'UTF-8' );
		foreach ( $rules as $kind => $rule ) {
			if ( is_array( $rule ) && ! empty( $rule['title'] ) && 1 === @preg_match( self::rx( $rule['title'] ), $title ) ) {
				return (string) $kind;
			}
		}
		return '';
	}

	/**
	 * Patch the price arrays of an unserialized option set.
	 *
	 * @param array $set    Unserialized option set (has 'fields').
	 * @param array $fields Flat field_id => value map.
	 * @return array|null Patched set or null when nothing to do.
	 */
	private static function patch_set( $set, $fields ) {
		if ( ! is_array( $set ) || empty( $set['fields'] ) || ! is_array( $set['fields'] ) ) {
			return null;
		}

		$w = 0.0;
		$h = 0.0;
		$found = false;
		foreach ( $set['fields'] as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'], $field['nbd_type'] ) || 'dimension' !== $field['nbd_type'] ) {
				continue;
			}
			if ( ! isset( $field['general']['enabled'] ) || 'y' !== $field['general']['enabled'] ) {
				continue;
			}
			if ( ! isset( $fields[ $field['id'] ] ) || ! is_string( $fields[ $field['id'] ] ) ) {
				continue;
			}
			$parts = explode( 'x', strtolower( $fields[ $field['id'] ] ) );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$w = (float) $parts[0];
			$h = (float) $parts[1];
			if ( $w > 0 && $h > 0 ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return null;
		}

		$rules = self::rules();
		foreach ( $set['fields'] as $fk => $field ) {
			if ( ! is_array( $field ) || ! isset( $field['general']['title'] ) || empty( $field['general']['attributes']['options'] ) || ! is_array( $field['general']['attributes']['options'] ) ) {
				continue;
			}
			$kind = self::kind_for_title( $field['general']['title'], $rules );
			if ( '' === $kind ) {
				continue;
			}
			foreach ( $field['general']['attributes']['options'] as $ok => $opt ) {
				if ( ! is_array( $opt ) ) {
					continue;
				}
				$name  = isset( $opt['name'] ) ? $opt['name'] : '';
				$price = number_format( self::compute( $kind, $w, $h, $name ), 2, '.', '' );
				$slots = ( isset( $opt['price'] ) && is_array( $opt['price'] ) ) ? array_keys( $opt['price'] ) : array();
				// Core reads price[0] or price[quantity_break index]: cover every quantity-break slot.
				$breaks = ( isset( $set['quantity_breaks'] ) && is_array( $set['quantity_breaks'] ) ) ? count( $set['quantity_breaks'] ) : 0;
				for ( $i = 0; $i <= $breaks; $i++ ) {
					$slots[] = $i;
				}
				$slots = array_unique( $slots );
				$new   = array();
				foreach ( $slots as $slot ) {
					$new[ $slot ] = $price;
				}
				ksort( $new );
				$set['fields'][ $fk ]['general']['attributes']['options'][ $ok ]['price'] = $new;
			}
		}
		return $set;
	}

	/**
	 * Patch pole pocket / rope prices inside an option-set DB row.
	 *
	 * @param array $options_row DB row ('fields' = serialized string or base64 of it).
	 * @param array $fields      Flat field_id => value map.
	 * @return array
	 */
	public static function patch_options( $options_row, $fields ) {
		try {
			if ( ! is_array( $options_row ) || ! is_array( $fields ) || ! isset( $options_row['fields'] ) ) {
				return $options_row;
			}
			$raw = $options_row['fields'];

			if ( is_array( $raw ) ) {
				$patched = self::patch_set( $raw, $fields );
				if ( null !== $patched ) {
					$options_row['fields'] = $patched;
				}
				return $options_row;
			}
			if ( ! is_string( $raw ) || '' === $raw ) {
				return $options_row;
			}

			$is_b64 = function_exists( 'nbd_is_base64_string' ) && nbd_is_base64_string( $raw );
			$plain  = $is_b64 ? base64_decode( $raw ) : $raw;
			$set    = maybe_unserialize( $plain );
			$patched = self::patch_set( $set, $fields );
			if ( null === $patched ) {
				return $options_row;
			}
			$out = serialize( $patched );
			$options_row['fields'] = $is_b64 ? base64_encode( $out ) : $out;
			return $options_row;
		} catch ( \Throwable $e ) {
			return $options_row;
		}
	}

	/**
	 * After core (priority 10): re-price the freshly added cart item.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @param int   $quantity       Quantity.
	 * @return array
	 */
	public static function filter_cart_item_data( $cart_item_data, $product_id = 0, $variation_id = 0, $quantity = 1 ) {
		try {
			if ( ! is_array( $cart_item_data ) || empty( $cart_item_data['nbo_meta'] ) || ! is_array( $cart_item_data['nbo_meta'] ) ) {
				return $cart_item_data;
			}
			$meta = $cart_item_data['nbo_meta'];
			if ( empty( $meta['options'] ) || empty( $meta['field'] ) || ! is_array( $meta['field'] ) ) {
				return $cart_item_data;
			}

			$patched = self::patch_options( $meta['options'], $meta['field'] );
			if ( $patched === $meta['options'] ) {
				return $cart_item_data;
			}
			$meta['options'] = $patched;

			$old_op = ( isset( $meta['option_price'] ) && is_array( $meta['option_price'] ) ) ? $meta['option_price'] : array();
			if ( empty( $old_op['manual_pm'] ) && class_exists( 'NBD_FRONTEND_PRINTING_OPTIONS' ) && isset( $meta['original_price'] ) ) {
				$product_id = $variation_id ? $variation_id : $product_id;
				$product    = wc_get_product( $product_id );
				$qty        = max( 1, (int) $quantity );
				$nbo        = NBD_FRONTEND_PRINTING_OPTIONS::instance();
				$original   = (float) $meta['original_price'];
				$option_price = $nbo->option_processing( $patched, $original, $meta['field'], $qty, null, $product );
				if ( is_array( $option_price ) && isset( $option_price['total_price'] ) ) {
					// Keep the preview image core attached when the item was added.
					if ( isset( $old_op['cart_image'] ) && '' !== $old_op['cart_image'] ) {
						$option_price['cart_image'] = $old_op['cart_image'];
					}
					$discount = isset( $option_price['discount_price'] ) ? (float) $option_price['discount_price'] : 0.0;
					$meta['option_price'] = $option_price;
					$meta['price']        = $original + (float) $option_price['total_price'] - $discount;
				}
			}

			$cart_item_data['nbo_meta'] = $meta;
		} catch ( \Throwable $e ) {
			return $cart_item_data;
		}
		return $cart_item_data;
	}

	/**
	 * Before core's re_calculate_price (priority 1): patch items already in the cart.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public static function patch_cart( $cart ) {
		try {
			if ( ! is_object( $cart ) || ! method_exists( $cart, 'get_cart_contents' ) || ! method_exists( $cart, 'set_cart_contents' ) ) {
				return;
			}
			$contents = $cart->get_cart_contents();
			$changed  = false;
			foreach ( $contents as $key => $item ) {
				if ( empty( $item['nbo_meta']['options'] ) || empty( $item['nbo_meta']['field'] ) || ! is_array( $item['nbo_meta']['field'] ) ) {
					continue;
				}
				$patched = self::patch_options( $item['nbo_meta']['options'], $item['nbo_meta']['field'] );
				if ( $patched !== $item['nbo_meta']['options'] ) {
					$contents[ $key ]['nbo_meta']['options'] = $patched;
					$changed = true;
				}
			}
			if ( $changed ) {
				$cart->set_cart_contents( $contents );
			}
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Data handed to the browser mirror.
	 *
	 * @return array
	 */
	public static function js_config() {
		return array( 'rules' => self::rules() );
	}
}
