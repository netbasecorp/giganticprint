<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Ajax {

    public static function init() {
        $actions = array(
            /* Quotes */
            'pcs_save_quote',
            'pcs_send_quote',
            'pcs_delete_quote',
            'pcs_quote_respond',   /* frontend: accept/decline */
            'pcs_submit_quote_request', /* frontend: customer submit request */
            /* Core */
            'pcs_reorder',
            'pcs_approve_store',
            'pcs_reject_store',
            'pcs_send_invitation',
            'pcs_resend_invitation',
            'pcs_update_customer_tier',
            'pcs_save_pricing_rule',
            'pcs_delete_pricing_rule',
            'pcs_add_member',
            'pcs_remove_member',
            'pcs_save_store_settings',
            'pcs_invite_member_fe',
            'pcs_remove_member_fe',
            'pcs_cancel_team_invite',
        );

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( __CLASS__, $action ) );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Reorder — add all items from a past order to cart                  */
    /* ---------------------------------------------------------------- */
    public static function pcs_reorder() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'pcs_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Please log in.' ), 403 );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Invalid order.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_customer_id() !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $added   = 0;
        $skipped = 0;

        foreach ( $order->get_items() as $item ) {
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $quantity     = $item->get_quantity();
            $product      = wc_get_product( $variation_id ?: $product_id );

            if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
                $skipped++;
                continue;
            }

            $result = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
            if ( $result ) {
                $added++;
            } else {
                $skipped++;
            }
        }

        if ( $added > 0 ) {
            $msg = sprintf( '%d item%s added to cart.', $added, $added > 1 ? 's' : '' );
            if ( $skipped > 0 ) {
                $msg .= sprintf( ' %d item%s skipped (unavailable).', $skipped, $skipped > 1 ? 's' : '' );
            }
            wp_send_json_success( array( 'message' => $msg, 'cart_url' => wc_get_cart_url() ) );
        } else {
            wp_send_json_error( array( 'message' => 'No items could be added (products may be unavailable).' ) );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Approve store                                                       */
    /* ---------------------------------------------------------------- */
    public static function pcs_approve_store() {
        self::check_nonce_cap();

        $store_id = absint( $_POST['store_id'] ?? 0 );
        if ( ! $store_id ) wp_send_json_error( array( 'message' => 'Invalid store ID.' ) );

        $result = PCS_B2B_Stores::approve_store( $store_id, array(
            'tier'          => sanitize_text_field( $_POST['tier']          ?? 'b2b_a' ),
            'payment_terms' => sanitize_text_field( $_POST['payment_terms'] ?? 'prepay' ),
            'credit_limit'  => floatval( $_POST['credit_limit']             ?? 0 ),
            'team_size'     => absint( $_POST['team_size']                  ?? 5 ),
        ) );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Store approved successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Could not approve store.' ) );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Reject store                                                        */
    /* ---------------------------------------------------------------- */
    public static function pcs_reject_store() {
        self::check_nonce_cap();

        $store_id = absint( $_POST['store_id'] ?? 0 );
        $reason   = sanitize_textarea_field( $_POST['reason'] ?? '' );

        if ( ! $store_id ) wp_send_json_error( array( 'message' => 'Invalid store ID.' ) );

        $result = PCS_B2B_Stores::reject_store( $store_id, $reason );

        $result
            ? wp_send_json_success( array( 'message' => 'Store rejected.' ) )
            : wp_send_json_error( array( 'message' => 'Could not reject store.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Send invitation                                                     */
    /* ---------------------------------------------------------------- */
    public static function pcs_send_invitation() {
        self::check_nonce_cap();

        $email       = sanitize_email( $_POST['email']        ?? '' );
        $company     = sanitize_text_field( $_POST['company'] ?? '' );
        $contact     = sanitize_text_field( $_POST['contact'] ?? '' );
        $tier        = sanitize_text_field( $_POST['tier']    ?? 'b2b_b' );
        $message     = sanitize_textarea_field( $_POST['message']       ?? '' );
        $expires     = absint( $_POST['expires_days']         ?? 14 );

        $result = PCS_Invitations::send_invitation( $email, $company, $contact, $tier, $message, $expires );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => 'Invitation sent!', 'id' => $result ) );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Resend an invitation email                                          */
    /* ---------------------------------------------------------------- */
    public static function pcs_resend_invitation() {
        self::check_nonce_cap();

        $inv_id = absint( $_POST['inv_id'] ?? 0 );
        if ( ! $inv_id ) wp_send_json_error( array( 'message' => 'Invalid invitation.' ) );

        $result = PCS_Invitations::resend_invitation( $inv_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => 'Invitation re-sent successfully.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Update customer tier                                               */
    /* ---------------------------------------------------------------- */
    public static function pcs_update_customer_tier() {
        self::check_nonce_cap();

        $user_id = absint( $_POST['user_id'] ?? 0 );
        $tier    = sanitize_text_field( $_POST['tier'] ?? '' );
        $note    = sanitize_textarea_field( $_POST['note'] ?? '' );

        if ( ! $user_id || ! $tier ) wp_send_json_error( array( 'message' => 'Invalid data.' ) );

        $tiers = PCS_DB::get_tiers_from_rules();
        if ( ! isset( $tiers[ $tier ] ) ) wp_send_json_error( array( 'message' => 'Invalid tier.' ) );

        PCS_DB::set_customer_tier( $user_id, $tier, get_current_user_id(), $note );

        wp_send_json_success( array( 'message' => 'Tier updated.', 'tier' => $tier ) );
    }

    /* ---------------------------------------------------------------- */
    /* Save pricing rule                                                   */
    /* ---------------------------------------------------------------- */
    public static function pcs_save_pricing_rule() {
        self::check_nonce_cap();

        $data = array(
            'id'                      => absint( $_POST['id']                        ?? 0 ) ?: null,
            'name'                    => sanitize_text_field( $_POST['name']         ?? '' ),
            'rule_type'               => sanitize_text_field( $_POST['rule_type']    ?? 'tier' ),
            'target_type'             => sanitize_text_field( $_POST['target_type']  ?? 'tier' ),
            'target_value'            => sanitize_text_field( $_POST['target_value'] ?? '' ),
            'discount_type'           => sanitize_text_field( $_POST['discount_type'] ?? 'percentage' ),
            'discount_value'          => floatval( $_POST['discount_value']          ?? 0 ),
            'min_order'               => floatval( $_POST['min_order']               ?? 0 ),
            'payment_terms'           => sanitize_text_field( $_POST['payment_terms'] ?? '' ),
            'free_shipping_threshold' => '' !== ( $_POST['free_shipping_threshold'] ?? '' )
                                         ? floatval( $_POST['free_shipping_threshold'] ) : null,
            'promo_code'              => sanitize_text_field( $_POST['promo_code']   ?? '' ),
            'status'                  => sanitize_text_field( $_POST['status']       ?? 'active' ),
            'priority'                => absint( $_POST['priority']                  ?? 10 ),
            'valid_from'              => sanitize_text_field( $_POST['valid_from']   ?? '' ) ?: null,
            'valid_until'             => sanitize_text_field( $_POST['valid_until']  ?? '' ) ?: null,
        );

        if ( empty( $data['id'] ) ) unset( $data['id'] );

        // Tier rules phải có target_value (tier key như b2b_a, vip...)
        if ( 'tier' === $data['rule_type'] && empty( $data['target_value'] ) ) {
            wp_send_json_error( array( 'message' => 'Tier rules must have a Target Value (e.g. b2b_a, b2b_b, vip).' ) );
        }

        $id = PCS_DB::save_pricing_rule( $data );

        $id
            ? wp_send_json_success( array( 'message' => 'Rule saved.', 'id' => $id ) )
            : wp_send_json_error( array( 'message' => 'Could not save rule.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Delete pricing rule                                                */
    /* ---------------------------------------------------------------- */
    public static function pcs_delete_pricing_rule() {
        self::check_nonce_cap();

        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error( array( 'message' => 'Invalid ID.' ) );

        PCS_DB::delete_pricing_rule( $id );
        wp_send_json_success( array( 'message' => 'Rule deleted.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Add member to store                                                */
    /* ---------------------------------------------------------------- */
    public static function pcs_add_member() {
        self::check_nonce_cap();

        $store_id      = absint( $_POST['store_id']       ?? 0 );
        $user_id       = absint( $_POST['user_id']        ?? 0 );
        $role          = sanitize_text_field( $_POST['role'] ?? 'member' );
        $spending_limit = floatval( $_POST['spending_limit'] ?? 0 );

        if ( ! $store_id || ! $user_id ) wp_send_json_error( array( 'message' => 'Invalid data.' ) );

        $result = PCS_DB::add_member( $store_id, $user_id, $role, $spending_limit );

        $result
            ? wp_send_json_success( array( 'message' => 'Member added.' ) )
            : wp_send_json_error( array( 'message' => 'Member already exists or error.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Remove member                                                       */
    /* ---------------------------------------------------------------- */
    public static function pcs_remove_member() {
        self::check_nonce_cap();

        $id = absint( $_POST['member_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error( array( 'message' => 'Invalid ID.' ) );

        PCS_DB::remove_member( $id );
        wp_send_json_success( array( 'message' => 'Member removed.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Save store settings (General tab)                                  */
    /* ---------------------------------------------------------------- */
    public static function pcs_save_store_settings() {
        self::check_nonce_cap();

        $allowed = array(
            'pcs_store_name', 'pcs_admin_email', 'pcs_support_phone',
            'pcs_timezone', 'pcs_currency',
            'pcs_production_location', 'pcs_standard_turnaround',
            'pcs_rush_enabled', 'pcs_rush_surcharge',
            /* B2B Application */
            'pcs_b2b_default_tier',
            /* Advanced */
            'pcs_show_tier_badge', 'pcs_apply_in_cart',
        );

        /* Checkboxes — save 0 when unchecked (not present in POST) */
        $checkboxes = array( 'pcs_b2b_self_register', 'pcs_b2b_auto_approve', 'pcs_show_tier_badge', 'pcs_apply_in_cart', 'pcs_rush_enabled' );
        foreach ( $checkboxes as $cb ) {
            update_option( $cb, isset( $_POST[ $cb ] ) ? '1' : '0' );
        }

        /* Auto-upgrade tier thresholds */
        foreach ( $_POST as $key => $val ) {
            if ( strpos( $key, 'pcs_tier_threshold_' ) === 0 ) {
                update_option( sanitize_key( $key ), floatval( $val ) );
            }
        }

        foreach ( $allowed as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_option( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
        }

        wp_send_json_success( array( 'message' => 'Settings saved.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Save quote (admin — create or update with line items)              */
    /* ---------------------------------------------------------------- */
    public static function pcs_save_quote() {
        self::check_nonce_cap();

        $id       = absint( $_POST['quote_id'] ?? 0 );
        $status   = sanitize_text_field( $_POST['status']      ?? 'in_review' );
        $title    = sanitize_text_field( $_POST['title']       ?? '' );
        $notes    = sanitize_textarea_field( $_POST['admin_notes'] ?? '' );
        $discount = floatval( $_POST['discount']   ?? 0 );
        $valid    = sanitize_text_field( $_POST['valid_until'] ?? '' ) ?: null;
        $items_raw = isset( $_POST['items'] ) ? (array) $_POST['items'] : array();

        /* Sanitize items */
        $items = array();
        foreach ( $items_raw as $item ) {
            $items[] = array(
                'description' => sanitize_text_field( $item['description'] ?? '' ),
                'notes'       => sanitize_text_field( $item['notes']       ?? '' ),
                'sku'         => sanitize_text_field( $item['sku']         ?? '' ),
                'quantity'    => max( 1, absint( $item['quantity'] ?? 1 ) ),
                'unit_price'  => floatval( $item['unit_price'] ?? 0 ),
                'product_id'  => absint( $item['product_id']  ?? 0 ),
            );
        }

        if ( $id ) {
            /* Update existing */
            $subtotal = PCS_Quotes::save_items( $id, $items );
            $total    = max( 0, $subtotal - $discount );
            PCS_Quotes::update( $id, array(
                'status'      => $status,
                'title'       => $title,
                'admin_notes' => $notes,
                'subtotal'    => $subtotal,
                'discount'    => $discount,
                'total'       => $total,
                'valid_until' => $valid,
            ) );
            wp_send_json_success( array( 'message' => 'Quote saved.', 'id' => $id ) );
        } else {
            /* Create new (admin-initiated) */
            $user_id = absint( $_POST['user_id'] ?? 0 );
            if ( ! $user_id ) wp_send_json_error( array( 'message' => 'Customer required.' ) );

            $new_id = PCS_Quotes::create( array(
                'user_id' => $user_id,
                'title'   => $title,
                'status'  => 'in_review',
            ) );
            if ( ! $new_id ) wp_send_json_error( array( 'message' => 'Could not create quote.' ) );

            $subtotal = PCS_Quotes::save_items( $new_id, $items );
            $total    = max( 0, $subtotal - $discount );
            PCS_Quotes::update( $new_id, array(
                'status'      => $status,
                'admin_notes' => $notes,
                'subtotal'    => $subtotal,
                'discount'    => $discount,
                'total'       => $total,
                'valid_until' => $valid,
            ) );
            wp_send_json_success( array( 'message' => 'Quote created.', 'id' => $new_id ) );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Send quote to customer                                             */
    /* ---------------------------------------------------------------- */
    public static function pcs_send_quote() {
        self::check_nonce_cap();
        $id = absint( $_POST['quote_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error( array( 'message' => 'Invalid quote.' ) );

        $result = PCS_Quotes::send_quote_to_customer( $id );

        if ( is_array( $result ) && $result['quote_sent'] ) {
            $msg = $result['email_sent']
                ? 'Quote sent to customer.'
                : 'Quote marked as sent. Email delivery failed — customer may need manual notification (check SMTP settings).';
            wp_send_json_success( array( 'message' => $msg, 'email_sent' => $result['email_sent'] ) );
        } else {
            wp_send_json_error( array( 'message' => 'Could not process quote.' ) );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Delete quote                                                       */
    /* ---------------------------------------------------------------- */
    public static function pcs_delete_quote() {
        self::check_nonce_cap();
        $id = absint( $_POST['quote_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error( array( 'message' => 'Invalid quote.' ) );
        PCS_Quotes::delete( $id );
        wp_send_json_success( array( 'message' => 'Quote deleted.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Customer: accept or decline a sent quote                           */
    /* ---------------------------------------------------------------- */
    public static function pcs_quote_respond() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ), 405 );
        }
        self::check_frontend_nonce();
        $quote_id = absint( $_POST['quote_id']  ?? 0 );
        $action   = sanitize_text_field( wp_unslash( $_POST['response'] ?? '' ) );

        if ( ! $quote_id || ! in_array( $action, array( 'accept', 'decline' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ) );
        }

        $result = PCS_Quotes::respond( $quote_id, $action, get_current_user_id() );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $new_status = $result['status'];
        $order_id   = $result['order_id'];
        $order_url  = $result['order_url'];

        if ( 'accept' === $action ) {
            $msg = $order_id
                ? __( 'Quote accepted! Your order has been created.', 'printcart-b2bstore' )
                : __( 'Quote accepted! Our team will contact you shortly.', 'printcart-b2bstore' );
        } else {
            $msg = __( 'Quote declined.', 'printcart-b2bstore' );
        }

        wp_send_json_success( array(
            'message'   => $msg,
            'status'    => $new_status,
            'order_id'  => $order_id,
            'order_url' => $order_url,
        ) );
    }

    /* ---------------------------------------------------------------- */
    /* Customer: submit a quote request from portal                       */
    /* ---------------------------------------------------------------- */
    public static function pcs_submit_quote_request() {
        self::check_frontend_nonce();

        $user_id = get_current_user_id();
        $title   = sanitize_text_field( $_POST['title']   ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( empty( $title ) ) {
            wp_send_json_error( array( 'message' => 'Title is required.' ) );
        }

        $store   = PCS_DB::get_store_by_user( $user_id );
        $id = PCS_Quotes::create( array(
            'user_id'  => $user_id,
            'store_id' => $store ? $store->id : null,
            'title'    => $title,
            'message'  => $message,
        ) );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'Could not submit request.' ) );
        }

        /* Notify admin */
        $quote = PCS_Quotes::get( $id );
        $user  = wp_get_current_user();
        if ( $quote ) PCS_Quotes::notify_admin_new_request( $quote, $user );

        wp_send_json_success( array( 'message' => 'Quote request submitted! We will respond within 24 hours.', 'id' => $id ) );
    }

    /* ---------------------------------------------------------------- */
    /* Invite member (frontend — store owner only)                        */
    /* ---------------------------------------------------------------- */
    public static function pcs_invite_member_fe() {
        self::check_frontend_nonce();

        $current_user_id = get_current_user_id();
        $tier    = PCS_DB::get_customer_tier( $current_user_id );
        $is_b2b  = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );

        if ( ! $is_b2b ) {
            wp_send_json_error( array( 'message' => 'B2B access required to invite team members.' ) );
        }

        $store = PCS_DB::get_store_by_user( $current_user_id );
        if ( ! $store ) {
            /* Auto-create store record for users whose tier was set directly by admin */
            $u = get_userdata( $current_user_id );
            PCS_DB::save_store( array(
                'user_id'       => $current_user_id,
                'company_name'  => $u->display_name,
                'contact_name'  => $u->display_name,
                'contact_email' => $u->user_email,
                'tier'          => $tier,
                'status'        => 'active',
                'team_size'     => 5,
            ) );
            $store = PCS_DB::get_store_by_user( $current_user_id );
        }
        if ( ! $store ) {
            wp_send_json_error( array( 'message' => 'Could not access store data.' ) );
        }

        /* Seat limit: active members + pending invites */
        $all_members   = PCS_DB::get_members( $store->id );
        $owner_id      = (int) $store->user_id;
        $active_count  = count( array_filter( $all_members, function( $m ) use ( $owner_id ) {
            return (int) $m->user_id !== $owner_id;
        } ) ) + 1;
        $pending_count = count( PCS_DB::get_team_invites_by_store( $store->id, 'pending' ) );
        $max_seats     = max( 1, (int) $store->team_size );

        if ( $active_count + $pending_count >= $max_seats ) {
            wp_send_json_error( array( 'message' => 'No available slots (including pending invites). Contact admin to increase your seat limit.' ) );
        }

        $email   = sanitize_email( $_POST['email'] ?? '' );
        $role    = sanitize_text_field( $_POST['role'] ?? 'member' );
        $limit   = floatval( $_POST['spending_limit'] ?? 0 );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Invalid email address.' ) );
        }

        /* Validate target */
        $target = get_user_by( 'email', $email );
        if ( $target ) {
            if ( $target->ID === $current_user_id || $target->ID === $owner_id ) {
                wp_send_json_error( array( 'message' => 'You cannot invite yourself.' ) );
            }
            if ( PCS_DB::get_member_by_user( $store->id, $target->ID ) ) {
                wp_send_json_error( array( 'message' => 'This user is already a team member.' ) );
            }
        }

        if ( PCS_DB::get_pending_team_invite( $store->id, $email ) ) {
            wp_send_json_error( array( 'message' => 'An invitation is already pending for this email.' ) );
        }

        /* Create invitation record */
        $token     = wp_generate_password( 32, false );
        $invite_id = PCS_DB::save_team_invite( array(
            'store_id'        => $store->id,
            'invited_email'   => $email,
            'invited_user_id' => $target ? $target->ID : 0,
            'role'            => $role,
            'spending_limit'  => $limit,
            'token'           => $token,
            'invited_by'      => $current_user_id,
            'message'         => $message,
            'expires_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
        ) );

        if ( ! $invite_id ) {
            wp_send_json_error( array( 'message' => 'Could not create invitation.' ) );
        }

        /* Send email (works on live site with SMTP; silently skips on local) */
        PCS_Team_Invitations::send_invite_email( $store, $token, $email, $role, $message );

        wp_send_json_success( array(
            'message' => 'Invitation sent to ' . $email . '. They will receive an email to accept or decline.',
            'invite'  => array(
                'id'    => $invite_id,
                'email' => $email,
                'role'  => ucfirst( $role ),
            ),
        ) );
    }

    /* ---------------------------------------------------------------- */
    /* Cancel pending team invitation (frontend — store owner only)      */
    /* ---------------------------------------------------------------- */
    public static function pcs_cancel_team_invite() {
        self::check_frontend_nonce();

        $current_user_id = get_current_user_id();
        $store = PCS_DB::get_store_by_user( $current_user_id );
        if ( ! $store ) {
            wp_send_json_error( array( 'message' => 'No B2B store found.' ) );
        }

        $invite_id = absint( $_POST['invite_id'] ?? 0 );
        if ( ! $invite_id ) {
            wp_send_json_error( array( 'message' => 'Invalid invite ID.' ) );
        }

        global $wpdb;
        $invite = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_team_invitations WHERE id = %d AND store_id = %d AND status = 'pending'",
            $invite_id, $store->id
        ) );
        if ( ! $invite ) {
            wp_send_json_error( array( 'message' => 'Invitation not found or already responded.' ) );
        }

        PCS_DB::update_team_invite_status( $invite_id, 'cancelled' );
        wp_send_json_success( array( 'message' => 'Invitation cancelled.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Remove member (frontend — store owner only)                        */
    /* ---------------------------------------------------------------- */
    public static function pcs_remove_member_fe() {
        self::check_frontend_nonce();

        $current_user_id = get_current_user_id();
        $tier   = PCS_DB::get_customer_tier( $current_user_id );
        $is_b2b = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );
        if ( ! $is_b2b ) {
            wp_send_json_error( array( 'message' => 'B2B access required.' ) );
        }

        $store = PCS_DB::get_store_by_user( $current_user_id );
        if ( ! $store ) {
            wp_send_json_error( array( 'message' => 'No B2B store found.' ) );
        }

        $member_id = absint( $_POST['member_id'] ?? 0 );
        if ( ! $member_id ) {
            wp_send_json_error( array( 'message' => 'Invalid member ID.' ) );
        }

        global $wpdb;
        $member = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_b2b_members WHERE id = %d AND store_id = %d",
            $member_id, $store->id
        ) );
        if ( ! $member ) {
            wp_send_json_error( array( 'message' => 'Member not found.' ) );
        }

        $removed_user_id = (int) $member->user_id;
        PCS_DB::remove_member( $member_id );

        /* Revert tier to pre-team value if it was auto-set when joining */
        PCS_Team_Invitations::maybe_revert_tier_on_leave( $removed_user_id, $store->id );

        wp_send_json_success( array( 'message' => 'Member removed.' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Auth helper                                                         */
    /* ---------------------------------------------------------------- */
    private static function check_nonce_cap() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'pcs_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
        }
    }

    private static function check_frontend_nonce() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'pcs_ajax' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Please log in.' ), 403 );
        }
    }
}
