<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Invitations {

    const COOKIE = 'pcs_inv';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'handle_accept' ) );
        add_action( 'user_register', array( __CLASS__, 'handle_new_user_registration' ), 10, 1 );
        add_action( 'woocommerce_created_customer', array( __CLASS__, 'handle_new_user_registration' ), 10, 1 );
    }

    /* ---------------------------------------------------------------- */
    /* Tier validation                                                    */
    /* ---------------------------------------------------------------- */
    public static function resolve_tier( $tier ) {
        $tier  = sanitize_text_field( (string) $tier );
        $tiers = PCS_DB::get_tiers_from_rules();
        if ( '' !== $tier && isset( $tiers[ $tier ] ) ) return $tier;
        $default = sanitize_text_field( (string) get_option( 'pcs_b2b_default_tier', 'b2b_a' ) );
        return '' !== $default ? $default : 'b2b_a';
    }

    /* ---------------------------------------------------------------- */
    /* Send invitation                                                    */
    /* ---------------------------------------------------------------- */
    public static function send_invitation( $email, $company = '', $contact = '', $tier = 'b2b_b', $message = '', $expires_days = 14 ) {
        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) return new WP_Error( 'invalid_email', 'Invalid email address.' );

        $tier       = self::resolve_tier( $tier );
        $token      = wp_generate_password( 32, false );
        $expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$expires_days} days" ) );

        $id = PCS_DB::save_invitation( array(
            'email'         => $email,
            'company_name'  => sanitize_text_field( $company ),
            'contact_name'  => sanitize_text_field( $contact ),
            'proposed_tier' => $tier,
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
    /* Pending-invitation cookie (set on accept, consumed on register)    */
    /* ---------------------------------------------------------------- */
    private static function cookie_args( $expires ) {
        return array(
            'expires'  => $expires,
            'path'     => COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        );
    }

    private static function set_cookie( $token ) {
        if ( ! headers_sent() ) {
            setcookie( self::COOKIE, (string) $token, self::cookie_args( time() + HOUR_IN_SECONDS ) );
        }
        $_COOKIE[ self::COOKIE ] = (string) $token;
    }

    private static function clear_cookie() {
        if ( ! headers_sent() ) {
            setcookie( self::COOKIE, '', self::cookie_args( time() - YEAR_IN_SECONDS ) );
        }
        unset( $_COOKIE[ self::COOKIE ] );
    }

    private static function emails_match( $a, $b ) {
        return strtolower( trim( (string) $a ) ) === strtolower( trim( (string) $b ) );
    }

    private static function is_usable( $inv, $token ) {
        if ( ! $inv || 'pending' !== $inv->status ) return false;
        if ( ! hash_equals( (string) $inv->token, (string) $token ) ) return false;
        if ( $inv->expires_at && strtotime( $inv->expires_at ) < time() ) return false;
        return true;
    }

    private static function notice( $message ) {
        if ( function_exists( 'WC' ) && function_exists( 'wc_add_notice' ) && WC() && ! empty( WC()->session ) ) {
            if ( ! WC()->session->has_session() ) WC()->session->set_customer_session_cookie( true );
            wc_add_notice( $message, 'notice' );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Handle token acceptance from URL                                   */
    /* ---------------------------------------------------------------- */
    public static function handle_accept() {
        if ( ! isset( $_GET['pcs_accept_invitation'] ) ) return;

        $token = sanitize_text_field( wp_unslash( $_GET['pcs_accept_invitation'] ) );
        if ( '' === $token ) return;
        $inv = PCS_DB::get_invitation_by_token( $token );

        if ( ! $inv || 'pending' !== $inv->status || ! hash_equals( (string) $inv->token, $token ) ) return;

        /* Check expiry */
        if ( $inv->expires_at && strtotime( $inv->expires_at ) < time() ) {
            PCS_DB::save_invitation( array( 'id' => $inv->id, 'status' => 'expired' ) );
            wp_die( esc_html__( 'This invitation has expired.', 'printcart-b2bstore' ) );
        }

        $existing_user = get_user_by( 'email', $inv->email );
        $accept_url    = add_query_arg( 'pcs_accept_invitation', $token, home_url( '/' ) );

        if ( $existing_user ) {
            if ( ! is_user_logged_in() ) {
                /* Visitor must prove they own the account: log in, then return here */
                self::notice( __( 'Please log in with the invited account to accept your B2B invitation.', 'printcart-b2bstore' ) );
                $login = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
                wp_safe_redirect( add_query_arg( array(
                    'redirect_to' => rawurlencode( $accept_url ),
                    'redirect'    => rawurlencode( $accept_url ),
                ), $login ) );
                exit;
            }
            if ( (int) get_current_user_id() !== (int) $existing_user->ID ) {
                wp_die(
                    esc_html__( 'This invitation was sent to a different account. Please log out and open the link again while logged in as the invited user.', 'printcart-b2bstore' ),
                    '',
                    array( 'response' => 403, 'back_link' => true )
                );
            }
            if ( self::apply_invitation( $inv, $existing_user ) ) {
                wp_safe_redirect( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ) );
                exit;
            }
            wp_die( esc_html__( 'This invitation is no longer valid.', 'printcart-b2bstore' ), '', array( 'response' => 410 ) );
        }

        /* Not registered yet: remember the token and send to registration */
        self::set_cookie( $token );
        if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) && function_exists( 'wc_get_page_permalink' ) ) {
            $register_url = wc_get_page_permalink( 'myaccount' );
        } else {
            $register_url = wp_registration_url();
        }
        wp_safe_redirect( $register_url );
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* Apply invitation after a new user registers (cookie-bound)         */
    /* ---------------------------------------------------------------- */
    public static function handle_new_user_registration( $user_id ) {
        if ( empty( $_COOKIE[ self::COOKIE ] ) ) return;
        $token = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );
        if ( '' === $token ) return;

        $user = get_userdata( $user_id );
        if ( ! $user ) return;

        $inv = PCS_DB::get_invitation_by_token( $token );
        if ( ! self::is_usable( $inv, $token ) || ! self::emails_match( $inv->email, $user->user_email ) ) return;

        if ( self::apply_invitation( $inv, $user ) ) {
            self::clear_cookie();
        }
    }

    /* Single use: claims the invitation (pending -> accepted), then sets tier + store. */
    private static function apply_invitation( $inv, $user ) {
        if ( ! self::emails_match( $inv->email, $user->user_email ) ) return false;
        if ( ! PCS_DB::claim_invitation( $inv->id ) ) return false;

        $tier = self::resolve_tier( $inv->proposed_tier );
        PCS_DB::set_customer_tier( $user->ID, $tier, 0, 'Accepted B2B invitation' );

        if ( ! PCS_DB::get_store_by_user( $user->ID ) ) {
            $company_name = ! empty( $inv->company_name ) ? $inv->company_name : $user->display_name;
            PCS_DB::save_store( array(
                'user_id'       => $user->ID,
                'company_name'  => $company_name,
                'company_slug'  => sanitize_title( $company_name ),
                'tier'          => $tier,
                'status'        => PCS_DB::STORE_STATUS_ACTIVE,
                'contact_name'  => ! empty( $inv->contact_name ) ? $inv->contact_name : $user->display_name,
                'contact_email' => $user->user_email,
            ) );
        }
        return true;
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
            self::resolve_tier( $inv->proposed_tier ),
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
