<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Activator {

    public static function activate() {
        self::create_tables();
        self::seed_pricing_rules();
        self::add_capabilities();
        update_option( 'pcs_version', PCS_VERSION );
        update_option( 'pcs_tiers', self::default_tiers() );
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    /* Run once after plugin update to create new tables on existing installs */
    public static function maybe_run_migrations() {
        if ( get_option( 'pcs_db_version' ) === '1.1' ) return;
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_team_invitations (
            id              bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            store_id        bigint(20) UNSIGNED NOT NULL,
            invited_email   varchar(255)        NOT NULL DEFAULT '',
            invited_user_id bigint(20) UNSIGNED          DEFAULT 0,
            role            varchar(20)         NOT NULL DEFAULT 'member',
            spending_limit  decimal(10,2)       NOT NULL DEFAULT 0.00,
            token           varchar(64)         NOT NULL DEFAULT '',
            status          varchar(20)         NOT NULL DEFAULT 'pending',
            invited_by      bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            message         text,
            created_at      datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at      datetime            NOT NULL,
            responded_at    datetime                     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY store_id (store_id),
            KEY invited_email (invited_email),
            KEY status (status)
        ) $charset;" );
        update_option( 'pcs_db_version', '1.1' );
    }

    public static function maybe_run_quotes_migration() {
        if ( get_option( 'pcs_db_version' ) === '1.3' ) return;
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_quotes (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quote_number   varchar(30)         NOT NULL DEFAULT '',
            user_id        bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            store_id       bigint(20) UNSIGNED          DEFAULT NULL,
            status         varchar(20)         NOT NULL DEFAULT 'new_request',
            title          varchar(255)                 DEFAULT '',
            message        text,
            admin_notes    text,
            subtotal       decimal(10,2)       NOT NULL DEFAULT 0.00,
            discount       decimal(10,2)       NOT NULL DEFAULT 0.00,
            total          decimal(10,2)       NOT NULL DEFAULT 0.00,
            currency       varchar(10)         NOT NULL DEFAULT 'USD',
            valid_until    date                         DEFAULT NULL,
            sent_at        datetime                     DEFAULT NULL,
            responded_at   datetime                     DEFAULT NULL,
            wc_order_id    bigint(20) UNSIGNED          DEFAULT NULL,
            created_at     datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at     datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY quote_number (quote_number),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_quote_items (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quote_id    bigint(20) UNSIGNED NOT NULL,
            product_id  bigint(20) UNSIGNED          DEFAULT 0,
            description varchar(500)        NOT NULL DEFAULT '',
            notes       varchar(500)                 DEFAULT '',
            sku         varchar(100)                 DEFAULT '',
            quantity    int(11)             NOT NULL DEFAULT 1,
            unit_price  decimal(10,2)       NOT NULL DEFAULT 0.00,
            total       decimal(10,2)       NOT NULL DEFAULT 0.00,
            sort_order  int(11)             NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY quote_id (quote_id)
        ) $charset;" );

        update_option( 'pcs_db_version', '1.3' );
    }

    /* ---------------------------------------------------------------- */
    /* DB Tables                                                          */
    /* ---------------------------------------------------------------- */
    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /* B2B store accounts */
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_b2b_stores (
            id            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id       bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            company_name  varchar(255)        NOT NULL DEFAULT '',
            company_slug  varchar(255)                 DEFAULT '',
            industry      varchar(100)                 DEFAULT '',
            tier          varchar(20)         NOT NULL DEFAULT 'b2b_a',
            status        varchar(20)         NOT NULL DEFAULT 'pending',
            contact_name  varchar(255)                 DEFAULT '',
            contact_email varchar(255)                 DEFAULT '',
            contact_phone varchar(100)                 DEFAULT '',
            address       text,
            tax_id        varchar(100)                 DEFAULT '',
            website       varchar(255)                 DEFAULT '',
            about         text,
            monthly_volume varchar(100)                DEFAULT '',
            team_size     int(11)             NOT NULL DEFAULT 1,
            credit_limit  decimal(10,2)       NOT NULL DEFAULT 0.00,
            payment_terms varchar(20)         NOT NULL DEFAULT 'prepay',
            notes         text,
            created_at    datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY tier (tier)
        ) $charset;" );

        /* Team members */
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_b2b_members (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            store_id       bigint(20) UNSIGNED NOT NULL,
            user_id        bigint(20) UNSIGNED NOT NULL,
            role           varchar(20)         NOT NULL DEFAULT 'member',
            spending_limit decimal(10,2)       NOT NULL DEFAULT 0.00,
            status         varchar(20)         NOT NULL DEFAULT 'active',
            joined_at      datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY store_id (store_id),
            KEY user_id (user_id)
        ) $charset;" );

        /* Invitations */
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_invitations (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email          varchar(255)        NOT NULL DEFAULT '',
            company_name   varchar(255)                 DEFAULT '',
            contact_name   varchar(255)                 DEFAULT '',
            proposed_tier  varchar(20)         NOT NULL DEFAULT 'b2b_b',
            token          varchar(64)         NOT NULL DEFAULT '',
            status         varchar(20)         NOT NULL DEFAULT 'pending',
            message        text,
            expires_at     datetime,
            accepted_at    datetime,
            created_by     bigint(20) UNSIGNED          DEFAULT 0,
            created_at     datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY email (email),
            KEY status (status)
        ) $charset;" );

        /* Pricing rules */
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_pricing_rules (
            id                       bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name                     varchar(255)        NOT NULL DEFAULT '',
            rule_type                varchar(30)         NOT NULL DEFAULT 'tier',
            target_type              varchar(30)         NOT NULL DEFAULT 'tier',
            target_value             varchar(255)                 DEFAULT '',
            discount_type            varchar(20)         NOT NULL DEFAULT 'percentage',
            discount_value           decimal(10,4)       NOT NULL DEFAULT 0.0000,
            min_order                decimal(10,2)       NOT NULL DEFAULT 0.00,
            payment_terms            varchar(20)                  DEFAULT '',
            free_shipping_threshold  decimal(10,2)                DEFAULT NULL,
            promo_code               varchar(100)                 DEFAULT '',
            status                   varchar(20)         NOT NULL DEFAULT 'active',
            priority                 int(11)             NOT NULL DEFAULT 10,
            valid_from               date                         DEFAULT NULL,
            valid_until              date                         DEFAULT NULL,
            created_at               datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY rule_type (rule_type)
        ) $charset;" );

        /* Team member invitations */
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_team_invitations (
            id              bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            store_id        bigint(20) UNSIGNED NOT NULL,
            invited_email   varchar(255)        NOT NULL DEFAULT '',
            invited_user_id bigint(20) UNSIGNED          DEFAULT 0,
            role            varchar(20)         NOT NULL DEFAULT 'member',
            spending_limit  decimal(10,2)       NOT NULL DEFAULT 0.00,
            token           varchar(64)         NOT NULL DEFAULT '',
            status          varchar(20)         NOT NULL DEFAULT 'pending',
            invited_by      bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            message         text,
            created_at      datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at      datetime            NOT NULL,
            responded_at    datetime                     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY store_id (store_id),
            KEY invited_email (invited_email),
            KEY status (status)
        ) $charset;" );

        /* Tier history */
        dbDelta( "CREATE TABLE {$wpdb->prefix}pcs_tier_history (
            id         bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id    bigint(20) UNSIGNED NOT NULL,
            old_tier   varchar(20)         NOT NULL DEFAULT '',
            new_tier   varchar(20)         NOT NULL DEFAULT '',
            changed_by bigint(20) UNSIGNED          DEFAULT 0,
            note       text,
            changed_at datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset;" );
    }

    /* ---------------------------------------------------------------- */
    /* Seed default pricing rules                                         */
    /* ---------------------------------------------------------------- */
    private static function seed_pricing_rules() {
        global $wpdb;
        $table = $wpdb->prefix . 'pcs_pricing_rules';

        // Only seed if table is empty
        if ( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) > 0 ) {
            return;
        }

        $defaults = array(
            array(
                'name'            => 'B2C — Retail price',
                'rule_type'       => 'tier',
                'target_type'     => 'tier',
                'target_value'    => 'b2c',
                'discount_type'   => 'percentage',
                'discount_value'  => 0,
                'min_order'       => 0,
                'payment_terms'   => 'prepay',
                'free_shipping_threshold' => 200,
                'status'          => 'active',
                'priority'        => 100,
            ),
            array(
                'name'            => 'B2B — Tier A (10%)',
                'rule_type'       => 'tier',
                'target_type'     => 'tier',
                'target_value'    => 'b2b_a',
                'discount_type'   => 'percentage',
                'discount_value'  => 10,
                'min_order'       => 100,
                'payment_terms'   => 'net15',
                'free_shipping_threshold' => 150,
                'status'          => 'active',
                'priority'        => 90,
            ),
            array(
                'name'            => 'B2B — Tier B (15%)',
                'rule_type'       => 'tier',
                'target_type'     => 'tier',
                'target_value'    => 'b2b_b',
                'discount_type'   => 'percentage',
                'discount_value'  => 15,
                'min_order'       => 250,
                'payment_terms'   => 'net15',
                'free_shipping_threshold' => 100,
                'status'          => 'active',
                'priority'        => 80,
            ),
            array(
                'name'            => 'B2B — Tier C (20%)',
                'rule_type'       => 'tier',
                'target_type'     => 'tier',
                'target_value'    => 'b2b_c',
                'discount_type'   => 'percentage',
                'discount_value'  => 20,
                'min_order'       => 500,
                'payment_terms'   => 'net30',
                'free_shipping_threshold' => 0,
                'status'          => 'active',
                'priority'        => 70,
            ),
            array(
                'name'            => 'VIP (24%)',
                'rule_type'       => 'tier',
                'target_type'     => 'tier',
                'target_value'    => 'vip',
                'discount_type'   => 'percentage',
                'discount_value'  => 24,
                'min_order'       => 0,
                'payment_terms'   => 'net30',
                'free_shipping_threshold' => 0,
                'status'          => 'active',
                'priority'        => 60,
            ),
        );

        foreach ( $defaults as $rule ) {
            $wpdb->insert( $table, $rule );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Capabilities                                                       */
    /* ---------------------------------------------------------------- */
    private static function add_capabilities() {
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'manage_pcs' );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Default tier definitions                                           */
    /* ---------------------------------------------------------------- */
    public static function default_tiers() {
        return array(
            'b2c'   => array( 'label' => 'B2C',         'discount' => 0,  'color' => '#6b7280', 'min_spend_upgrade' => 0 ),
            'b2b_a' => array( 'label' => 'B2B · Tier A', 'discount' => 10, 'color' => '#2271b1', 'min_spend_upgrade' => 1000 ),
            'b2b_b' => array( 'label' => 'B2B · Tier B', 'discount' => 15, 'color' => '#92400e', 'min_spend_upgrade' => 5000 ),
            'b2b_c' => array( 'label' => 'B2B · Tier C', 'discount' => 20, 'color' => '#3730a3', 'min_spend_upgrade' => 15000 ),
            'vip'   => array( 'label' => '★ VIP',        'discount' => 24, 'color' => '#78350f', 'min_spend_upgrade' => 0 ),
        );
    }
}
