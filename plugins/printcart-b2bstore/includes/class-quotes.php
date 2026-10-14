<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Quotes {

    public static function init() {
        add_action( 'admin_init', array( 'PCS_Activator', 'maybe_run_quotes_migration' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Generate unique quote number                                       */
    /* ---------------------------------------------------------------- */
    public static function generate_number() {
        return 'Q-' . date( 'Y' ) . '-' . str_pad( rand( 1, 9999 ), 4, '0', STR_PAD_LEFT );
    }

    /* ---------------------------------------------------------------- */
    /* CRUD                                                               */
    /* ---------------------------------------------------------------- */
    public static function create( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_quotes';

        do {
            $number = self::generate_number();
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE quote_number = %s", $number ) );
        } while ( $exists );

        $wpdb->insert( $table, array(
            'quote_number' => $number,
            'user_id'      => absint( $data['user_id'] ?? 0 ),
            'store_id'     => ! empty( $data['store_id'] ) ? absint( $data['store_id'] ) : null,
            'status'       => 'new_request',
            'title'        => sanitize_text_field( $data['title'] ?? '' ),
            'message'      => sanitize_textarea_field( $data['message'] ?? '' ),
            'currency'     => get_woocommerce_currency(),
        ), array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' ) );

        return $wpdb->insert_id ?: false;
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT q.*, u.display_name, u.user_email
             FROM {$wpdb->prefix}pcs_quotes q
             LEFT JOIN {$wpdb->users} u ON u.ID = q.user_id
             WHERE q.id = %d",
            absint( $id )
        ) );
    }

    public static function get_by_number( $number ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_quotes WHERE quote_number = %s",
            sanitize_text_field( $number )
        ) );
    }

    public static function get_list( $args = array() ) {
        global $wpdb;
        $defaults = array(
            'status'   => '',
            'user_id'  => 0,
            'search'   => '',
            'per_page' => 20,
            'paged'    => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );
        $args    = wp_parse_args( $args, $defaults );
        $where   = array( '1=1' );
        $values  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'q.status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['user_id'] ) ) {
            $where[]  = 'q.user_id = %d';
            $values[] = absint( $args['user_id'] );
        }
        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[]  = '(q.quote_number LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR q.title LIKE %s)';
            $values[] = $like; $values[] = $like; $values[] = $like; $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $offset    = ( absint( $args['paged'] ) - 1 ) * absint( $args['per_page'] );
        $values[]  = absint( $args['per_page'] );
        $values[]  = $offset;

        $sql = "SELECT q.*, u.display_name, u.user_email
                FROM {$wpdb->prefix}pcs_quotes q
                LEFT JOIN {$wpdb->users} u ON u.ID = q.user_id
                WHERE $where_sql
                ORDER BY q.{$args['orderby']} {$args['order']}
                LIMIT %d OFFSET %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
    }

    public static function count( $args = array() ) {
        global $wpdb;
        $where  = array( '1=1' );
        $values = array();
        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['user_id'] ) ) {
            $where[]  = 'user_id = %d';
            $values[] = absint( $args['user_id'] );
        }
        $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'pcs_quotes WHERE ' . implode( ' AND ', $where );
        return (int) ( $values ? $wpdb->get_var( $wpdb->prepare( $sql, $values ) ) : $wpdb->get_var( $sql ) );
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $allowed = array( 'status', 'title', 'admin_notes', 'subtotal', 'discount', 'total', 'valid_until', 'sent_at', 'responded_at', 'wc_order_id' );
        $set     = array();
        $values  = array();
        foreach ( $allowed as $col ) {
            if ( array_key_exists( $col, $data ) ) {
                $set[]   = "$col = %s";
                $values[] = $data[ $col ];
            }
        }
        if ( empty( $set ) ) return false;
        $values[] = absint( $id );
        return $wpdb->query( $wpdb->prepare(
            'UPDATE ' . $wpdb->prefix . 'pcs_quotes SET ' . implode( ', ', $set ) . ' WHERE id = %d',
            $values
        ) );
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'pcs_quote_items', array( 'quote_id' => absint( $id ) ) );
        $wpdb->delete( $wpdb->prefix . 'pcs_quotes',      array( 'id'       => absint( $id ) ) );
    }

    /* ---------------------------------------------------------------- */
    /* Quote items                                                        */
    /* ---------------------------------------------------------------- */
    public static function get_items( $quote_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_quote_items WHERE quote_id = %d ORDER BY sort_order ASC, id ASC",
            absint( $quote_id )
        ) );
    }

    public static function save_items( $quote_id, $items ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_quote_items';
        $wpdb->delete( $table, array( 'quote_id' => absint( $quote_id ) ) );

        $subtotal = 0;
        foreach ( $items as $i => $item ) {
            $qty   = max( 1, absint( $item['quantity'] ?? 1 ) );
            $price = (float) ( $item['unit_price'] ?? 0 );
            $total = round( $qty * $price, 2 );
            $subtotal += $total;
            $wpdb->insert( $table, array(
                'quote_id'    => absint( $quote_id ),
                'product_id'  => absint( $item['product_id'] ?? 0 ),
                'description' => sanitize_text_field( $item['description'] ?? '' ),
                'notes'       => sanitize_text_field( $item['notes'] ?? '' ),
                'sku'         => sanitize_text_field( $item['sku'] ?? '' ),
                'quantity'    => $qty,
                'unit_price'  => $price,
                'total'       => $total,
                'sort_order'  => $i,
            ) );
        }
        return $subtotal;
    }

    /* ---------------------------------------------------------------- */
    /* Create a WooCommerce order from an accepted quote                  */
    /* ---------------------------------------------------------------- */
    public static function create_order_from_quote( $quote_id ) {
        $quote = self::get( $quote_id );
        if ( ! $quote ) return new WP_Error( 'not_found', 'Quote not found.' );

        $items = self::get_items( $quote_id );
        $user  = get_userdata( $quote->user_id );
        if ( ! $user ) return new WP_Error( 'no_user', 'Customer not found.' );

        $order = wc_create_order( array(
            'customer_id' => (int) $quote->user_id,
            'status'      => 'pending',
        ) );
        if ( is_wp_error( $order ) ) return $order;

        /* Add line items */
        foreach ( $items as $item ) {
            $product   = ! empty( $item->product_id ) ? wc_get_product( $item->product_id ) : null;
            $line_item = new WC_Order_Item_Product();
            $line_item->set_quantity( max( 1, (int) $item->quantity ) );
            $line_item->set_subtotal( (float) $item->total );
            $line_item->set_total( (float) $item->total );

            if ( $product ) {
                $line_item->set_product( $product );
                if ( $item->description && $item->description !== $product->get_name() ) {
                    $line_item->set_name( $item->description );
                }
            } else {
                $line_item->set_name( $item->description ?: __( 'Custom item', 'printcart-b2bstore' ) );
            }

            if ( ! empty( $item->notes ) ) {
                $line_item->add_meta_data( 'Notes', $item->notes, true );
            }

            $order->add_item( $line_item );
        }

        /* Quote-level discount as a fee line */
        if ( (float) $quote->discount > 0 ) {
            $fee = new WC_Order_Item_Fee();
            $fee->set_name( __( 'Quote discount', 'printcart-b2bstore' ) );
            $fee->set_total( -(float) $quote->discount );
            $fee->set_tax_status( 'none' );
            $order->add_item( $fee );
        }

        /* Billing address from user meta */
        $order->set_billing_email( $user->user_email );
        $order->set_billing_first_name( get_user_meta( $user->ID, 'billing_first_name', true ) ?: $user->first_name );
        $order->set_billing_last_name(  get_user_meta( $user->ID, 'billing_last_name',  true ) ?: $user->last_name );
        $order->set_billing_phone(      get_user_meta( $user->ID, 'billing_phone',       true ) );
        $order->set_billing_address_1(  get_user_meta( $user->ID, 'billing_address_1',  true ) );
        $order->set_billing_city(       get_user_meta( $user->ID, 'billing_city',        true ) );
        $order->set_billing_country(    get_user_meta( $user->ID, 'billing_country',     true ) );

        /* Override total with the agreed quote total */
        $order->set_total( (float) $quote->total );

        /* Link order ↔ quote */
        $order->update_meta_data( '_pcs_from_quote',   $quote->quote_number );
        $order->update_meta_data( '_pcs_quote_id',     $quote_id );
        $order->add_order_note( sprintf(
            /* translators: %s = quote number */
            __( 'Order created automatically from quote %s.', 'printcart-b2bstore' ),
            $quote->quote_number
        ) );

        $order->save();

        /* Save WC order ID back to quote */
        self::update( $quote_id, array( 'wc_order_id' => $order->get_id() ) );

        return $order->get_id();
    }

    /* ---------------------------------------------------------------- */
    /* Stats for admin dashboard                                          */
    /* ---------------------------------------------------------------- */
    public static function get_stats() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) as cnt FROM {$wpdb->prefix}pcs_quotes GROUP BY status"
        );
        $stats = array(
            'new_request' => 0, 'in_review' => 0,
            'sent'        => 0, 'accepted'  => 0,
            'declined'    => 0, 'expired'   => 0, 'total' => 0,
        );
        foreach ( $rows as $r ) {
            if ( isset( $stats[ $r->status ] ) ) $stats[ $r->status ] = (int) $r->cnt;
            $stats['total'] += (int) $r->cnt;
        }
        /* Accepted value (30d) */
        $stats['accepted_value'] = (float) $wpdb->get_var(
            "SELECT SUM(total) FROM {$wpdb->prefix}pcs_quotes
             WHERE status = 'accepted' AND responded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        return $stats;
    }

    /* ---------------------------------------------------------------- */
    /* Email: notify admin of new request                                  */
    /* ---------------------------------------------------------------- */
    public static function notify_admin_new_request( $quote, $user ) {
        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        $review_url  = admin_url( 'admin.php?page=printcart-quotes&action=view&id=' . $quote->id );

        $subject = sprintf( '[%s] New quote request %s from %s', $site_name, $quote->quote_number, $user->display_name );
        $body    = '<div style="font-family:sans-serif;max-width:540px;margin:0 auto;">'
                 . '<div style="background:linear-gradient(135deg,#1d2327,#2563eb);padding:24px;border-radius:8px 8px 0 0;">'
                 . '<h2 style="margin:0;color:#fff;font-size:17px;">📋 New Quote Request</h2>'
                 . '<p style="margin:4px 0 0;color:rgba(255,255,255,.7);font-size:13px;">' . esc_html( $site_name ) . ' · ' . esc_html( $quote->quote_number ) . '</p>'
                 . '</div>'
                 . '<div style="background:#fff;padding:24px;border:1px solid #e5e7eb;border-top:none;">'
                 . '<p style="margin:0 0 14px;font-size:14px;color:#374151;"><strong>' . esc_html( $user->display_name ) . '</strong> (' . esc_html( $user->user_email ) . ') submitted a quote request.</p>'
                 . '<p style="margin:0 0 14px;font-size:14px;color:#374151;"><strong>Title:</strong> ' . esc_html( $quote->title ) . '</p>'
                 . ( $quote->message ? '<p style="background:#f9fafb;padding:12px;border-radius:6px;font-size:13px;color:#374151;margin:0 0 14px;">' . nl2br( esc_html( $quote->message ) ) . '</p>' : '' )
                 . '<a href="' . esc_url( $review_url ) . '" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:700;">Review request →</a>'
                 . '</div></div>';

        wp_mail( $admin_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Email: notify admin when customer accepts or declines              */
    /* ---------------------------------------------------------------- */
    public static function notify_admin_quote_response( $quote_id, $status ) {
        $quote = self::get( $quote_id );
        if ( ! $quote ) return;

        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        $review_url  = admin_url( 'admin.php?page=printcart-quotes&action=view&id=' . $quote_id );
        $is_accept   = 'accepted' === $status;

        $subject = sprintf(
            '[%s] Quote %s %s by %s',
            $site_name,
            $quote->quote_number,
            $is_accept ? 'ACCEPTED' : 'DECLINED',
            $quote->display_name
        );

        $color  = $is_accept ? '#059669' : '#dc2626';
        $icon   = $is_accept ? '✅' : '✕';
        $label  = $is_accept ? 'ACCEPTED' : 'DECLINED';

        $body = '<div style="font-family:sans-serif;max-width:540px;margin:0 auto;">'
              . '<div style="background:' . $color . ';padding:20px 28px;border-radius:8px 8px 0 0;">'
              . '<h2 style="margin:0;color:#fff;font-size:16px;">' . $icon . ' Quote ' . $label . '</h2>'
              . '<p style="margin:4px 0 0;color:rgba(255,255,255,.8);font-size:13px;">' . esc_html( $site_name ) . ' · ' . esc_html( $quote->quote_number ) . '</p>'
              . '</div>'
              . '<div style="background:#fff;padding:24px 28px;border:1px solid #e5e7eb;border-top:none;">'
              . '<p style="margin:0 0 14px;font-size:14px;color:#374151;">'
              . '<strong>' . esc_html( $quote->display_name ) . '</strong> has <strong>' . strtolower( $label ) . '</strong> quote <strong>' . esc_html( $quote->quote_number ) . '</strong> — <em>' . esc_html( $quote->title ) . '</em>'
              . ( $is_accept ? ' valued at <strong>' . strip_tags( wc_price( $quote->total ) ) . '</strong>.' : '.' )
              . '</p>'
              . ( $is_accept ? '<p style="background:#f0fdf4;border-left:3px solid #059669;padding:10px 14px;border-radius:0 6px 6px 0;font-size:13px;color:#065f46;margin:0 0 18px;">🎉 Great news! A WooCommerce order has been created automatically — please review it and confirm production.</p>' : '' )
              . '<a href="' . esc_url( $review_url ) . '" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 22px;border-radius:8px;font-size:14px;font-weight:700;">View quote →</a>'
              . '</div></div>';

        wp_mail( $admin_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Email: send quote to customer                                       */
    /* ---------------------------------------------------------------- */
    public static function send_quote_to_customer( $quote_id ) {
        $quote = self::get( $quote_id );
        if ( ! $quote ) return false;

        $items     = self::get_items( $quote_id );
        $user      = get_userdata( $quote->user_id );
        if ( ! $user ) return false;

        $portal_id = (int) get_option( 'pcs_portal_page_id' );
        $view_url  = $portal_id
            ? add_query_arg( array( 'section' => 'quotes', 'quote_id' => $quote_id ), get_permalink( $portal_id ) )
            : home_url( '/' );
        $site_name = get_bloginfo( 'name' );
        $expiry    = $quote->valid_until ? date_i18n( get_option( 'date_format' ), strtotime( $quote->valid_until ) ) : '—';

        /* Build items table */
        $items_html = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0;">'
                    . '<tr style="background:#f3f4f6;"><th style="padding:8px 12px;text-align:left;font-size:12px;color:#6b7280;">Description</th>'
                    . '<th style="padding:8px 12px;text-align:center;font-size:12px;color:#6b7280;">Qty</th>'
                    . '<th style="padding:8px 12px;text-align:right;font-size:12px;color:#6b7280;">Unit price</th>'
                    . '<th style="padding:8px 12px;text-align:right;font-size:12px;color:#6b7280;">Total</th></tr>';
        foreach ( $items as $item ) {
            $items_html .= '<tr style="border-bottom:1px solid #f3f4f6;">'
                         . '<td style="padding:8px 12px;font-size:13px;">' . esc_html( $item->description ) . '</td>'
                         . '<td style="padding:8px 12px;font-size:13px;text-align:center;">' . (int) $item->quantity . '</td>'
                         . '<td style="padding:8px 12px;font-size:13px;text-align:right;">' . wc_price( $item->unit_price ) . '</td>'
                         . '<td style="padding:8px 12px;font-size:13px;text-align:right;font-weight:600;">' . wc_price( $item->total ) . '</td>'
                         . '</tr>';
        }
        if ( $quote->discount > 0 ) {
            $items_html .= '<tr><td colspan="3" style="padding:8px 12px;font-size:13px;text-align:right;color:#059669;">Discount</td>'
                         . '<td style="padding:8px 12px;font-size:13px;text-align:right;color:#059669;">-' . wc_price( $quote->discount ) . '</td></tr>';
        }
        $items_html .= '<tr style="background:#f9fafb;"><td colspan="3" style="padding:10px 12px;font-size:14px;font-weight:700;text-align:right;">Total</td>'
                     . '<td style="padding:10px 12px;font-size:15px;font-weight:700;text-align:right;color:#2563eb;">' . wc_price( $quote->total ) . '</td></tr>'
                     . '</table>';

        $subject = sprintf( 'Your quote %s from %s', $quote->quote_number, $site_name );
        $body    = '<div style="font-family:sans-serif;max-width:560px;margin:0 auto;">'
                 . '<div style="background:linear-gradient(135deg,#1d2327,#2563eb);padding:24px;border-radius:8px 8px 0 0;">'
                 . '<h2 style="margin:0;color:#fff;font-size:17px;">Your Quote is Ready</h2>'
                 . '<p style="margin:4px 0 0;color:rgba(255,255,255,.7);font-size:13px;">' . esc_html( $quote->quote_number ) . ' · Valid until ' . esc_html( $expiry ) . '</p>'
                 . '</div>'
                 . '<div style="background:#fff;padding:24px;border:1px solid #e5e7eb;border-top:none;">'
                 . '<p style="margin:0 0 12px;font-size:14px;color:#374151;">Hi <strong>' . esc_html( $user->display_name ) . '</strong>,</p>'
                 . '<p style="margin:0 0 16px;font-size:14px;color:#374151;">We\'ve prepared a quote for your request <strong>' . esc_html( $quote->title ) . '</strong>.</p>'
                 . $items_html
                 . ( $quote->admin_notes ? '<div style="background:#fffbeb;border-left:3px solid #f59e0b;padding:12px 16px;border-radius:0 6px 6px 0;font-size:13px;color:#374151;margin:16px 0;">' . nl2br( esc_html( $quote->admin_notes ) ) . '</div>' : '' )
                 . '<div style="display:flex;gap:12px;margin-top:20px;">'
                 . '<a href="' . esc_url( add_query_arg( 'pcs_quote_action', 'accept', $view_url ) ) . '" style="flex:1;display:inline-block;background:#059669;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:700;text-align:center;">✓ Accept quote</a>'
                 . '<a href="' . esc_url( add_query_arg( 'pcs_quote_action', 'decline', $view_url ) ) . '" style="flex:1;display:inline-block;background:#f3f4f6;color:#374151;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:700;text-align:center;">✕ Decline</a>'
                 . '</div>'
                 . '</div></div>';

        /* Status = sent regardless of email delivery (admin's intent, not mail delivery status) */
        self::update( $quote_id, array( 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ) );

        $email_sent = wp_mail( $user->user_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

        /* Return array so caller can distinguish quote-sent vs email-sent */
        return array( 'quote_sent' => true, 'email_sent' => $email_sent );
    }
}
