<?php
/**
 * Per-company private products: a product assigned to one or more B2B companies is visible
 * only to the members (owner included) of those companies and to shop managers/admins.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Private_Products {

    const DB_VERSION     = '1';
    const OPT_DB_VERSION = 'pcs_b4_db_version';
    const OPT_SETTINGS   = 'pcs_store_settings';
    const TRANSIENT      = 'pcs_private_product_ids';
    const DESIGNER_SLUG  = 'create-your-own';

    private static $private_ids = null;
    private static $allowed     = array();
    private static $in_query    = false;

    public static function init() {
        self::maybe_create_table();

        add_action( 'pre_get_posts', array( __CLASS__, 'filter_queries' ), 20 );
        add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'filter_is_visible' ), 20, 2 );
        add_action( 'template_redirect', array( __CLASS__, 'maybe_404' ), 1 );
        add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 5, 2 );
        add_filter( 'woocommerce_related_products', array( __CLASS__, 'filter_id_list' ), 20 );
        add_filter( 'woocommerce_product_get_upsell_ids', array( __CLASS__, 'filter_id_list' ), 20 );
        add_filter( 'woocommerce_product_get_cross_sell_ids', array( __CLASS__, 'filter_id_list' ), 20 );
        add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'sitemap_args' ), 20, 2 );

        add_action( 'admin_post_pcs_save_private_products', array( __CLASS__, 'handle_save' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Storage                                                            */
    /* ---------------------------------------------------------------- */

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'pcs_store_products';
    }

    public static function maybe_create_table() {
        if ( get_option( self::OPT_DB_VERSION ) === self::DB_VERSION ) {
            return;
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table   = self::table();
        $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$table} (
  store_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (store_id,product_id),
  KEY product_id (product_id)
) {$charset};" );
        update_option( self::OPT_DB_VERSION, self::DB_VERSION );
    }

    private static function invalidate() {
        self::$private_ids = null;
        self::$allowed     = array();
        delete_transient( self::TRANSIENT );
    }

    public static function assign( $store_id, $product_id ) {
        global $wpdb;
        $store_id   = absint( $store_id );
        $product_id = absint( $product_id );
        if ( ! $store_id || ! $product_id ) {
            return false;
        }
        $r = $wpdb->query( $wpdb->prepare(
            'INSERT IGNORE INTO ' . self::table() . ' (store_id, product_id, created_at) VALUES (%d, %d, %s)',
            $store_id, $product_id, current_time( 'mysql', true )
        ) );
        self::invalidate();
        return false !== $r;
    }

    public static function unassign( $store_id, $product_id ) {
        global $wpdb;
        $r = $wpdb->delete( self::table(), array( 'store_id' => absint( $store_id ), 'product_id' => absint( $product_id ) ), array( '%d', '%d' ) );
        self::invalidate();
        return false !== $r;
    }

    public static function set_for_store( $store_id, array $product_ids ) {
        $store_id = absint( $store_id );
        if ( ! $store_id ) {
            return false;
        }
        $new = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
        $old = self::get_for_store( $store_id );
        foreach ( array_diff( $old, $new ) as $pid ) {
            self::unassign( $store_id, $pid );
        }
        foreach ( array_diff( $new, $old ) as $pid ) {
            self::assign( $store_id, $pid );
        }
        self::invalidate();
        return true;
    }

    public static function get_for_store( $store_id ) {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            'SELECT product_id FROM ' . self::table() . ' WHERE store_id = %d ORDER BY product_id',
            absint( $store_id )
        ) );
        return array_map( 'intval', (array) $ids );
    }

    public static function get_stores_for_product( $product_id ) {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            'SELECT store_id FROM ' . self::table() . ' WHERE product_id = %d ORDER BY store_id',
            absint( $product_id )
        ) );
        return array_map( 'intval', (array) $ids );
    }

    /** All product ids assigned to at least one existing company. */
    public static function private_product_ids() {
        if ( null !== self::$private_ids ) {
            return self::$private_ids;
        }
        $cached = get_transient( self::TRANSIENT );
        if ( is_array( $cached ) ) {
            self::$private_ids = $cached;
            return $cached;
        }
        global $wpdb;
        $stores = $wpdb->prefix . 'pcs_b2b_stores';
        $ids    = $wpdb->get_col( 'SELECT DISTINCT sp.product_id FROM ' . self::table() . " sp INNER JOIN {$stores} s ON s.id = sp.store_id" );
        $ids    = array_map( 'intval', (array) $ids );
        self::$private_ids = $ids;
        set_transient( self::TRANSIENT, $ids, DAY_IN_SECONDS );
        return $ids;
    }

    /** Private products the user may see (assigned to an approved company where they are owner or member). */
    public static function allowed_ids_for_user( $user_id ) {
        global $wpdb;
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return array();
        }
        if ( isset( self::$allowed[ $user_id ] ) ) {
            return self::$allowed[ $user_id ];
        }
        $stores  = $wpdb->prefix . 'pcs_b2b_stores';
        $members = $wpdb->prefix . 'pcs_b2b_members';
        $ids = $wpdb->get_col( $wpdb->prepare(
            'SELECT DISTINCT sp.product_id FROM ' . self::table() . " sp
             INNER JOIN {$stores} s ON s.id = sp.store_id
             WHERE s.status IN ('active','approved')
               AND ( s.user_id = %d OR EXISTS ( SELECT 1 FROM {$members} m WHERE m.store_id = s.id AND m.user_id = %d ) )",
            $user_id, $user_id
        ) );
        self::$allowed[ $user_id ] = array_map( 'intval', (array) $ids );
        return self::$allowed[ $user_id ];
    }

    private static function is_manager( $user_id ) {
        if ( ! $user_id ) {
            return false;
        }
        return user_can( $user_id, 'manage_woocommerce' ) || user_can( $user_id, 'manage_options' );
    }

    public static function is_visible( $product_id, $user_id = null ) {
        $product_id = absint( $product_id );
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }
        $user_id = absint( $user_id );
        if ( ! in_array( $product_id, self::private_product_ids(), true ) ) {
            return true;
        }
        if ( self::is_manager( $user_id ) ) {
            return true;
        }
        return in_array( $product_id, self::allowed_ids_for_user( $user_id ), true );
    }

    /** Product ids hidden from the current visitor. */
    public static function hidden_ids() {
        $private = self::private_product_ids();
        if ( ! $private ) {
            return array();
        }
        $uid = get_current_user_id();
        if ( self::is_manager( $uid ) ) {
            return array();
        }
        return array_values( array_diff( $private, self::allowed_ids_for_user( $uid ) ) );
    }

    /* ---------------------------------------------------------------- */
    /* Enforcement                                                        */
    /* ---------------------------------------------------------------- */

    public static function filter_queries( $query ) {
        if ( self::$in_query ) {
            return;
        }
        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }
        $pt      = $query->get( 'post_type' );
        $applies = false;
        if ( is_array( $pt ) ) {
            $applies = in_array( 'product', $pt, true ) || in_array( 'any', $pt, true );
        } elseif ( 'product' === $pt || 'any' === $pt ) {
            $applies = true;
        } elseif ( '' === $pt || false === $pt ) {
            $applies = $query->is_search() || $query->is_tax( array( 'product_cat', 'product_tag' ) );
        }
        if ( ! $applies ) {
            return;
        }
        self::$in_query = true;
        $hidden         = self::hidden_ids();
        self::$in_query = false;
        if ( ! $hidden ) {
            return;
        }
        $exclude = $query->get( 'post__not_in' );
        $exclude = is_array( $exclude ) ? $exclude : array();
        $query->set( 'post__not_in', array_values( array_unique( array_merge( array_map( 'intval', $exclude ), $hidden ) ) ) );
    }

    public static function filter_is_visible( $visible, $product_id ) {
        if ( $visible && ! self::is_visible( $product_id ) ) {
            return false;
        }
        return $visible;
    }

    public static function maybe_404() {
        if ( is_admin() ) {
            return;
        }
        $hide = false;
        if ( is_singular( 'product' ) ) {
            $hide = ! self::is_visible( (int) get_queried_object_id() );
        } elseif ( is_page( self::DESIGNER_SLUG ) && isset( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $hide = ! self::is_visible( absint( wp_unslash( $_GET['product_id'] ) ) );
        }
        if ( ! $hide ) {
            return;
        }
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        $tpl = get_404_template();
        if ( $tpl ) {
            include $tpl;
        }
        exit;
    }

    public static function validate_add_to_cart( $passed, $product_id ) {
        if ( $passed && ! self::is_visible( absint( $product_id ) ) ) {
            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( __( 'This product is not available.', 'printcart-b2bstore' ), 'error' );
            }
            return false;
        }
        return $passed;
    }

    public static function filter_id_list( $ids ) {
        if ( ! is_array( $ids ) || ! $ids ) {
            return $ids;
        }
        $hidden = self::hidden_ids();
        return $hidden ? array_values( array_diff( array_map( 'intval', $ids ), $hidden ) ) : $ids;
    }

    public static function sitemap_args( $args, $post_type ) {
        if ( 'product' !== $post_type ) {
            return $args;
        }
        $private = self::private_product_ids();
        if ( $private ) {
            $ex = isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] ) ? $args['post__not_in'] : array();
            $args['post__not_in'] = array_values( array_unique( array_merge( $ex, $private ) ) );
        }
        return $args;
    }

    /* ---------------------------------------------------------------- */
    /* Per-company settings                                               */
    /* ---------------------------------------------------------------- */

    public static function get_store_setting( $store_id, $key, $default = null ) {
        $all = get_option( self::OPT_SETTINGS, array() );
        $sid = absint( $store_id );
        if ( is_array( $all ) && isset( $all[ $sid ] ) && is_array( $all[ $sid ] ) && array_key_exists( $key, $all[ $sid ] ) ) {
            return $all[ $sid ][ $key ];
        }
        return $default;
    }

    public static function set_store_setting( $store_id, $key, $value ) {
        $all = get_option( self::OPT_SETTINGS, array() );
        if ( ! is_array( $all ) ) {
            $all = array();
        }
        $sid = absint( $store_id );
        if ( ! isset( $all[ $sid ] ) || ! is_array( $all[ $sid ] ) ) {
            $all[ $sid ] = array();
        }
        $all[ $sid ][ $key ] = $value;
        return update_option( self::OPT_SETTINGS, $all, false );
    }

    /* ---------------------------------------------------------------- */
    /* Admin UI                                                           */
    /* ---------------------------------------------------------------- */

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'printcart-b2bstore' ), '', array( 'response' => 403 ) );
        }
        $store_id = isset( $_POST['store_id'] ) ? absint( $_POST['store_id'] ) : 0;
        check_admin_referer( 'pcs_save_private_products_' . $store_id );
        $store = $store_id ? PCS_DB::get_store( $store_id ) : null;
        if ( $store ) {
            $ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['product_ids'] ) ) : array();
            self::set_for_store( $store_id, $ids );
            self::set_store_setting( $store_id, 'hide_public_templates', empty( $_POST['hide_public_templates'] ) ? 0 : 1 );
        }
        $ref  = wp_get_referer();
        $back = add_query_arg( 'pcs_private_saved', '1', $ref ? $ref : admin_url( 'admin.php?page=printcart-stores-b2b' ) );
        wp_safe_redirect( $back );
        exit;
    }

    public static function render_admin_box( $store ) {
        if ( ! current_user_can( 'manage_options' ) || ! $store ) {
            return;
        }
        $store_id = (int) $store->id;
        $assigned = self::get_for_store( $store_id );
        $products = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        $hide_tpl = (int) self::get_store_setting( $store_id, 'hide_public_templates', 0 );
        $box_id   = 'pcs-private-products-' . $store_id;
        ?>
      <div class="pcs-card" style="margin-top:16px;" id="<?php echo esc_attr( $box_id ); ?>">
        <div class="pcs-card-head"><span class="pcs-card-title">🔒 Private products</span></div>
        <div class="pcs-card-body" style="font-size:13px;">
          <?php if ( ! empty( $_GET['pcs_private_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
            <div style="background:#dcfce7;color:#065f46;padding:6px 10px;border-radius:6px;margin-bottom:10px;">Saved.</div>
          <?php endif; ?>
          <p style="margin-top:0;color:var(--pcs-text-soft);">Selected products are hidden from everybody except the members of this company (and shop managers).</p>
          <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="pcs_save_private_products">
            <input type="hidden" name="store_id" value="<?php echo $store_id; ?>">
            <?php wp_nonce_field( 'pcs_save_private_products_' . $store_id ); ?>
            <input type="search" class="pcs-input pcs-pp-search" placeholder="Search products…" style="width:100%;max-width:100%;margin-bottom:8px;">
            <div class="pcs-pp-list" style="max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;">
              <?php foreach ( $products as $pid ) :
                  $title = get_the_title( $pid );
                  ?>
                <label class="pcs-pp-item" data-title="<?php echo esc_attr( strtolower( $title ) ); ?>" style="display:block;padding:3px 0;">
                  <input type="checkbox" name="product_ids[]" value="<?php echo (int) $pid; ?>" <?php checked( in_array( (int) $pid, $assigned, true ) ); ?>>
                  <?php echo esc_html( $title ); ?> <span style="color:#9ca3af;">#<?php echo (int) $pid; ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div style="margin:10px 0;">
              <strong>Currently assigned (<?php echo count( $assigned ); ?>):</strong>
              <?php
              if ( $assigned ) {
                  $names = array();
                  foreach ( $assigned as $pid ) {
                      $t       = get_the_title( $pid );
                      $names[] = esc_html( '' !== $t ? $t : '#' . $pid );
                  }
                  echo implode( ', ', $names ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
              } else {
                  echo '—';
              }
              ?>
            </div>
            <label style="display:block;margin-bottom:10px;">
              <input type="checkbox" name="hide_public_templates" value="1" <?php checked( $hide_tpl, 1 ); ?>>
              Hide public templates for this company
            </label>
            <button type="submit" class="pcs-btn pcs-btn-primary">Save private products</button>
          </form>
        </div>
      </div>
      <script>
      (function(){
        var box = document.getElementById(<?php echo wp_json_encode( $box_id ); ?>);
        if (!box) return;
        var s = box.querySelector('.pcs-pp-search');
        s.addEventListener('input', function(){
          var q = s.value.toLowerCase();
          box.querySelectorAll('.pcs-pp-item').forEach(function(l){
            l.style.display = l.getAttribute('data-title').indexOf(q) === -1 ? 'none' : 'block';
          });
        });
      })();
      </script>
        <?php
    }
}
