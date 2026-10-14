<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_B2B_Stores {

    public static function init() {
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'auto_upgrade_tier_on_order' ), 10, 1 );
    }

    /* ---------------------------------------------------------------- */
    /* Approve B2B application                                            */
    /* ---------------------------------------------------------------- */
    public static function approve_store( $store_id, $data = array() ) {
        $store = PCS_DB::get_store( $store_id );
        if ( ! $store ) return false;

        $tier          = isset( $data['tier'] )           ? sanitize_text_field( $data['tier'] )   : 'b2b_a';
        $payment_terms = isset( $data['payment_terms'] )  ? sanitize_text_field( $data['payment_terms'] ) : 'prepay';
        $credit_limit  = isset( $data['credit_limit'] )   ? floatval( $data['credit_limit'] )      : 0;
        $team_size     = isset( $data['team_size'] )       ? absint( $data['team_size'] )           : 5;

        /* Update store record */
        PCS_DB::save_store( array(
            'id'            => $store_id,
            'status'        => 'active',
            'tier'          => $tier,
            'payment_terms' => $payment_terms,
            'credit_limit'  => $credit_limit,
            'team_size'     => $team_size,
        ) );

        /* Set tier on the owner WP user */
        if ( $store->user_id ) {
            PCS_DB::set_customer_tier( $store->user_id, $tier, get_current_user_id(), 'Store approved' );
        }

        /* Send approval email */
        self::send_approval_email( $store, $tier );

        do_action( 'pcs_store_approved', $store_id, $tier );

        return true;
    }

    /* ---------------------------------------------------------------- */
    /* Reject B2B application                                             */
    /* ---------------------------------------------------------------- */
    public static function reject_store( $store_id, $reason = '' ) {
        $store = PCS_DB::get_store( $store_id );
        if ( ! $store ) return false;

        PCS_DB::save_store( array( 'id' => $store_id, 'status' => 'rejected' ) );

        self::send_rejection_email( $store, $reason );

        do_action( 'pcs_store_rejected', $store_id, $reason );

        return true;
    }

    /* ---------------------------------------------------------------- */
    /* Auto-upgrade tier based on cumulative WooCommerce spend            */
    /* ---------------------------------------------------------------- */
    public static function auto_upgrade_tier_on_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $user_id = $order->get_user_id();
        if ( ! $user_id ) return;

        $current_tier = PCS_DB::get_customer_tier( $user_id );
        if ( in_array( $current_tier, array( 'vip' ), true ) ) return; // VIP: manual only

        $total_spent = (float) wc_get_customer_total_spent( $user_id );
        $tiers       = PCS_DB::get_tiers_from_rules();

        $new_tier = $current_tier;
        /* Check thresholds from highest tier to lowest — first match wins */
        foreach ( $tiers as $t => $tdef ) {
            if ( 'b2c' === $t || 'vip' === $t ) continue; /* skip B2C and VIP (manual only) */
            /* Read from settings first, fallback to tier definition */
            $threshold = (float) get_option( 'pcs_tier_threshold_' . $t, $tdef['min_spend_upgrade'] ?? 0 );
            if ( $threshold > 0 && $total_spent >= $threshold ) {
                /* Only upgrade, never downgrade */
                $current_discount = isset( $tiers[ $current_tier ]['discount'] ) ? (float) $tiers[ $current_tier ]['discount'] : 0;
                $new_discount     = isset( $tdef['discount'] ) ? (float) $tdef['discount'] : 0;
                if ( $new_discount > $current_discount ) {
                    $new_tier = $t;
                }
            }
        }

        if ( $new_tier !== $current_tier ) {
            PCS_DB::set_customer_tier( $user_id, $new_tier, 0, 'Auto-upgrade based on spend threshold' );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Stats helper                                                        */
    /* ---------------------------------------------------------------- */
    public static function get_stats() {
        return PCS_DB::get_store_stats();
    }

    /* ---------------------------------------------------------------- */
    /* Emails                                                             */
    /* ---------------------------------------------------------------- */
    private static function send_approval_email( $store, $tier ) {
        if ( empty( $store->contact_email ) ) return;

        $tiers      = PCS_DB::get_tiers_from_rules();
        $tier_label = isset( $tiers[ $tier ]['label'] ) ? $tiers[ $tier ]['label'] : $tier;
        $discount   = isset( $tiers[ $tier ]['discount'] ) ? $tiers[ $tier ]['discount'] : 0;

        $subject = sprintf( 'Your B2B account for %s is approved!', get_bloginfo( 'name' ) );
        $message = sprintf(
            "Hi %s,\n\nGreat news! Your B2B application for %s has been approved.\n\nYour tier: %s (%d%% discount)\n\nLog in to start ordering: %s\n\nBest regards,\n%s",
            esc_html( $store->contact_name ),
            esc_html( $store->company_name ),
            esc_html( $tier_label ),
            $discount,
            wc_get_page_permalink( 'myaccount' ),
            get_bloginfo( 'name' )
        );

        wp_mail( $store->contact_email, $subject, $message );
    }

    private static function send_rejection_email( $store, $reason ) {
        if ( empty( $store->contact_email ) ) return;

        $subject = sprintf( 'B2B application update from %s', get_bloginfo( 'name' ) );
        $message = sprintf(
            "Hi %s,\n\nThank you for applying for a B2B account at %s.\n\nUnfortunately, your application was not approved at this time.%s\n\nFeel free to contact us if you have questions.\n\nBest regards,\n%s",
            esc_html( $store->contact_name ),
            get_bloginfo( 'name' ),
            $reason ? "\n\nReason: " . esc_html( $reason ) : '',
            get_bloginfo( 'name' )
        );

        wp_mail( $store->contact_email, $subject, $message );
    }
}
