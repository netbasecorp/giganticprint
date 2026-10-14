<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * One-click reorder that keeps the original art.
 *
 * Every order line is rebuilt through the same filter WooCommerce uses for "order again"
 * (woocommerce_order_again_cart_item_data). NBDesigner hooks it twice:
 *   - printing options (frontend-options.php): nbo_meta = option price / field values / options / original price
 *   - design (class.nbdesigner.php): copies the customer design folder (_nbd) and the upload folder (_nbu)
 *     to a NEW key, so the old order keeps its own untouched folder.
 * Prices are not touched here: the normal add-to-cart pipeline (GP_Linear_Pricing, core option
 * pricing, PCS_Pricing in the cart) recalculates them.
 */
class PCS_Reorder {

    const QUERY_VAR   = 'pcs_reorder';
    const NONCE_FIELD = '_pcs_reorder_nonce';

    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'handle_request' ), 5 );
        add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'orders_list_action' ), 20, 2 );
        add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'view_order_button' ), 9 );
    }

    /* ---------------------------------------------------------------- */
    /* Access                                                             */
    /* ---------------------------------------------------------------- */

    /** Approved company store ids a user owns or belongs to. */
    private static function store_ids_for_user( $user_id ) {
        global $wpdb;
        $user_id = absint( $user_id );
        if ( ! $user_id ) return array();
        $stores  = $wpdb->prefix . 'pcs_b2b_stores';
        $members = $wpdb->prefix . 'pcs_b2b_members';
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$stores} WHERE user_id = %d AND status IN ('active','approved')
             UNION
             SELECT s.id FROM {$members} m INNER JOIN {$stores} s ON s.id = m.store_id
             WHERE m.user_id = %d AND s.status IN ('active','approved')",
            $user_id, $user_id
        ) );
        return array_map( 'intval', (array) $ids );
    }

    /** Own orders, or orders of a customer of the same (approved) company store. */
    public static function user_can_reorder( $order, $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $order instanceof WC_Order || ! $user_id ) return false;

        $customer_id = (int) $order->get_customer_id();
        if ( $customer_id <= 0 ) return false;
        if ( $customer_id === $user_id ) return true;

        $mine = self::store_ids_for_user( $user_id );
        if ( ! $mine ) return false;
        return count( array_intersect( $mine, self::store_ids_for_user( $customer_id ) ) ) > 0;
    }

    /* ---------------------------------------------------------------- */
    /* Building the cart lines                                            */
    /* ---------------------------------------------------------------- */

    private static function design_dir( $kind, $key ) {
        if ( 'nbd' === $kind ) {
            return defined( 'NBDESIGNER_CUSTOMER_DIR' ) ? NBDESIGNER_CUSTOMER_DIR . '/' . $key : '';
        }
        return defined( 'NBDESIGNER_UPLOAD_DIR' ) ? NBDESIGNER_UPLOAD_DIR . '/' . $key : '';
    }

    /**
     * Cart item data for one order line, built like WooCommerce's own order-again.
     * Design / upload folders are copied to a fresh key by core's filter; a key whose source
     * folder is missing is dropped (and reported through $art_missing).
     *
     * @param WC_Order_Item_Product $item
     * @param WC_Order              $order
     * @param bool                  $art_missing Set to true when the stored art is gone.
     * @return array
     */
    public static function build_cart_item_data( $item, $order, &$art_missing = false ) {
        $art_missing = false;
        $old_nbd = (string) $item->get_meta( '_nbd' );
        $old_nbu = (string) $item->get_meta( '_nbu' );
        if ( '' !== $old_nbd && ! is_dir( self::design_dir( 'nbd', $old_nbd ) ) ) $art_missing = true;
        if ( '' !== $old_nbu && ! is_dir( self::design_dir( 'nbu', $old_nbu ) ) ) $art_missing = true;

        $data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );
        if ( ! is_array( $data ) ) $data = array();

        if ( isset( $data['nbd_item_meta_ds'] ) && is_array( $data['nbd_item_meta_ds'] ) ) {
            if ( isset( $data['nbd_item_meta_ds']['nbd'] ) && ! is_dir( self::design_dir( 'nbd', $data['nbd_item_meta_ds']['nbd'] ) ) ) {
                unset( $data['nbd_item_meta_ds']['nbd'] );
                $art_missing = true;
            }
            if ( isset( $data['nbd_item_meta_ds']['nbu'] ) && ! is_dir( self::design_dir( 'nbu', $data['nbd_item_meta_ds']['nbu'] ) ) ) {
                unset( $data['nbd_item_meta_ds']['nbu'] );
                $art_missing = true;
            }
            if ( empty( $data['nbd_item_meta_ds'] ) ) unset( $data['nbd_item_meta_ds'] );
        }
        return $data;
    }

    /** Why a line cannot be reordered, or '' when it can. */
    private static function line_problem( $item ) {
        $product = $item->get_product();
        if ( ! $product ) return 'product no longer available';

        $parent_id = (int) $item->get_product_id();
        $status    = get_post_status( $parent_id );
        if ( ! $status || 'trash' === $status || 'draft' === $status ) return 'product no longer available';
        if ( 'private' === $status && ! current_user_can( 'read_post', $parent_id ) ) return 'product not available to your account';
        if ( 'publish' !== $status && 'private' !== $status ) return 'product no longer available';

        if ( ! apply_filters( 'pcs_reorder_product_allowed', true, $product, $item ) ) return 'product not available to your account';
        if ( ! $product->is_purchasable() ) return 'product not available';
        return '';
    }

    /**
     * Add every line of an order to the cart.
     *
     * @return array { added: int, skipped: string[] (messages), art_missing: string[] (line names) }
     */
    public static function add_order_to_cart( $order ) {
        $result = array( 'added' => 0, 'skipped' => array(), 'art_missing' => array() );
        if ( ! $order instanceof WC_Order ) return $result;

        if ( ! WC()->cart ) {
            wc_load_cart();
        }
        if ( ! WC()->cart ) return $result;

        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) continue;
            $name = $item->get_name();

            $problem = self::line_problem( $item );
            if ( '' !== $problem ) {
                $result['skipped'][] = $name . ' (' . $problem . ')';
                continue;
            }

            $product      = $item->get_product();
            $product_id   = (int) $item->get_product_id();
            $variation_id = (int) $item->get_variation_id();
            $quantity     = max( 1, (int) $item->get_quantity() );

            $variation = array();
            if ( $variation_id && $product->is_type( 'variation' ) ) {
                foreach ( (array) $product->get_variation_attributes() as $attr => $val ) {
                    if ( '' === (string) $val ) {
                        $meta_val = $item->get_meta( substr( $attr, 10 ) );
                        if ( '' !== (string) $meta_val ) $val = $meta_val;
                    }
                    $variation[ $attr ] = $val;
                }
            }

            $art_missing = false;
            $cart_data   = self::build_cart_item_data( $item, $order, $art_missing );

            try {
                $key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_data );
            } catch ( Exception $e ) {
                $key = false;
            }

            if ( $key ) {
                $result['added']++;
                if ( $art_missing ) $result['art_missing'][] = $name;
            } else {
                $result['skipped'][] = $name . ' (could not be added)';
            }
        }
        return $result;
    }

    /** Human readable summary of a result. */
    public static function summary( $result ) {
        $msg = '';
        if ( $result['added'] > 0 ) {
            $msg = sprintf( _n( '%d item added to your cart.', '%d items added to your cart.', $result['added'], 'printcart-b2bstore' ), $result['added'] );
        }
        if ( ! empty( $result['skipped'] ) ) {
            $msg .= ( '' !== $msg ? ' ' : '' ) . sprintf( 'Skipped: %s.', implode( '; ', $result['skipped'] ) );
        }
        if ( ! empty( $result['art_missing'] ) ) {
            $msg .= ( '' !== $msg ? ' ' : '' ) . sprintf( 'The saved artwork could not be found for: %s. Please upload it again.', implode( ', ', $result['art_missing'] ) );
        }
        return $msg;
    }

    private static function add_notices( $result ) {
        if ( $result['added'] > 0 ) {
            wc_add_notice( sprintf( _n( '%d item added to your cart.', '%d items added to your cart.', $result['added'], 'printcart-b2bstore' ), $result['added'] ) );
        }
        if ( ! empty( $result['skipped'] ) ) {
            wc_add_notice( sprintf( 'Some items were skipped: %s.', implode( '; ', $result['skipped'] ) ), 'notice' );
        }
        if ( ! empty( $result['art_missing'] ) ) {
            wc_add_notice( sprintf( 'The saved artwork could not be found for: %s. Please upload it again.', implode( ', ', $result['art_missing'] ) ), 'notice' );
        }
        if ( 0 === $result['added'] && empty( $result['skipped'] ) ) {
            wc_add_notice( 'There was nothing to reorder.', 'error' );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Request handler (GET link from the orders list, POST from the view page) */
    /* ---------------------------------------------------------------- */
    public static function handle_request() {
        if ( empty( $_REQUEST[ self::QUERY_VAR ] ) || ! function_exists( 'wc_get_order' ) ) return;

        $orders_url = wc_get_account_endpoint_url( 'orders' );

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }

        $order_id = absint( $_REQUEST[ self::QUERY_VAR ] );
        $nonce    = isset( $_REQUEST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ self::NONCE_FIELD ] ) ) : '';
        if ( ! $order_id || ! wp_verify_nonce( $nonce, 'pcs_reorder_' . $order_id ) ) {
            wc_add_notice( 'This reorder link has expired. Please try again.', 'error' );
            wp_safe_redirect( $orders_url );
            exit;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order || ! self::user_can_reorder( $order, get_current_user_id() ) ) {
            wc_add_notice( 'Order not found.', 'error' );
            wp_safe_redirect( $orders_url );
            exit;
        }

        $result = self::add_order_to_cart( $order );
        self::add_notices( $result );
        wp_safe_redirect( $result['added'] > 0 ? wc_get_cart_url() : $orders_url );
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* Buttons                                                            */
    /* ---------------------------------------------------------------- */
    public static function reorder_url( $order_id ) {
        return add_query_arg( array(
            self::QUERY_VAR   => (int) $order_id,
            self::NONCE_FIELD => wp_create_nonce( 'pcs_reorder_' . (int) $order_id ),
        ), wc_get_account_endpoint_url( 'orders' ) );
    }

    public static function orders_list_action( $actions, $order ) {
        if ( $order instanceof WC_Order && ! $order->has_status( array( 'checkout-draft', 'auto-draft' ) ) ) {
            $actions['pcs-reorder'] = array(
                'url'  => self::reorder_url( $order->get_id() ),
                'name' => __( 'Reorder', 'printcart-b2bstore' ),
            );
        }
        return $actions;
    }

    public static function view_order_button( $order ) {
        if ( ! $order instanceof WC_Order || ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'view-order' ) ) return;
        // Replace WooCommerce's own "Order again" button (completed orders only) by this one.
        remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
        if ( ! self::user_can_reorder( $order, get_current_user_id() ) ) return;
        ?>
        <form method="post" action="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="pcs-reorder-form">
            <?php wp_nonce_field( 'pcs_reorder_' . $order->get_id(), self::NONCE_FIELD, false ); ?>
            <input type="hidden" name="<?php echo esc_attr( self::QUERY_VAR ); ?>" value="<?php echo (int) $order->get_id(); ?>" />
            <button type="submit" class="button pcs-reorder-btn"><?php esc_html_e( 'Reorder', 'printcart-b2bstore' ); ?></button>
        </form>
        <?php
    }
}
