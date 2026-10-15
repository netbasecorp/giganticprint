<?php
/**
 * Order-level design flag for block-checkout (Store API) orders.
 *
 * PC Designer marks an order as "has a custom design" (order meta _nbd = 1, _nbu = 1) in set_nbd_order_data(),
 * hooked on the classic woocommerce_checkout_order_processed. The block checkout creates orders through the
 * Store API, which fires woocommerce_store_api_checkout_order_processed instead, so the flag was never set and
 * wp-admin > Order > "Customer Design" said "No design or upload file in this order" although the line items
 * carry the design key. Fire the core's own action for that path (the cart is still full at this point).
 *
 * @package signedge-designer-ui
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SG_Order {

    public static function init() {
        add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'flag' ), 5, 1 );
    }

    /** @param WC_Order $order */
    public static function flag( $order ) {
        if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) return;
        if ( $order->get_meta( '_nbd', true ) || $order->get_meta( '_nbu', true ) ) return; // already flagged
        if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) return;
        // core handler: copies each design folder to <order_id>_<key> and sets _nbd / _nbu on the order
        do_action( 'nbd_checkout_order_processed', $order->get_id() );
    }
}

SG_Order::init();
