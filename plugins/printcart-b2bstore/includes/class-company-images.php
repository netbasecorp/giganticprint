<?php
/**
 * Image Zone: a private image library per approved B2B company.
 *
 * Files live in uploads/gp-imagezone/<store_id>/ (denied by .htaccess, never served directly);
 * they are only streamed through the gp_iz_file endpoint after a membership check.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Company_Images {

    const DB_VERSION      = '1';
    const OPT_DB_VERSION  = 'pcs_b4_iz_db_version';
    const NONCE_ACTION    = 'pcs_ajax';
    const MAX_FILE_BYTES  = 104857600;   // 100 MB
    const MAX_STORE_BYTES = 2147483648;  // 2 GB per company
    const THUMB_MAX       = 320;
    const RATE_MAX        = 60;          // uploads per user ...
    const RATE_WINDOW     = 600;         // ... per 10 minutes
    const FALLBACK_DPI    = 150;

    public static function init() {
        self::maybe_create_table();

        /* logged-in only: no wp_ajax_nopriv_ handlers */
        add_action( 'wp_ajax_gp_iz_list',   array( __CLASS__, 'ajax_list' ) );
        add_action( 'wp_ajax_gp_iz_upload', array( __CLASS__, 'ajax_upload' ) );
        add_action( 'wp_ajax_gp_iz_file',   array( __CLASS__, 'ajax_file' ) );
        add_action( 'wp_ajax_gp_iz_delete', array( __CLASS__, 'ajax_delete' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Storage                                                            */
    /* ---------------------------------------------------------------- */

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'pcs_company_images';
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
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  store_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  name varchar(190) NOT NULL DEFAULT '',
  ext varchar(10) NOT NULL DEFAULT '',
  mime varchar(60) NOT NULL DEFAULT '',
  bytes bigint(20) unsigned NOT NULL DEFAULT 0,
  width int(11) unsigned NOT NULL DEFAULT 0,
  height int(11) unsigned NOT NULL DEFAULT 0,
  dpi int(11) unsigned DEFAULT NULL,
  file varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY store_id (store_id)
) {$charset};" );
        update_option( self::OPT_DB_VERSION, self::DB_VERSION );
    }

    public static function base_dir() {
        $up = wp_upload_dir();
        return rtrim( $up['basedir'], '/\\' ) . '/gp-imagezone';
    }

    public static function store_dir( $store_id ) {
        return self::base_dir() . '/' . absint( $store_id );
    }

    /** Create the store folder with the deny rules; returns the folder path or '' on failure. */
    private static function ensure_dir( $store_id ) {
        $base = self::base_dir();
        $dir  = self::store_dir( $store_id );
        if ( ! wp_mkdir_p( $dir ) ) {
            return '';
        }
        foreach ( array( $base, $dir ) as $d ) {
            if ( ! file_exists( $d . '/.htaccess' ) ) {
                @file_put_contents( $d . '/.htaccess', "# Image Zone: private files, served only by PHP\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\nDeny from all\nOptions -Indexes\n" );
            }
            if ( ! file_exists( $d . '/index.php' ) ) {
                @file_put_contents( $d . '/index.php', "<?php\n// Silence is golden.\n" );
            }
        }
        return $dir;
    }

    /* ---------------------------------------------------------------- */
    /* Access control                                                     */
    /* ---------------------------------------------------------------- */

    /** Ids of the approved companies the user owns or is an active member of (ascending id). */
    public static function user_store_ids( $user_id ) {
        global $wpdb;
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return array();
        }
        $stores  = $wpdb->prefix . 'pcs_b2b_stores';
        $members = $wpdb->prefix . 'pcs_b2b_members';
        $rows    = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT s.id, s.status
             FROM {$stores} s
             LEFT JOIN {$members} m ON m.store_id = s.id AND m.user_id = %d AND m.status = 'active'
             WHERE s.user_id = %d OR m.id IS NOT NULL
             ORDER BY s.id ASC",
            $user_id, $user_id
        ) );
        $ids = array();
        foreach ( (array) $rows as $r ) {
            if ( PCS_DB::store_is_approved( $r->status ) ) {
                $ids[] = (int) $r->id;
            }
        }
        return $ids;
    }

    public static function can_access( $store_id, $user_id ) {
        $store_id = absint( $store_id );
        $user_id  = absint( $user_id );
        if ( ! $store_id || ! $user_id ) {
            return false;
        }
        if ( in_array( $store_id, self::user_store_ids( $user_id ), true ) ) {
            return true;
        }
        return user_can( $user_id, 'manage_options' ) && (bool) PCS_DB::get_store_by_id( $store_id );
    }

    /** Uploader, company owner, company 'admin' member or site administrator. */
    public static function can_delete( $row, $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $row || ! $user_id || ! self::can_access( $row->store_id, $user_id ) ) {
            return false;
        }
        if ( (int) $row->user_id === $user_id || user_can( $user_id, 'manage_options' ) ) {
            return true;
        }
        $store = PCS_DB::get_store_by_id( $row->store_id );
        if ( $store && (int) $store->user_id === $user_id ) {
            return true;
        }
        $m = PCS_DB::get_member_by_user( $row->store_id, $user_id );
        return $m && 'admin' === $m->role;
    }

    /** Requested company if allowed, otherwise the user's first approved company (0 = none). */
    public static function resolve_store( $user_id, $requested = 0 ) {
        $requested = absint( $requested );
        if ( $requested && self::can_access( $requested, $user_id ) ) {
            return $requested;
        }
        if ( $requested ) {
            return 0;
        }
        $ids = self::user_store_ids( $user_id );
        return $ids ? $ids[0] : 0;
    }

    /* ---------------------------------------------------------------- */
    /* File inspection                                                    */
    /* ---------------------------------------------------------------- */

    public static function allowed_exts() {
        return array( 'png', 'jpg', 'jpeg', 'tif', 'tiff', 'pdf', 'ai', 'eps' );
    }

    private static function mime_for_ext( $ext ) {
        $map = array(
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'tif'  => 'image/tiff',
            'tiff' => 'image/tiff',
            'pdf'  => 'application/pdf',
            'ai'   => 'application/illustrator',
            'eps'  => 'application/postscript',
        );
        return isset( $map[ $ext ] ) ? $map[ $ext ] : 'application/octet-stream';
    }

    private static function is_vector_ext( $ext ) {
        return in_array( $ext, array( 'pdf', 'ai', 'eps' ), true );
    }

    /** Magic bytes must match the extension. */
    public static function magic_ok( $path, $ext ) {
        $fh = @fopen( $path, 'rb' );
        if ( ! $fh ) {
            return false;
        }
        $head = (string) fread( $fh, 1024 );
        fclose( $fh );
        if ( strlen( $head ) < 4 ) {
            return false;
        }
        $h4 = substr( $head, 0, 4 );
        switch ( $ext ) {
            case 'png':
                return 0 === strncmp( $head, "\x89PNG\r\n\x1a\n", 8 );
            case 'jpg':
            case 'jpeg':
                return 0 === strncmp( $head, "\xFF\xD8\xFF", 3 );
            case 'tif':
            case 'tiff':
                return "II*\x00" === $h4 || "MM\x00*" === $h4 || "II+\x00" === $h4 || "MM\x00+" === $h4;
            case 'pdf':
                return false !== strpos( $head, '%PDF-' );
            case 'ai':
                return false !== strpos( $head, '%PDF-' ) || 0 === strncmp( $head, '%!PS', 4 );
            case 'eps':
                return 0 === strncmp( $head, '%!PS', 4 ) || "\xC5\xD0\xD3\xC6" === $h4 || false !== strpos( $head, '%PDF-' );
        }
        return false;
    }

    /**
     * Pixel size / dpi (null when unknown) and the page-1 thumbnail written to $thumb_path (when given).
     * Never throws; returns array( width, height, dpi|null, thumb_ok ).
     */
    private static function inspect( $path, $ext, $thumb_path ) {
        $w = 0; $h = 0; $dpi = null; $thumb_ok = false;
        $vector = self::is_vector_ext( $ext );

        if ( class_exists( 'Imagick' ) ) {
            try {
                $im = new Imagick();
                if ( $vector ) {
                    $im->setResolution( 72, 72 );
                }
                $im->readImage( $path . '[0]' );
                $w = (int) $im->getImageWidth();
                $h = (int) $im->getImageHeight();

                if ( ! $vector ) {
                    $res   = $im->getImageResolution();
                    $units = (int) $im->getImageUnits();
                    $x     = isset( $res['x'] ) ? (float) $res['x'] : 0.0;
                    if ( 2 === $units ) {            // pixels per centimeter
                        $x *= 2.54;
                    }
                    $x = (int) round( $x );
                    if ( $units > 0 && $x >= 10 && $x <= 4800 ) {
                        $dpi = $x;
                    }
                }

                if ( $thumb_path ) {
                    $im->setImageBackgroundColor( new ImagickPixel( 'white' ) );
                    if ( $im->getImageAlphaChannel() ) {
                        $im->setImageAlphaChannel( Imagick::ALPHACHANNEL_REMOVE );
                    }
                    $im = $im->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
                    if ( Imagick::COLORSPACE_CMYK === $im->getImageColorspace() ) {
                        $im->transformImageColorspace( Imagick::COLORSPACE_SRGB );
                    }
                    $im->thumbnailImage( self::THUMB_MAX, self::THUMB_MAX, true );
                    $im->setImageFormat( 'jpeg' );
                    $im->setImageCompressionQuality( 80 );
                    $im->stripImage();
                    $thumb_ok = (bool) $im->writeImage( $thumb_path );
                }
                $im->clear();
                $im->destroy();
            } catch ( Throwable $e ) {
                /* fall through to the getimagesize() fallback below */
            }
        }

        if ( ( ! $w || ! $h ) && ! $vector ) {
            $gi = @getimagesize( $path );
            if ( $gi ) {
                $w = (int) $gi[0];
                $h = (int) $gi[1];
            }
        }
        return array( $w, $h, $dpi, $thumb_ok );
    }

    /* ---------------------------------------------------------------- */
    /* Core: store a file (testable without HTTP)                         */
    /* ---------------------------------------------------------------- */

    /**
     * Copy $path into the company library. $name is the original file name.
     * Does not check upload/membership/rate (callers do); checks extension, magic bytes and the caps.
     *
     * @return array|WP_Error row as returned by item_to_array() ('id' set)
     */
    public static function store_file( $path, $name, $store_id, $user_id ) {
        global $wpdb;
        $store_id = absint( $store_id );
        $user_id  = absint( $user_id );

        if ( ! $store_id || ! is_file( $path ) ) {
            return new WP_Error( 'gp_iz_bad', 'No file received.' );
        }
        $name = wp_basename( sanitize_file_name( wp_unslash( (string) $name ) ) );
        $ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::allowed_exts(), true ) ) {
            return new WP_Error( 'gp_iz_type', 'File type not allowed. Use PNG, JPG, TIF, PDF, AI or EPS.' );
        }
        $bytes = (int) filesize( $path );
        if ( $bytes <= 0 ) {
            return new WP_Error( 'gp_iz_empty', 'The file is empty.' );
        }
        if ( $bytes > self::MAX_FILE_BYTES ) {
            return new WP_Error( 'gp_iz_size', 'The file is larger than 100 MB.' );
        }
        if ( ! self::magic_ok( $path, $ext ) ) {
            return new WP_Error( 'gp_iz_magic', 'The file content does not match its type.' );
        }
        $used = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(bytes),0) FROM ' . self::table() . ' WHERE store_id = %d', $store_id ) );
        if ( $used + $bytes > self::MAX_STORE_BYTES ) {
            return new WP_Error( 'gp_iz_quota', 'Your company Image Zone is full (2 GB). Delete some images first.' );
        }

        $dir = self::ensure_dir( $store_id );
        if ( '' === $dir ) {
            return new WP_Error( 'gp_iz_dir', 'Could not create the storage folder.' );
        }
        $stored = bin2hex( random_bytes( 16 ) ) . '.' . $ext;
        $dest   = $dir . '/' . $stored;
        if ( ! @copy( $path, $dest ) ) {
            return new WP_Error( 'gp_iz_write', 'Could not save the file.' );
        }
        @chmod( $dest, 0640 );

        $thumb = $dest . '-t.jpg';
        list( $w, $h, $dpi, $thumb_ok ) = self::inspect( $dest, $ext, $thumb );
        if ( $thumb_ok ) {
            @chmod( $thumb, 0640 );
        } elseif ( file_exists( $thumb ) ) {
            @unlink( $thumb );
        }

        $ok = $wpdb->insert(
            self::table(),
            array(
                'store_id'   => $store_id,
                'user_id'    => $user_id,
                'name'       => mb_substr( $name, 0, 190 ),
                'ext'        => $ext,
                'mime'       => self::mime_for_ext( $ext ),
                'bytes'      => $bytes,
                'width'      => $w,
                'height'     => $h,
                'dpi'        => $dpi,
                'file'       => $stored,
                'created_at' => gmdate( 'Y-m-d H:i:s' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', $dpi === null ? '%s' : '%d', '%s', '%s' )
        );
        if ( ! $ok ) {
            @unlink( $dest );
            @unlink( $thumb );
            return new WP_Error( 'gp_iz_db', 'Could not save the image record.' );
        }
        return self::item_to_array( self::get( (int) $wpdb->insert_id ), $user_id );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', absint( $id ) ) );
    }

    /** Removes the row and both files. */
    public static function delete_image( $row ) {
        global $wpdb;
        if ( ! $row ) {
            return false;
        }
        $path = self::file_path( $row );
        if ( $path ) {
            @unlink( $path );
            @unlink( $path . '-t.jpg' );
        }
        return false !== $wpdb->delete( self::table(), array( 'id' => (int) $row->id ), array( '%d' ) );
    }

    public static function file_path( $row ) {
        if ( ! $row || ! preg_match( '/^[a-f0-9]{32}\.[a-z]{2,4}$/', (string) $row->file ) ) {
            return '';
        }
        return self::store_dir( $row->store_id ) . '/' . $row->file;
    }

    /* ---------------------------------------------------------------- */
    /* Presentation                                                       */
    /* ---------------------------------------------------------------- */

    private static function fmt_inches( $px, $dpi ) {
        $v = round( $px / $dpi, 1 );
        return rtrim( rtrim( number_format( $v, 1, '.', '' ), '0' ), '.' );
    }

    public static function item_to_array( $row, $viewer_id = 0 ) {
        if ( ! $row ) {
            return array();
        }
        $dpi      = null === $row->dpi ? 0 : (int) $row->dpi;
        $assumed  = $dpi <= 0;
        $use_dpi  = $assumed ? self::FALLBACK_DPI : $dpi;
        $w        = (int) $row->width;
        $h        = (int) $row->height;
        $print_w  = $w ? round( $w / $use_dpi, 1 ) : 0;
        $print_h  = $h ? round( $h / $use_dpi, 1 ) : 0;
        $print    = ( $w && $h ) ? self::fmt_inches( $w, $use_dpi ) . ' x ' . self::fmt_inches( $h, $use_dpi ) . ' in at ' . $use_dpi . ' dpi' : '';
        $user     = get_userdata( (int) $row->user_id );
        $url      = admin_url( 'admin-ajax.php' ) . '?action=gp_iz_file&id=' . (int) $row->id;

        return array(
            'id'          => (int) $row->id,
            'store_id'    => (int) $row->store_id,
            'name'        => (string) $row->name,
            'ext'         => (string) $row->ext,
            'mime'        => (string) $row->mime,
            'width'       => $w,
            'height'      => $h,
            'dpi'         => $assumed ? null : $dpi,
            'dpi_assumed' => $assumed,
            'bytes'       => (int) $row->bytes,
            'created'     => mysql2date( 'M j, Y', get_date_from_gmt( $row->created_at ) ),
            'uploader'    => $user ? $user->display_name : '',
            'url'         => $url,
            'thumb'       => $url . '&thumb=1',
            'print_w'     => $print_w,
            'print_h'     => $print_h,
            'print_label' => $print,
            'can_delete'  => $viewer_id ? self::can_delete( $row, $viewer_id ) : false,
        );
    }

    public static function list_for_store( $store_id, $viewer_id = 0 ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE store_id = %d ORDER BY id DESC LIMIT 500', absint( $store_id ) ) );
        $out  = array();
        foreach ( (array) $rows as $r ) {
            $out[] = self::item_to_array( $r, $viewer_id );
        }
        return $out;
    }

    /** Remove every image (rows + folder) of a company, e.g. when the company is deleted. */
    public static function purge_store( $store_id ) {
        global $wpdb;
        foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE store_id = %d', absint( $store_id ) ) ) as $r ) {
            self::delete_image( $r );
        }
    }

    /* ---------------------------------------------------------------- */
    /* AJAX                                                               */
    /* ---------------------------------------------------------------- */

    private static function check_nonce() {
        $nonce = isset( $_REQUEST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_nonce'] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Please log in.' ), 403 );
        }
    }

    public static function ajax_list() {
        self::check_nonce();
        $uid = get_current_user_id();
        $sid = self::resolve_store( $uid, isset( $_REQUEST['store_id'] ) ? absint( $_REQUEST['store_id'] ) : 0 );
        if ( ! $sid ) {
            wp_send_json_error( array( 'message' => 'No company Image Zone available for your account.' ), 403 );
        }
        wp_send_json_success( array(
            'store_id' => $sid,
            'items'    => self::list_for_store( $sid, $uid ),
        ) );
    }

    public static function ajax_upload() {
        self::check_nonce();
        if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
            wp_send_json_error( array( 'message' => 'POST required.' ), 405 );
        }
        $uid = get_current_user_id();
        $sid = self::resolve_store( $uid, isset( $_POST['store_id'] ) ? absint( $_POST['store_id'] ) : 0 );
        if ( ! $sid ) {
            wp_send_json_error( array( 'message' => 'No company Image Zone available for your account.' ), 403 );
        }

        $key   = 'gp_iz_rl_' . $uid;
        $count = (int) get_transient( $key );
        if ( $count >= self::RATE_MAX ) {
            wp_send_json_error( array( 'message' => 'Too many uploads. Please wait a few minutes.' ), 429 );
        }

        if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) || is_array( $_FILES['file']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => 'No file received (is it larger than the server limit?).' ), 400 );
        }
        $f = $_FILES['file'];
        if ( (int) $f['error'] !== UPLOAD_ERR_OK ) {
            $msg = in_array( (int) $f['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
                ? 'The file is larger than the server limit.' : 'Upload failed. Please try again.';
            wp_send_json_error( array( 'message' => $msg ), 400 );
        }
        if ( ! is_uploaded_file( $f['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid upload.' ), 400 );
        }

        set_transient( $key, $count + 1, self::RATE_WINDOW );
        $res = self::store_file( $f['tmp_name'], $f['name'], $sid, $uid );
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( array( 'message' => $res->get_error_message() ), 400 );
        }
        wp_send_json_success( array( 'item' => $res ) );
    }

    /** Streams the original or the thumbnail after the membership check (GET; cookie auth, no state change). */
    public static function ajax_file() {
        if ( ! is_user_logged_in() ) {
            status_header( 403 );
            exit;
        }
        $row = self::get( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
        if ( ! $row || ! self::can_access( $row->store_id, get_current_user_id() ) ) {
            status_header( 404 );
            exit;
        }
        $thumb = ! empty( $_GET['thumb'] );
        $path  = self::file_path( $row );
        if ( $thumb ) {
            $path  = $path ? $path . '-t.jpg' : '';
            $mime  = 'image/jpeg';
            $fname = pathinfo( $row->name, PATHINFO_FILENAME ) . '-thumb.jpg';
        } else {
            $mime  = $row->mime ? $row->mime : 'application/octet-stream';
            $fname = $row->name;
        }

        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }
        nocache_headers();
        header( 'Cache-Control: private, max-age=300' );
        header( 'Pragma:' );
        header( 'Expires:' );
        header( 'Vary: Cookie' );
        header( 'X-Content-Type-Options: nosniff' );

        if ( ! $path || ! is_file( $path ) ) {
            if ( $thumb ) {
                /* no thumbnail (e.g. vector file without a renderer): neutral placeholder */
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="213" viewBox="0 0 320 213"><rect width="320" height="213" fill="#f3f4f6"/><text x="160" y="116" text-anchor="middle" font-family="Arial,sans-serif" font-size="28" font-weight="700" fill="#6b7280">'
                     . esc_html( strtoupper( $row->ext ) ) . '</text></svg>';
                header( 'Content-Type: image/svg+xml' );
                header( 'Content-Length: ' . strlen( $svg ) );
                echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput
                exit;
            }
            status_header( 404 );
            exit;
        }

        $ascii = preg_replace( '/[^A-Za-z0-9._-]+/', '_', $fname );
        header( 'Content-Type: ' . $mime );
        header( 'Content-Length: ' . filesize( $path ) );
        header( 'Content-Disposition: inline; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode( $fname ) );
        readfile( $path );
        exit;
    }

    public static function ajax_delete() {
        self::check_nonce();
        if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
            wp_send_json_error( array( 'message' => 'POST required.' ), 405 );
        }
        $row = self::get( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
        if ( ! $row || ! self::can_delete( $row, get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => 'You cannot delete this image.' ), 403 );
        }
        if ( ! self::delete_image( $row ) ) {
            wp_send_json_error( array( 'message' => 'Could not delete the image.' ), 500 );
        }
        wp_send_json_success( array( 'id' => (int) $row->id ) );
    }
}
