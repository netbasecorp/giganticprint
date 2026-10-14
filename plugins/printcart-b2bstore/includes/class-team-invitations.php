<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Team member invitation system.
 * Handles sending invite emails and processing accept/decline responses.
 */
class PCS_Team_Invitations {

    public static function init() {
        /* Handle accept/decline links — runs early before template rendering */
        add_action( 'init', array( __CLASS__, 'handle_response' ), 1 );
    }

    /* ---------------------------------------------------------------- */
    /* Handle accept / decline from email link                           */
    /* ---------------------------------------------------------------- */
    public static function handle_response() {
        if ( empty( $_GET['pcs_team_invite'] ) || empty( $_GET['pcs_inv_action'] ) ) return;

        $token  = sanitize_text_field( wp_unslash( $_GET['pcs_team_invite'] ) );
        $action = sanitize_text_field( wp_unslash( $_GET['pcs_inv_action'] ) );

        if ( ! in_array( $action, array( 'accept', 'decline' ), true ) ) return;

        $invite     = PCS_DB::get_team_invite_by_token( $token );
        $portal_id  = (int) get_option( 'pcs_portal_page_id' );
        $portal_url = $portal_id ? get_permalink( $portal_id ) : home_url( '/' );

        /* Invalid or already responded */
        if ( ! $invite || ! hash_equals( (string) $invite->token, $token ) ) {
            wp_safe_redirect( add_query_arg( 'pcs_invite_msg', 'invalid', $portal_url ) );
            exit;
        }
        if ( $invite->status !== 'pending' ) {
            wp_safe_redirect( add_query_arg( 'pcs_invite_msg', 'already_' . $invite->status, $portal_url ) );
            exit;
        }
        /* Expired */
        if ( strtotime( $invite->expires_at ) < time() ) {
            PCS_DB::update_team_invite_status( $invite->id, 'expired' );
            wp_safe_redirect( add_query_arg( 'pcs_invite_msg', 'expired', $portal_url ) );
            exit;
        }

        if ( $action === 'accept' ) {
            /* Must be logged in to accept */
            if ( ! is_user_logged_in() ) {
                wp_safe_redirect( wp_login_url( add_query_arg( array(
                    'pcs_team_invite' => $token,
                    'pcs_inv_action'  => 'accept',
                ), home_url( '/' ) ) ) );
                exit;
            }

            $user = wp_get_current_user();

            /* Email must match the logged-in account */
            if ( strtolower( $user->user_email ) !== strtolower( $invite->invited_email ) ) {
                wp_safe_redirect( add_query_arg( 'pcs_invite_msg', 'wrong_account', $portal_url ) );
                exit;
            }

            /* Single use: only the caller that flips pending -> accepted adds the member */
            if ( ! PCS_DB::claim_team_invite( $invite->id ) ) {
                wp_safe_redirect( add_query_arg( 'pcs_invite_msg', 'already_accepted', $portal_url ) );
                exit;
            }
            PCS_DB::add_member( $invite->store_id, $user->ID, $invite->role, $invite->spending_limit );

            /* Auto-upgrade tier: if store tier > user's personal tier, upgrade user */
            self::maybe_upgrade_tier_on_join( $user->ID, $invite->store_id );

            wp_safe_redirect( add_query_arg( array(
                'section'        => 'overview',
                'pcs_invite_msg' => 'accepted',
            ), $portal_url ) );
            exit;
        }

        /* Decline */
        PCS_DB::update_team_invite_status( $invite->id, 'declined' );
        $redirect = is_user_logged_in()
            ? add_query_arg( 'pcs_invite_msg', 'declined', $portal_url )
            : add_query_arg( 'pcs_invite_msg', 'declined', home_url( '/' ) );
        wp_safe_redirect( $redirect );
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* Tier management on join / leave                                   */
    /* ---------------------------------------------------------------- */

    /**
     * When a B2C user joins a B2B store team, upgrade their tier to the store's tier.
     * If user already has a higher tier, do nothing.
     * Saves original tier in user meta so it can be restored on leave.
     */
    public static function maybe_upgrade_tier_on_join( $user_id, $store_id ) {
        $store = PCS_DB::get_store_by_id( $store_id );
        if ( ! $store || ! PCS_DB::store_is_approved( $store->status ) ) return;

        $tiers          = PCS_DB::get_tiers_from_rules();
        $store_discount = isset( $tiers[ $store->tier ]['discount'] ) ? (float) $tiers[ $store->tier ]['discount'] : 0;
        if ( $store_discount <= 0 ) return;

        $user_tier         = PCS_DB::get_customer_tier( $user_id );
        $user_discount     = isset( $tiers[ $user_tier ]['discount'] ) ? (float) $tiers[ $user_tier ]['discount'] : 0;

        if ( $store_discount > $user_discount ) {
            /* Save original tier so we can revert on leave */
            update_user_meta( $user_id, '_pcs_pre_team_tier',     $user_tier );
            update_user_meta( $user_id, '_pcs_team_tier_store_id', $store_id );
            PCS_DB::set_customer_tier(
                $user_id,
                $store->tier,
                0,
                'Auto-upgraded on joining ' . $store->company_name . ' team'
            );
        }
    }

    /**
     * When a member is removed from a store, revert their tier to what it was before joining.
     * Only reverts if the tier was auto-set by joining this specific store.
     */
    public static function maybe_revert_tier_on_leave( $user_id, $store_id ) {
        $pre_tier       = get_user_meta( $user_id, '_pcs_pre_team_tier',      true );
        $pre_store_id   = (int) get_user_meta( $user_id, '_pcs_team_tier_store_id', true );

        if ( ! $pre_tier || $pre_store_id !== (int) $store_id ) return;

        $store = PCS_DB::get_store_by_id( $store_id );
        $note  = $store ? 'Auto-reverted after leaving ' . $store->company_name . ' team' : 'Auto-reverted after leaving team';

        PCS_DB::set_customer_tier( $user_id, $pre_tier, 0, $note );
        delete_user_meta( $user_id, '_pcs_pre_team_tier' );
        delete_user_meta( $user_id, '_pcs_team_tier_store_id' );
    }

    /* ---------------------------------------------------------------- */
    /* Send invitation email                                              */
    /* ---------------------------------------------------------------- */
    public static function send_invite_email( $store, $token, $to_email, $role, $personal_message = '' ) {
        $accept_url  = add_query_arg( array( 'pcs_team_invite' => $token, 'pcs_inv_action' => 'accept'  ), home_url( '/' ) );
        $decline_url = add_query_arg( array( 'pcs_team_invite' => $token, 'pcs_inv_action' => 'decline' ), home_url( '/' ) );

        $site_name   = get_bloginfo( 'name' );
        $company     = $store->company_name;
        $inviter     = wp_get_current_user()->display_name;
        $role_label  = ucfirst( $role );
        $expires     = date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) );

