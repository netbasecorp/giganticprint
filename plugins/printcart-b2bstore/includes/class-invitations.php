<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Invitations {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'handle_accept' ) );
        add_action( 'user_register', array( __CLASS__, 'handle_new_user_registration' ), 10, 1 );
        add_action( 'woocommerce_created_customer', array( __CLASS__, 'handle_new_user_registration' ), 10, 1 );
    }

    /* ---------------------------------------------------------------- */
    /* Send invitation                                                    */
    /* ---------------------------------------------------------------- */
    public static function send_invitation( $email, $company = '', $contact = '', $tier = 'b2b_b', $message = '', $expires_days = 14 ) {
        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) return new WP_Error( 'invalid_email', 'Invalid email address.' );

        $token      = wp_generate_password( 32, false );
        $expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$expires_days} days" ) );

        $id = PCS_DB::save_invitation( array(
            'email'         => $email,
            'company_name'  => sanitize_text_field( $company ),
            'contact_name'  => sanitize_text_field( $contact ),
            'proposed_tier' => sanitize_text_field( $tier ),
            'token'         => $token,
            'status'        => 'pending',
            'message'       => sanitize_textarea_field( $message ),
            'expires_at'    => $expires_at,
            'created_by'    => get_current_user_id(),
        ) );

        if ( ! $id ) return new WP_Error( 'db_error', 'Failed to save invitation.' );

        self::send_invitation_email( $email, $contact, $company, $tier, $token, $message );

        return $id;
    }

    /* ---------------------------------------------------------------- */
    /* Handle token acceptance from URL                                   */
    /* ---------------------------------------------------------------- */
    public static function handle_accept() {
        if ( ! isset( $_GET['pcs_accept_invitation'] ) ) return;

        $token = sanitize_text_field( wp_unslash( $_GET['pcs_accept_invitation'] ) );
        $inv   = PCS_DB::get_invitation_by_token( $token );

        if ( ! $inv || $inv->status !== 'pending' ) return;

        /* Check expiry */
        if ( $inv->expires_at && strtotime( $inv->expires_at ) < time() ) {
            PCS_DB::save_invitation( array( 'id' => $inv->id, 'status' => 'expired' ) );
            wp_die( esc_html__( 'This invitation has expired.', 'printcart-b2bstore' ) );
        }

        /* If email already registered — set tier + create store if not exists */
        $existing_user = get_user_by( 'email', $inv->email );
        if ( $existing_user ) {
            PCS_DB::set_customer_tier( $existing_user->ID, $inv->proposed_tier, 0, 'Accepted B2B invitation' );

            if ( ! PCS_DB::get_store_by_user( $existing_user->ID ) ) {
                $company_name = ! empty( $inv->company_name ) ? $inv->company_name : $existing_user->display_name;
                PCS_DB::save_store( array(
                    'user_id'       => $existing_user->ID,
                    'company_name'  => $company_name,
                    'company_slug'  => sanitize_title( $company_name ),
                    'tier'          => $inv->proposed_tier,
                    'status'        => 'active',
                    'contact_name'  => ! empty( $inv->contact_name ) ? $inv->contact_name : $existing_user->display_name,
                    'contact_email' => $inv->email,
                ) );
            }

            PCS_DB::save_invitation( array( 'id' => $inv->id, 'status' => 'accepted', 'accepted_at' => current_time( 'mysql' ) ) );
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }

        /* Redirect to registration with token pre-filled */
        $register_url = add_query_arg( array( 'pcs_token' => $token ), wp_registration_url() );
        wp_safe_redirect( $register_url );
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* Auto-apply invitation when a new user registers with invited email  */
    /* ---------------------------------------------------------------- */
    public static function handle_new_user_registration( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) return;

        $inv = PCS_DB::get_pending_invitation_by_email( $user->user_email );
        if ( ! $inv ) return;

        PCS_DB::set_customer_tier( $user_id, $inv->proposed_tier, 0, 'Accepted B2B invitation' );

        if ( ! PCS_DB::get_store_by_user( $user_id ) ) {
            $company_name = ! empty( $inv->company_name ) ? $inv->company_name : $user->display_name;
            PCS_DB::save_store( array(
                'user_id'       => $user_id,
                'company_name'  => $company_name,
                'company_slug'  => sanitize_title( $company_name ),
                'tier'          => $inv->proposed_tier,
                'status'        => 'active',
                'contact_name'  => ! empty( $inv->contact_name ) ? $inv->contact_name : $user->display_name,
                'contact_email' => $user->user_email,
            ) );
        }

        PCS_DB::save_invitation( array( 'id' => $inv->id, 'status' => 'accepted', 'accepted_at' => current_time( 'mysql' ) ) );
    }

    /* ---------------------------------------------------------------- */
    /* Resend an existing invitation (generates new token + new expiry)   */
    /* ---------------------------------------------------------------- */
    public static function resend_invitation( $inv_id, $expires_days = 14 ) {
        global $wpdb;
        $inv = PCS_DB::get_invitation_by_id( $inv_id );
        if ( ! $inv ) return new WP_Error( 'not_found', 'Invitation not found.' );
        if ( 'accepted' === $inv->status ) return new WP_Error( 'already_accepted', 'This invitation has already been accepted.' );

        $new_token  = wp_generate_password( 32, false );
        $expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$expires_days} days" ) );

        $wpdb->update(
            $wpdb->prefix . 'pcs_invitations',
            array( 'token' => $new_token, 'expires_at' => $expires_at, 'status' => 'pending' ),
            array( 'id'    => absint( $inv_id ) ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        self::send_invitation_email(
            $inv->email,
            $inv->contact_name,
            $inv->company_name,
            $inv->proposed_tier,
            $new_token,
            $inv->message ?? ''
        );

        return true;
    }

    /* ---------------------------------------------------------------- */
    /* Mark expired invitations (run daily via cron or on-demand)         */
    /* ---------------------------------------------------------------- */
    public static function expire_old_invitations() {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}pcs_invitations
             SET status = 'expired'
             WHERE status = 'pending' AND expires_at < %s",
            current_time( 'mysql' )
        ) );
    }

    /* ---------------------------------------------------------------- */
    /* Stats helper                                                        */
    /* ---------------------------------------------------------------- */
    public static function get_stats() {
        return PCS_DB::get_invitation_stats();
    }

    /* ---------------------------------------------------------------- */
    /* Email                                                              */
    /* ---------------------------------------------------------------- */
    private static function send_invitation_email( $email, $contact, $company, $tier, $token, $custom_message ) {
        $tiers      = PCS_DB::get_tiers_from_rules();
        $tier_label = isset( $tiers[ $tier ]['label'] ) ? $tiers[ $tier ]['label'] : $tier;
        $discount   = isset( $tiers[ $tier ]['discount'] ) ? $tiers[ $tier ]['discount'] : 0;
        $accept_url = add_query_arg( 'pcs_accept_invitation', $token, home_url( '/' ) );
        $site_name  = get_bloginfo( 'name' );

        $greeting = $contact ? "Hi $contact," : 'Hello,';

        $body = $greeting . "\n\n";
        if ( $custom_message ) {
            $body .= $custom_message . "\n\n";
        } else {
            $body .= "You've been invited to join $site_name as a B2B partner.\n\n";
        }
        $body .= "Your proposed tier: $tier_label ($discount% discount on all orders)\n\n";
        $body .= "Accept your invitation here:\n$accept_url\n\n";
        $body .= "Best regards,\n$site_name";

        $subject = "You're invited to join $site_name as a B2B partner";
        wp_mail( $email, $subject, $body );
    }
}
