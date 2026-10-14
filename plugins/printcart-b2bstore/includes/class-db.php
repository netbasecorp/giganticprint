<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_DB {

    /* Canonical store status is 'active'; 'approved' is accepted on read for legacy rows */
    const STORE_STATUS_ACTIVE = 'active';
    const STORE_APPROVED_STATUSES = array( 'active', 'approved' );

    public static function store_is_approved( $status ) {
        return in_array( (string) $status, self::STORE_APPROVED_STATUSES, true );
    }

    /* ---------------------------------------------------------------- */
    /* B2B Stores                                                         */
    /* ---------------------------------------------------------------- */

    public static function get_stores( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_b2b_stores';

        $defaults = array(
            'status'   => '',
            'tier'     => '',
            'search'   => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
            'per_page' => 20,
            'paged'    => 1,
        );
        $args   = wp_parse_args( $args, $defaults );
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['tier'] ) ) {
            $where[]  = 'tier = %s';
            $values[] = $args['tier'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where[]  = '(company_name LIKE %s OR contact_email LIKE %s OR contact_name LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $orderby   = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] ) ?: 'created_at DESC';
        $offset    = ( absint( $args['paged'] ) - 1 ) * absint( $args['per_page'] );

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $values[] = absint( $args['per_page'] );
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
    }

    public static function count_stores( $args = array() ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'pcs_b2b_stores';
        $where   = array( '1=1' );
        $values  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['tier'] ) ) {
            $where[]  = 'tier = %s';
            $values[] = $args['tier'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where[]  = '(company_name LIKE %s OR contact_email LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $sql       = "SELECT COUNT(*) FROM $table WHERE $where_sql";

        return (int) ( empty( $values )
            ? $wpdb->get_var( $sql )
            : $wpdb->get_var( $wpdb->prepare( $sql, $values ) )
        );
    }

    public static function get_store( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_b2b_stores WHERE id = %d",
            absint( $id )
        ) );
    }

    public static function get_store_by_user( $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_b2b_stores WHERE user_id = %d LIMIT 1",
            absint( $user_id )
        ) );
    }

    public static function get_store_by_id( $store_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_b2b_stores WHERE id = %d LIMIT 1",
            absint( $store_id )
        ) );
    }

    public static function save_store( $data ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'pcs_b2b_stores';
        $formats = array(
            'user_id'       => '%d',
            'company_name'  => '%s',
            'company_slug'  => '%s',
            'industry'      => '%s',
            'tier'          => '%s',
            'status'        => '%s',
            'contact_name'  => '%s',
            'contact_email' => '%s',
            'contact_phone' => '%s',
            'address'       => '%s',
            'tax_id'        => '%s',
            'website'       => '%s',
            'about'         => '%s',
            'monthly_volume'=> '%s',
            'team_size'     => '%d',
            'credit_limit'  => '%f',
            'payment_terms' => '%s',
            'notes'         => '%s',
        );

        $insert_data = array();
        $insert_fmt  = array();
        foreach ( $formats as $key => $fmt ) {
            if ( isset( $data[ $key ] ) ) {
                $insert_data[ $key ] = $data[ $key ];
                $insert_fmt[]        = $fmt;
            }
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $insert_data, array( 'id' => absint( $data['id'] ) ), $insert_fmt, array( '%d' ) );
            return absint( $data['id'] );
        } else {
            $wpdb->insert( $table, $insert_data, $insert_fmt );
            return $wpdb->insert_id;
        }
    }

    public static function delete_store( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'pcs_b2b_stores', array( 'id' => absint( $id ) ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'pcs_b2b_members', array( 'store_id' => absint( $id ) ), array( '%d' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Store stats                                                         */
    /* ---------------------------------------------------------------- */

    public static function get_store_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_b2b_stores';
        return array(
            'total'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
            'active'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'active'" ),
            'pending'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" ),
            'rejected' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'rejected'" ),
        );
    }

    /* ---------------------------------------------------------------- */
    /* Members                                                            */
    /* ---------------------------------------------------------------- */

    public static function get_members( $store_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM {$wpdb->prefix}pcs_b2b_members m
             LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id
             WHERE m.store_id = %d",
            absint( $store_id )
        ) );
    }

    public static function add_member( $store_id, $user_id, $role = 'member', $spending_limit = 0 ) {
        global $wpdb;
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pcs_b2b_members WHERE store_id = %d AND user_id = %d",
            absint( $store_id ), absint( $user_id )
        ) );
        if ( $existing ) return false;
        return $wpdb->insert(
            $wpdb->prefix . 'pcs_b2b_members',
            array(
                'store_id'       => absint( $store_id ),
                'user_id'        => absint( $user_id ),
                'role'           => sanitize_text_field( $role ),
                'spending_limit' => floatval( $spending_limit ),
            ),
            array( '%d', '%d', '%s', '%f' )
        );
    }

    public static function remove_member( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'pcs_b2b_members', array( 'id' => absint( $id ) ), array( '%d' ) );
    }

    public static function get_member_by_user( $store_id, $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_b2b_members WHERE store_id = %d AND user_id = %d",
            absint( $store_id ), absint( $user_id )
        ) );
    }

    /* ---------------------------------------------------------------- */
    /* Team Invitations                                                   */
    /* ---------------------------------------------------------------- */

    public static function save_team_invite( $data ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'pcs_team_invitations',
            array(
                'store_id'        => absint( $data['store_id'] ),
                'invited_email'   => sanitize_email( $data['invited_email'] ),
                'invited_user_id' => absint( $data['invited_user_id'] ?? 0 ),
                'role'            => sanitize_text_field( $data['role'] ?? 'member' ),
                'spending_limit'  => floatval( $data['spending_limit'] ?? 0 ),
                'token'           => sanitize_text_field( $data['token'] ),
                'status'          => 'pending',
                'invited_by'      => absint( $data['invited_by'] ),
                'message'         => sanitize_textarea_field( $data['message'] ?? '' ),
                'expires_at'      => $data['expires_at'],
            ),
            array( '%d', '%s', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%s' )
        );
        return $wpdb->insert_id ?: false;
    }

    public static function get_team_invite_by_token( $token ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_team_invitations WHERE token = %s",
            sanitize_text_field( $token )
        ) );
    }

    public static function get_team_invites_by_store( $store_id, $status = '' ) {
        global $wpdb;
        $sql  = "SELECT * FROM {$wpdb->prefix}pcs_team_invitations WHERE store_id = %d";
        $args = array( absint( $store_id ) );
        if ( $status ) {
            $sql   .= ' AND status = %s';
            $args[] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
    }

    public static function get_pending_invites_for_email( $email ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT ti.*, s.company_name
             FROM {$wpdb->prefix}pcs_team_invitations ti
             LEFT JOIN {$wpdb->prefix}pcs_b2b_stores s ON s.id = ti.store_id
             WHERE ti.invited_email = %s AND ti.status = 'pending' AND ti.expires_at > NOW()",
            sanitize_email( $email )
        ) );
    }

    public static function get_pending_team_invite( $store_id, $email ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_team_invitations
             WHERE store_id = %d AND invited_email = %s AND status = 'pending'",
            absint( $store_id ), sanitize_email( $email )
        ) );
    }

    public static function update_team_invite_status( $id, $status ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'pcs_team_invitations',
            array(
                'status'       => sanitize_text_field( $status ),
                'responded_at' => in_array( $status, array( 'accepted', 'declined' ), true )
                                  ? current_time( 'mysql' ) : null,
            ),
            array( 'id' => absint( $id ) ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    /* ---------------------------------------------------------------- */
    /* Invitations                                                        */
    /* ---------------------------------------------------------------- */

    public static function get_invitations( $args = array() ) {
        global $wpdb;
        $table    = $wpdb->prefix . 'pcs_invitations';
        $defaults = array(
            'status'   => '',
            'search'   => '',
            'per_page' => 20,
            'paged'    => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );
        $args   = wp_parse_args( $args, $defaults );
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where[]  = '(email LIKE %s OR company_name LIKE %s OR contact_name LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $offset    = ( absint( $args['paged'] ) - 1 ) * absint( $args['per_page'] );

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $values[] = absint( $args['per_page'] );
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
    }

    public static function count_invitations( $args = array() ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'pcs_invitations';
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        $where_sql = implode( ' AND ', $where );
        $sql       = "SELECT COUNT(*) FROM $table WHERE $where_sql";

        return (int) ( empty( $values )
            ? $wpdb->get_var( $sql )
            : $wpdb->get_var( $wpdb->prepare( $sql, $values ) )
        );
    }

    public static function get_invitation( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_invitations WHERE id = %d",
            absint( $id )
        ) );
    }

    public static function get_invitation_by_token( $token ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_invitations WHERE token = %s",
            sanitize_text_field( $token )
        ) );
    }

    public static function get_pending_invitation_by_email( $email ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_invitations WHERE email = %s AND status = 'pending' ORDER BY id DESC LIMIT 1",
            sanitize_email( $email )
        ) );
    }

    /* Single-use claim: pending -> accepted, true only for the one caller that wins */
    public static function claim_invitation( $id ) {
        global $wpdb;
        $rows = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}pcs_invitations SET status = 'accepted', accepted_at = %s WHERE id = %d AND status = 'pending'",
            current_time( 'mysql' ),
            absint( $id )
        ) );
        return 1 === (int) $rows;
    }

    public static function claim_team_invite( $id ) {
        global $wpdb;
        $rows = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}pcs_team_invitations SET status = 'accepted', responded_at = %s WHERE id = %d AND status = 'pending'",
            current_time( 'mysql' ),
            absint( $id )
        ) );
        return 1 === (int) $rows;
    }

    public static function get_invitation_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_invitations WHERE id = %d",
            absint( $id )
        ) );
    }

    public static function save_invitation( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_invitations';

        $fields = array( 'email', 'company_name', 'contact_name', 'proposed_tier',
                         'token', 'status', 'message', 'expires_at', 'accepted_at', 'created_by' );
        $insert = array();
        $fmt    = array();
        $fmt_map = array(
            'email' => '%s', 'company_name' => '%s', 'contact_name' => '%s',
            'proposed_tier' => '%s', 'token' => '%s', 'status' => '%s',
            'message' => '%s', 'expires_at' => '%s', 'accepted_at' => '%s',
            'created_by' => '%d',
        );

        foreach ( $fields as $f ) {
            if ( isset( $data[ $f ] ) ) {
                $insert[ $f ] = $data[ $f ];
                $fmt[]        = $fmt_map[ $f ];
            }
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $insert, array( 'id' => absint( $data['id'] ) ), $fmt, array( '%d' ) );
            return absint( $data['id'] );
        } else {
            $wpdb->insert( $table, $insert, $fmt );
            return $wpdb->insert_id;
        }
    }

    /* ---------------------------------------------------------------- */
    /* Invitation stats                                                   */
    /* ---------------------------------------------------------------- */

    public static function get_invitation_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_invitations';
        return array(
            'total'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
            'pending'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" ),
            'accepted' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'accepted'" ),
            'expired'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'expired'" ),
        );
    }

    /* ---------------------------------------------------------------- */
    /* Pricing Rules                                                      */
    /* ---------------------------------------------------------------- */

    public static function get_pricing_rules( $args = array() ) {
        global $wpdb;
        $table    = $wpdb->prefix . 'pcs_pricing_rules';
        $defaults = array(
            'status'    => '',
            'rule_type' => '',
            'per_page'  => 50,
            'paged'     => 1,
        );
        $args   = wp_parse_args( $args, $defaults );
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['rule_type'] ) ) {
            $where[]  = 'rule_type = %s';
            $values[] = $args['rule_type'];
        }

        $where_sql = implode( ' AND ', $where );
        $offset    = ( absint( $args['paged'] ) - 1 ) * absint( $args['per_page'] );

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY priority DESC, created_at ASC LIMIT %d OFFSET %d";
        $values[] = absint( $args['per_page'] );
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
    }

    public static function get_pricing_rule( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_pricing_rules WHERE id = %d",
            absint( $id )
        ) );
    }

    public static function save_pricing_rule( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_pricing_rules';

        $fields  = array( 'name', 'rule_type', 'target_type', 'target_value', 'discount_type',
                          'discount_value', 'min_order', 'payment_terms', 'free_shipping_threshold',
                          'promo_code', 'status', 'priority', 'valid_from', 'valid_until' );
        $fmt_map = array(
            'name' => '%s', 'rule_type' => '%s', 'target_type' => '%s', 'target_value' => '%s',
            'discount_type' => '%s', 'discount_value' => '%f', 'min_order' => '%f',
            'payment_terms' => '%s', 'free_shipping_threshold' => '%f',
            'promo_code' => '%s', 'status' => '%s', 'priority' => '%d',
            'valid_from' => '%s', 'valid_until' => '%s',
        );

        $insert = array();
        $fmt    = array();
        foreach ( $fields as $f ) {
            if ( isset( $data[ $f ] ) ) {
                $insert[ $f ] = $data[ $f ];
                $fmt[]        = $fmt_map[ $f ];
            }
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $insert, array( 'id' => absint( $data['id'] ) ), $fmt, array( '%d' ) );
            return absint( $data['id'] );
        } else {
            $wpdb->insert( $table, $insert, $fmt );
            return $wpdb->insert_id;
        }
    }

    public static function delete_pricing_rule( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'pcs_pricing_rules', array( 'id' => absint( $id ) ), array( '%d' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Customer tier helpers                                              */
    /* ---------------------------------------------------------------- */

    public static function get_customer_tier( $user_id ) {
        $tier = get_user_meta( absint( $user_id ), '_pcs_tier', true );
        return $tier ?: 'b2c';
    }

    public static function set_customer_tier( $user_id, $new_tier, $changed_by = 0, $note = '' ) {
        global $wpdb;
        $user_id  = absint( $user_id );
        $old_tier = self::get_customer_tier( $user_id );

        if ( $old_tier === $new_tier ) return false;

        update_user_meta( $user_id, '_pcs_tier', sanitize_text_field( $new_tier ) );

        $wpdb->insert(
            $wpdb->prefix . 'pcs_tier_history',
            array(
                'user_id'    => $user_id,
                'old_tier'   => $old_tier,
                'new_tier'   => $new_tier,
                'changed_by' => absint( $changed_by ),
                'note'       => sanitize_textarea_field( $note ),
            ),
            array( '%d', '%s', '%s', '%d', '%s' )
        );

        do_action( 'pcs_customer_tier_changed', $user_id, $old_tier, $new_tier );

        return true;
    }

    public static function get_tier_history( $user_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pcs_tier_history WHERE user_id = %d ORDER BY changed_at DESC LIMIT 10",
            absint( $user_id )
        ) );
    }

    /**
     * Lấy danh sách tier từ bảng pricing_rules (rule_type = 'tier', status = 'active').
     * Đây là nguồn dữ liệu duy nhất — admin thêm/sửa ở Pricing Rules là tự cập nhật khắp nơi.
     * Trả về array keyed by target_value: [ 'b2b_a' => ['label'=>'...','discount'=>10,...], ... ]
     */
    public static function get_tiers_from_rules() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT target_value, name, discount_value, payment_terms, free_shipping_threshold, priority
             FROM {$wpdb->prefix}pcs_pricing_rules
             WHERE rule_type = 'tier' AND status = 'active' AND target_value != ''
             ORDER BY priority DESC"
        );

        $tiers = array();
        foreach ( $rows as $row ) {
            $tiers[ $row->target_value ] = array(
                'label'                  => $row->name,
                'discount'               => (float) $row->discount_value,
                'payment_terms'          => $row->payment_terms,
                'free_shipping_threshold'=> $row->free_shipping_threshold,
            );
        }

        // Fallback: nếu bảng trống thì dùng option cũ
        if ( empty( $tiers ) ) {
            $tiers = get_option( 'pcs_tiers', PCS_Activator::default_tiers() );
        }

        return $tiers;
    }

    /**
     * Returns inline CSS styles (bg + color) for a tier pill.
     * Known tiers get fixed brand colors; unknown tiers get a deterministic
     * color picked from a palette using a hash of the tier key.
     *
     * @param  string $tier_key  e.g. 'b2b_a', 'vip', 'b2b_pro'
     * @return array  [ 'bg' => '#hex', 'color' => '#hex' ]
     */
    public static function get_tier_style( $tier_key ) {
        $known = array(
            'b2c'   => array( 'bg' => '#f3f4f6', 'color' => '#6b7280' ),
            'b2b_a' => array( 'bg' => '#dbeafe', 'color' => '#1e40af' ),
            'b2b_b' => array( 'bg' => '#fde68a', 'color' => '#92400e' ),
            'b2b_c' => array( 'bg' => '#c7d2fe', 'color' => '#3730a3' ),
            'vip'   => array( 'bg' => 'linear-gradient(135deg,#fde68a,#fbbf24)', 'color' => '#78350f' ),
        );

        if ( isset( $known[ $tier_key ] ) ) {
            return $known[ $tier_key ];
        }

        /* Palette for custom tiers — pick by hash so same key always same color */
        $palette = array(
            array( 'bg' => '#d1fae5', 'color' => '#065f46' ),   // emerald
            array( 'bg' => '#fce7f3', 'color' => '#9d174d' ),   // pink
            array( 'bg' => '#e0e7ff', 'color' => '#3730a3' ),   // indigo
            array( 'bg' => '#ffedd5', 'color' => '#9a3412' ),   // orange
            array( 'bg' => '#ede9fe', 'color' => '#5b21b6' ),   // violet
            array( 'bg' => '#ecfeff', 'color' => '#164e63' ),   // cyan
            array( 'bg' => '#fef9c3', 'color' => '#854d0e' ),   // amber
            array( 'bg' => '#f0fdf4', 'color' => '#14532d' ),   // green
            array( 'bg' => '#fdf2f8', 'color' => '#831843' ),   // rose
            array( 'bg' => '#f0fdfa', 'color' => '#134e4a' ),   // teal
        );

        $idx = abs( crc32( $tier_key ) ) % count( $palette );
        return $palette[ $idx ];
    }

    /**
     * Render a tier pill <span> with dynamic color.
     */
    public static function tier_pill_html( $tier_key, $label = '' ) {
        if ( ! $label ) {
            $tiers = self::get_tiers_from_rules();
            $label = isset( $tiers[ $tier_key ]['label'] ) ? $tiers[ $tier_key ]['label'] : ucwords( str_replace( '_', ' ', $tier_key ) );
        }
        $style = self::get_tier_style( $tier_key );
        $bg    = esc_attr( $style['bg'] );
        $fg    = esc_attr( $style['color'] );
        return sprintf(
            '<span class="pcs-tier-pill" data-tier="%s" style="background:%s;color:%s;">%s</span>',
            esc_attr( $tier_key ),
            $bg, $fg,
            esc_html( $label )
        );
    }

    public static function get_tier_counts() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT meta_value AS tier, COUNT(*) AS cnt
             FROM {$wpdb->usermeta}
             WHERE meta_key = '_pcs_tier'
             GROUP BY meta_value"
        );
        /* Initialise from pricing rules so custom tiers (e.g. "pro") are always present */
        $tiers  = self::get_tiers_from_rules();
        $counts = array_fill_keys( array_keys( $tiers ), 0 );
        foreach ( $rows as $row ) {
            $counts[ $row->tier ] = (int) $row->cnt;   // includes any key, even unknown
        }
        return $counts;
    }
}
