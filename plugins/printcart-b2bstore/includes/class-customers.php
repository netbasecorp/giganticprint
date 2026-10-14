<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Customers {

    public static function init() {
        // Nothing extra needed; B2B_Stores handles auto-upgrade
    }

    /* ---------------------------------------------------------------- */
    /* Stats for the dashboard                                            */
    /* ---------------------------------------------------------------- */
    public static function get_stats() {
        $tier_counts = PCS_DB::get_tier_counts();
        $total       = count_users()['total_users'];
        $store_stats = PCS_DB::get_store_stats();

        /* Sum all tiers that have a discount > 0 (dynamically covers custom tiers like "pro") */
        $all_tiers = PCS_DB::get_tiers_from_rules();
        $b2b_count = 0;
        foreach ( $tier_counts as $key => $cnt ) {
            if ( isset( $all_tiers[ $key ] ) && (float) $all_tiers[ $key ]['discount'] > 0 ) {
                $b2b_count += (int) $cnt;
            }
        }

        /* B2C = everyone without an explicit B2B tier (includes users with no meta set) */
        $b2c_count = $total - $b2b_count;

        return array(
            'total'            => $total,
            'b2b_count'        => $b2b_count,
            'b2c_count'        => max( 0, $b2c_count ),
            'pending_approval' => $store_stats['pending'],
            'tier_counts'      => $tier_counts,
        );
    }

    /* ---------------------------------------------------------------- */
    /* Get customers with WC data (paginated)                             */
    /* ---------------------------------------------------------------- */
    public static function get_customers( $args = array() ) {
        $defaults = array(
            'tier'     => '',
            'search'   => '',
            'per_page' => 20,
            'paged'    => 1,
        );
        $args = wp_parse_args( $args, $defaults );

        $query_args = array(
            'number' => $args['per_page'],
            'offset' => ( absint( $args['paged'] ) - 1 ) * absint( $args['per_page'] ),
            'fields' => 'all',
        );

        if ( ! empty( $args['search'] ) ) {
            $query_args['search']         = '*' . $args['search'] . '*';
            $query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
        }

        if ( ! empty( $args['tier'] ) ) {
            if ( 'b2c' === $args['tier'] ) {
                /* B2C = users with tier explicitly set to b2c OR no tier meta at all */
                $query_args['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_pcs_tier',
                        'value'   => 'b2c',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_pcs_tier',
                        'compare' => 'NOT EXISTS',
                    ),
                );
            } else {
                $query_args['meta_key']   = '_pcs_tier';
                $query_args['meta_value'] = $args['tier'];
            }
        }

        $user_query = new WP_User_Query( $query_args );
        $users      = $user_query->get_results();
        $total      = $user_query->get_total();

        $result = array();
        foreach ( $users as $user ) {
            $tier              = PCS_DB::get_customer_tier( $user->ID );
            $total_orders      = wc_get_customer_order_count( $user->ID );
            $total_spent       = wc_get_customer_total_spent( $user->ID );
            $result[]          = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'registered'   => $user->user_registered,
                'tier'         => $tier,
                'orders'       => $total_orders,
                'spent'        => (float) $total_spent,
            );
        }

        return array( 'customers' => $result, 'total' => $total );
    }

    /* ---------------------------------------------------------------- */
    /* Tier label helper                                                   */
    /* ---------------------------------------------------------------- */
    public static function tier_label( $tier ) {
        $tiers = PCS_DB::get_tiers_from_rules();
        return isset( $tiers[ $tier ]['label'] ) ? $tiers[ $tier ]['label'] : ucfirst( $tier );
    }

    /* ---------------------------------------------------------------- */
    /* Initials helper (for avatar chips)                                 */
    /* ---------------------------------------------------------------- */
    public static function get_initials( $name ) {
        $parts    = explode( ' ', trim( $name ) );
        $initials = '';
        foreach ( array_slice( $parts, 0, 2 ) as $p ) {
            $initials .= strtoupper( substr( $p, 0, 1 ) );
        }
        return $initials ?: '?';
    }
}