        $subject = sprintf( "You've been invited to join %s's B2B workspace on %s", $company, $site_name );
        $body    = self::build_email_html( compact( 'company', 'inviter', 'role_label', 'personal_message',
                                                    'accept_url', 'decline_url', 'site_name', 'expires' ) );

        wp_mail( $to_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    /* ---------------------------------------------------------------- */
    /* HTML email template                                                */
    /* ---------------------------------------------------------------- */
    private static function build_email_html( $d ) {
        $personal_block = '';
        if ( ! empty( $d['personal_message'] ) ) {
            $personal_block = '<div style="background:#f0f4ff;border-left:4px solid #2563eb;padding:14px 18px;border-radius:0 8px 8px 0;margin-bottom:24px;">
                <p style="margin:0;font-size:14px;color:#374151;font-style:italic;">&ldquo;' . esc_html( $d['personal_message'] ) . '&rdquo;</p>
                <p style="margin:6px 0 0;font-size:12px;color:#6b7280;">— ' . esc_html( $d['inviter'] ) . '</p>
            </div>';
        }

        return '<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Team Invitation</title></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center" style="padding:48px 20px;">
<table width="560" cellpadding="0" cellspacing="0" role="presentation"
       style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:100%;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#1d2327 0%,#2563eb 100%);padding:36px;text-align:center;">
    <div style="display:inline-block;background:rgba(255,255,255,.15);border-radius:50%;width:56px;height:56px;line-height:56px;font-size:26px;margin-bottom:12px;">👥</div>
    <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-.3px;">Team Invitation</h1>
    <p style="margin:6px 0 0;color:rgba(255,255,255,.65);font-size:13px;">' . esc_html( $d['site_name'] ) . '</p>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:36px;">
    <p style="margin:0 0 20px;font-size:15px;color:#111827;line-height:1.7;">
      Hi there,<br><br>
      <strong>' . esc_html( $d['inviter'] ) . '</strong> has invited you to join
      <strong>' . esc_html( $d['company'] ) . '\'s</strong> B2B workspace as a
      <strong style="color:#2563eb;">' . esc_html( $d['role_label'] ) . '</strong>.
    </p>
    ' . $personal_block . '
    <p style="margin:0 0 28px;font-size:14px;color:#6b7280;line-height:1.6;">
      As a <strong>' . esc_html( $d['role_label'] ) . '</strong> you will have access to the B2B portal,
      shared team orders, and workspace features on <strong>' . esc_html( $d['site_name'] ) . '</strong>.
    </p>

    <!-- Buttons -->
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td width="48%">
          <a href="' . esc_url( $d['accept_url'] ) . '"
             style="display:block;background:#2563eb;color:#ffffff;text-decoration:none;padding:15px 20px;border-radius:10px;font-size:15px;font-weight:700;text-align:center;">
            ✓ &nbsp;Accept invitation
          </a>
        </td>
        <td width="4%">&nbsp;</td>
        <td width="48%">
          <a href="' . esc_url( $d['decline_url'] ) . '"
             style="display:block;background:#f3f4f6;color:#374151;text-decoration:none;padding:15px 20px;border-radius:10px;font-size:15px;font-weight:700;text-align:center;border:1px solid #e5e7eb;">
            ✕ &nbsp;Decline
          </a>
        </td>
      </tr>
    </table>

    <p style="margin:24px 0 0;font-size:12px;color:#9ca3af;text-align:center;">
      Or copy this link to accept: <a href="' . esc_url( $d['accept_url'] ) . '" style="color:#2563eb;word-break:break-all;">' . esc_url( $d['accept_url'] ) . '</a>
    </p>
  </td></tr>

  <!-- Footer -->
  <tr><td style="padding:20px 36px;border-top:1px solid #f3f4f6;background:#f9fafb;text-align:center;">
    <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
      This invitation expires on <strong>' . esc_html( $d['expires'] ) . '</strong>.<br>
      If you did not expect this email, you can safely ignore it.
    </p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';
    }
}
