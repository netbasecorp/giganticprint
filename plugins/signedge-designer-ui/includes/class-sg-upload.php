<?php
/**
 * Server side of the bigger / more artwork types designer upload.
 *
 * PROTOCOL (all requests: POST multipart to admin-ajax.php, same nonce as the core
 * upload = wp_create_nonce('save-design'), also exposed as window.SG_UPLOAD.nonce).
 * Errors are always HTTP 4xx/5xx with JSON {"success":false,"data":{"message":"..."}}.
 *
 * 1) Conversion: action=gp_convert_upload, fields: file (TIFF/TIF/EPS, <= 100 MB), nonce.
 *    Success: HTTP 200, body = PNG bytes, Content-Type image/png,
 *    header X-GP-Filename: <rawurlencoded original basename without extension>.png
 *
 * 2) Chunked upload: action=gp_chunk_upload, field step:
 *    step=init    fields: name, size (bytes, <= 200 MB), nonce
 *                 -> {"success":true,"data":{"id":"<24 hex>","chunk":8388608}}
 *    step=put     fields: id, index (0-based int), nonce, file (the chunk, <= 16 MB)
 *                 -> {"success":true,"data":{"index":<int>}}   (re-sending a chunk overwrites it)
 *    step=finish  fields: id, nonce, expected (number of chunks)
 *                 -> exactly the JSON of the core handler nbdesigner_customer_upload
 *                    ({flag, src, mes, ...}); tif/tiff/eps are converted to PNG first,
 *                    ai is treated as pdf. Missing chunks: HTTP 409 with
 *                    data.missing = [indexes] (dir kept, resend and finish again).
 *    Allowed extensions: jpg jpeg png pdf tif tiff eps ai.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SG_Upload {

    const MAX_BYTES        = 209715200; // 200 MB
    const CONVERT_MAX      = 104857600; // 100 MB
    const CHUNK_BYTES      = 8388608;   // 8 MB
    const CHUNK_MAX_BYTES  = 16777216;  // 16 MB
    const MAX_INDEX        = 400;
    const DIR_TTL          = 7200;
    const CRON_HOOK        = 'gp_chunk_cleanup';

    private static $cleanup = array();
    private static $shutdown_registered = false;

    public static function init() {
        add_action( 'wp_ajax_gp_convert_upload', array( __CLASS__, 'ajax_convert' ) );
        add_action( 'wp_ajax_nopriv_gp_convert_upload', array( __CLASS__, 'ajax_convert' ) );
        add_action( 'wp_ajax_gp_chunk_upload', array( __CLASS__, 'ajax_chunk' ) );
        add_action( 'wp_ajax_nopriv_gp_chunk_upload', array( __CLASS__, 'ajax_chunk' ) );
        add_action( 'nbd_extra_js', array( __CLASS__, 'print_config' ), 3 );
        add_action( self::CRON_HOOK, array( __CLASS__, 'cleanup_old' ) );
        add_action( 'init', array( __CLASS__, 'schedule' ) );
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + 300, 'hourly', self::CRON_HOOK );
        }
    }

    public static function print_config() {
        $cfg = array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'save-design' ),
            'maxMb'       => 200,
            'directMaxMb' => 90,
            'chunkBytes'  => self::CHUNK_BYTES,
            'convertExt'  => array( 'tif', 'tiff', 'eps' ),
            'pdfAliasExt' => array( 'ai' ),
        );
        echo '<script id="sg-upload-config">window.SG_UPLOAD = '
            . wp_json_encode( $cfg, JSON_HEX_TAG | JSON_HEX_AMP ) . ';</script>' . "\n";
    }

    /* ---------------------------------------------------------------- helpers */

    private static function check_nonce() {
        $nonce = isset( $_POST['nonce'] ) ? (string) $_POST['nonce'] : '';
        if ( $nonce === '' || ! wp_verify_nonce( $nonce, 'save-design' ) ) {
            throw new RuntimeException( 'Security check failed. Please reload the page.', 403 );
        }
    }

    private static function client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $cf = (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
            if ( filter_var( $cf, FILTER_VALIDATE_IP ) ) {
                $ip = $cf;
            }
        }
        return $ip;
    }

    private static function rate_limit( $bucket, $max, $window = 600 ) {
        $key   = 'gp_rl_' . $bucket . '_' . md5( self::client_ip() );
        $count = (int) get_transient( $key );
        if ( $count >= $max ) {
            throw new RuntimeException( 'Too many requests. Please try again in a few minutes.', 429 );
        }
        set_transient( $key, $count + 1, $window );
    }

    private static function clean_name( $name ) {
        $name = wp_basename( str_replace( '\\', '/', (string) $name ) );
        $name = preg_replace( '/[\x00-\x1F\x7F]+/', '', $name );
        $name = trim( (string) $name );
        if ( strlen( $name ) > 150 ) {
            $name = substr( $name, -150 );
        }
        return $name;
    }

    private static function split_name( $name ) {
        $ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
        $base = pathinfo( $name, PATHINFO_FILENAME );
        if ( $base === '' ) {
            $base = 'upload';
        }
        return array( $base, $ext );
    }

    private static function register_cleanup( $path ) {
        self::$cleanup[] = $path;
        if ( ! self::$shutdown_registered ) {
            self::$shutdown_registered = true;
            register_shutdown_function( array( __CLASS__, 'run_cleanup' ) );
        }
    }

    public static function run_cleanup() {
        foreach ( self::$cleanup as $p ) {
            self::rrmdir( $p );
        }
        self::$cleanup = array();
    }

    private static function rrmdir( $path ) {
        if ( is_link( $path ) || is_file( $path ) ) {
            @unlink( $path );
            return;
        }
        if ( ! is_dir( $path ) ) {
            return;
        }
        $items = @scandir( $path );
        if ( is_array( $items ) ) {
            foreach ( $items as $it ) {
                if ( $it === '.' || $it === '..' ) {
                    continue;
                }
                self::rrmdir( $path . '/' . $it );
            }
        }
        @rmdir( $path );
    }

    private static function base_dir() {
        $up   = wp_upload_dir();
        $base = rtrim( $up['basedir'], '/\\' ) . '/gp-chunks';
        if ( ! is_dir( $base ) ) {
            wp_mkdir_p( $base );
        }
        if ( ! is_dir( $base ) ) {
            throw new RuntimeException( 'Server storage is not available.', 500 );
        }
        if ( ! file_exists( $base . '/index.php' ) ) {
            @file_put_contents( $base . '/index.php', "<?php\n// Silence is golden.\n" );
        }
        if ( ! file_exists( $base . '/.htaccess' ) ) {
            @file_put_contents( $base . '/.htaccess', "Deny from all\n" );
        }
        return $base;
    }

    private static function valid_id( $id ) {
        return is_string( $id ) && preg_match( '/^[a-f0-9]{24}$/', $id ) === 1;
    }

    private static function job_dir( $id ) {
        if ( ! self::valid_id( $id ) ) {
            throw new RuntimeException( 'Invalid upload id.', 400 );
        }
        $dir = self::base_dir() . '/' . $id;
        if ( ! is_dir( $dir ) ) {
            throw new RuntimeException( 'Upload session not found or expired.', 404 );
        }
        return $dir;
    }

    private static function read_meta( $dir ) {
        $raw  = @file_get_contents( $dir . '/meta.json' );
        $meta = is_string( $raw ) ? json_decode( $raw, true ) : null;
        if ( ! is_array( $meta ) || ! isset( $meta['name'], $meta['size'], $meta['ext'] ) ) {
            throw new RuntimeException( 'Upload session is damaged.', 500 );
        }
        return $meta;
    }

    public static function cleanup_old() {
        $up   = wp_upload_dir();
        $base = rtrim( $up['basedir'], '/\\' ) . '/gp-chunks';
        if ( ! is_dir( $base ) ) {
            return;
        }
        $items = @scandir( $base );
        if ( ! is_array( $items ) ) {
            return;
        }
        $limit = time() - self::DIR_TTL;
        foreach ( $items as $it ) {
            if ( ! self::valid_id( $it ) ) {
                continue;
            }
            $p = $base . '/' . $it;
            if ( is_dir( $p ) && ! is_link( $p ) && (int) @filemtime( $p ) < $limit ) {
                self::rrmdir( $p );
            }
        }
    }

    private static function read_head( $path, $len = 16 ) {
        $h = @fopen( $path, 'rb' );
        if ( ! $h ) {
            return '';
        }
        $data = (string) fread( $h, $len );
        fclose( $h );
        return $data;
    }

    private static function is_tiff_magic( $head ) {
        $four = substr( $head, 0, 4 );
        return $four === "II*\0" || $four === "MM\0*";
    }

    private static function is_eps_magic( $head ) {
        return strncmp( $head, '%!PS-Adobe', 10 ) === 0 || substr( $head, 0, 4 ) === "\xC5\xD0\xD3\xC6";
    }

    private static function magic_ok( $ext, $head ) {
        switch ( $ext ) {
            case 'png':
                return strncmp( $head, "\x89PNG", 4 ) === 0;
            case 'jpg':
            case 'jpeg':
                return strncmp( $head, "\xFF\xD8\xFF", 3 ) === 0;
            case 'pdf':
            case 'ai':
                return strncmp( $head, '%PDF', 4 ) === 0;
            case 'tif':
            case 'tiff':
                return self::is_tiff_magic( $head );
            case 'eps':
                return self::is_eps_magic( $head );
        }
        return false;
    }

    /** Refuse PostScript that uses file / pipe operators (ghostscript 9.27 without -dSAFER). */
    private static function eps_is_safe( $path ) {
        $h = @fopen( $path, 'rb' );
        if ( ! $h ) {
            return false;
        }
        $needles = array( 'deletefile', 'renamefile', '%pipe', 'filenameforall', '%os%', '%disk' );
        $tail    = '';
        $ok      = true;
        while ( ! feof( $h ) ) {
            $buf = fread( $h, 1048576 );
            if ( $buf === false || $buf === '' ) {
                break;
            }
            $chunk = $tail . $buf;
            foreach ( $needles as $n ) {
                if ( stripos( $chunk, $n ) !== false ) {
                    $ok = false;
                    break 2;
                }
            }
            if ( preg_match( '/\(\s*[rwa]b?\+?\s*\)\s*file\b/', $chunk ) ) {
                $ok = false;
                break;
            }
            $tail = substr( $chunk, -128 );
        }
        fclose( $h );
        return $ok;
    }

    /* ------------------------------------------------------------- conversion */

    /**
     * Convert a TIFF / EPS file to PNG with Imagick. Throws RuntimeException (safe message).
     */
    private static function convert_to_png( $src, $ext, $dest ) {
        if ( ! class_exists( 'Imagick' ) ) {
            throw new RuntimeException( 'Image conversion is not available on this server.', 501 );
        }
        $is_eps = ( $ext === 'eps' );
        if ( ! self::magic_ok( $ext, self::read_head( $src ) ) ) {
            throw new RuntimeException( 'The file content does not match its type.', 415 );
        }
        if ( $is_eps && ! self::eps_is_safe( $src ) ) {
            throw new RuntimeException( 'This EPS file cannot be processed. Please export it as PDF, TIFF or PNG.', 422 );
        }

        @set_time_limit( 180 );
        try {
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_MEMORY, 512 * 1048576 );
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_MAP, 1024 * 1048576 );
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_TIME, 120 );
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_DISK, 2048 * 1048576 );
        } catch ( \Throwable $e ) {
            // limits are best effort
        }

        $im = null;
        try {
            if ( $is_eps ) {
                $im = self::read_eps( $src );
            } else {
                $im = self::read_tiff( $src );
            }

            if ( $im->getImageColorspace() === Imagick::COLORSPACE_CMYK ) {
                $im->transformImageColorspace( Imagick::COLORSPACE_SRGB );
            }
            $im->setImageFormat( 'png' );
            $im->setImageDepth( 8 );
            if ( ! $im->writeImage( 'PNG:' . $dest ) ) {
                throw new RuntimeException( 'Could not convert the file.', 422 );
            }
            $im->clear();
        } catch ( RuntimeException $e ) {
            if ( $im instanceof Imagick ) {
                $im->clear();
            }
            throw $e;
        } catch ( \Throwable $e ) {
            if ( $im instanceof Imagick ) {
                $im->clear();
            }
            error_log( 'gp_upload convert failed: ' . $e->getMessage() );
            throw new RuntimeException( 'Could not convert the file. It may be damaged or unsupported.', 422 );
        }
        if ( ! is_file( $dest ) || filesize( $dest ) < 8 ) {
            throw new RuntimeException( 'Could not convert the file.', 422 );
        }
    }

    private static function check_dims( $w, $h ) {
        if ( $w < 1 || $h < 1 || $w > 30000 || $h > 30000 || ( $w * $h ) > 100000000 ) {
            throw new RuntimeException( 'The image dimensions are too large (max 100 megapixels).', 422 );
        }
    }

    private static function read_tiff( $src ) {
        $ping = new Imagick();
        $ping->pingImage( 'TIFF:' . $src . '[0]' );
        $w = $ping->getImageWidth();
        $h = $ping->getImageHeight();
        $ping->clear();
        self::check_dims( $w, $h );

        $im = new Imagick();
        $im->readImage( 'TIFF:' . $src . '[0]' );
        $im->setIteratorIndex( 0 );

        $res   = $im->getImageResolution();
        $units = $im->getImageUnits();
        $x = isset( $res['x'] ) ? (float) $res['x'] : 0.0;
        $y = isset( $res['y'] ) ? (float) $res['y'] : 0.0;
        if ( $units === Imagick::RESOLUTION_PIXELSPERCENTIMETER ) {
            $x *= 2.54;
            $y *= 2.54;
        }
        if ( $x < 1 || $y < 1 ) {
            $x = 150.0;
            $y = 150.0;
        }
        $im->setImageUnits( Imagick::RESOLUTION_PIXELSPERINCH );
        $im->setImageResolution( round( $x, 2 ), round( $y, 2 ) );
        return $im;
    }

    private static function read_eps( $src ) {
        $probe = new Imagick();
        $probe->setResolution( 72, 72 );
        $probe->readImage( 'EPS:' . $src . '[0]' );
        $w = $probe->getImageWidth();
        $h = $probe->getImageHeight();
        $probe->clear();
        if ( $w < 1 || $h < 1 ) {
            throw new RuntimeException( 'Could not read the EPS page size.', 422 );
        }
        $density = (int) floor( 10000 * 72 / max( $w, $h ) );
        if ( $density > 300 ) {
            $density = 300;
        }
        if ( $density < 1 ) {
            $density = 1;
        }
        self::check_dims( (int) ceil( $w * $density / 72 ), (int) ceil( $h * $density / 72 ) );

        $im = new Imagick();
        $im->setResolution( $density, $density );
        $im->setBackgroundColor( new ImagickPixel( 'white' ) );
        $im->readImage( 'EPS:' . $src . '[0]' );
        $im->setIteratorIndex( 0 );
        $im->setImageBackgroundColor( new ImagickPixel( 'white' ) );
        $flat = $im->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
        $im->clear();
        $flat->setImageUnits( Imagick::RESOLUTION_PIXELSPERINCH );
        $flat->setImageResolution( $density, $density );
        return $flat;
    }

    private static function temp_file( $suffix ) {
        $p = rtrim( get_temp_dir(), '/\\' ) . '/gp-conv-' . bin2hex( random_bytes( 8 ) ) . $suffix;
        self::register_cleanup( $p );
        return $p;
    }

    public static function ajax_convert() {
        try {
            self::check_nonce();
            self::rate_limit( 'conv', 20 );

            if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) || is_array( $_FILES['file']['tmp_name'] ) ) {
                throw new RuntimeException( 'No file received.', 400 );
            }
            $f   = $_FILES['file'];
            $err = isset( $f['error'] ) ? (int) $f['error'] : UPLOAD_ERR_NO_FILE;
            if ( $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ) {
                throw new RuntimeException( 'The file is too large (max 100 MB).', 413 );
            }
            if ( $err !== UPLOAD_ERR_OK || ! is_uploaded_file( $f['tmp_name'] ) ) {
                throw new RuntimeException( 'The upload failed. Please try again.', 400 );
            }
            $size = (int) filesize( $f['tmp_name'] );
            if ( $size > self::CONVERT_MAX ) {
                throw new RuntimeException( 'The file is too large for conversion (max 100 MB).', 413 );
            }
            if ( $size < 16 ) {
                throw new RuntimeException( 'The file is empty.', 400 );
            }
            $name = self::clean_name( isset( $f['name'] ) ? $f['name'] : '' );
            list( $base, $ext ) = self::split_name( $name );
            if ( ! in_array( $ext, array( 'tif', 'tiff', 'eps' ), true ) ) {
                throw new RuntimeException( 'Only TIFF and EPS files can be converted.', 415 );
            }

            $out = self::temp_file( '.png' );
            self::convert_to_png( $f['tmp_name'], $ext === 'eps' ? 'eps' : 'tiff', $out );

            $len = (int) filesize( $out );
            while ( ob_get_level() > 0 ) {
                @ob_end_clean();
            }
            nocache_headers();
            header( 'Cache-Control: no-store' );
            header( 'Content-Type: image/png' );
            header( 'Content-Length: ' . $len );
            header( 'X-GP-Filename: ' . rawurlencode( $base ) . '.png' );
            readfile( $out );
            exit;
        } catch ( \Throwable $e ) {
            self::handle_exception( $e );
        }
    }

    private static function handle_exception( $e ) {
        $code = (int) $e->getCode();
        if ( $e instanceof RuntimeException && $code >= 400 && $code < 600 ) {
            $data = array( 'message' => $e->getMessage() );
            if ( $e instanceof SG_Upload_Missing ) {
                $data['missing'] = $e->missing;
            }
            wp_send_json_error( $data, $code );
        }
        error_log( 'gp_upload error: ' . get_class( $e ) . ': ' . $e->getMessage() );
        wp_send_json_error( array( 'message' => 'Server error while processing the upload.' ), 500 );
    }

    /* ---------------------------------------------------------------- chunked */

    public static function ajax_chunk() {
        try {
            self::check_nonce();
            $step = isset( $_POST['step'] ) ? (string) $_POST['step'] : '';
            if ( $step === 'init' ) {
                self::step_init();
            } elseif ( $step === 'put' ) {
                self::step_put();
            } elseif ( $step === 'finish' ) {
                self::step_finish();
            }
            throw new RuntimeException( 'Unknown step.', 400 );
        } catch ( \Throwable $e ) {
            self::handle_exception( $e );
        }
    }

    private static function step_init() {
        self::rate_limit( 'init', 30 );
        $name = self::clean_name( isset( $_POST['name'] ) ? wp_unslash( (string) $_POST['name'] ) : '' );
        $size = isset( $_POST['size'] ) ? (int) $_POST['size'] : 0;
        list( , $ext ) = self::split_name( $name );
        if ( $name === '' || ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'pdf', 'tif', 'tiff', 'eps', 'ai' ), true ) ) {
            throw new RuntimeException( 'This file type is not supported.', 415 );
        }
        if ( $size < 16 ) {
            throw new RuntimeException( 'The file is empty.', 400 );
        }
        if ( $size > self::MAX_BYTES ) {
            throw new RuntimeException( 'The file is too large (max 200 MB).', 413 );
        }

        $base = self::base_dir();
        self::cleanup_old();
        $id  = bin2hex( random_bytes( 12 ) );
        $dir = $base . '/' . $id;
        if ( ! @mkdir( $dir, 0755 ) ) {
            throw new RuntimeException( 'Server storage is not available.', 500 );
        }
        $meta = array(
            'name'    => $name,
            'ext'     => $ext,
            'size'    => $size,
            'created' => time(),
            'ip'      => md5( self::client_ip() . wp_salt() ),
        );
        if ( file_put_contents( $dir . '/meta.json', wp_json_encode( $meta ) ) === false ) {
            self::rrmdir( $dir );
            throw new RuntimeException( 'Server storage is not available.', 500 );
        }
        wp_send_json_success( array( 'id' => $id, 'chunk' => self::CHUNK_BYTES ) );
    }

    private static function step_put() {
        self::rate_limit( 'put', 600 );
        $id   = isset( $_POST['id'] ) ? (string) $_POST['id'] : '';
        $dir  = self::job_dir( $id );
        $meta = self::read_meta( $dir );

        if ( ! isset( $_POST['index'] ) || ! preg_match( '/^\d{1,4}$/', (string) $_POST['index'] ) ) {
            throw new RuntimeException( 'Invalid chunk index.', 400 );
        }
        $index = (int) $_POST['index'];
        if ( $index > self::MAX_INDEX ) {
            throw new RuntimeException( 'Invalid chunk index.', 400 );
        }
        if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) || is_array( $_FILES['file']['tmp_name'] ) ) {
            throw new RuntimeException( 'No chunk received.', 400 );
        }
        $f   = $_FILES['file'];
        $err = isset( $f['error'] ) ? (int) $f['error'] : UPLOAD_ERR_NO_FILE;
        if ( $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ) {
            throw new RuntimeException( 'The chunk is too large.', 413 );
        }
        if ( $err !== UPLOAD_ERR_OK || ! is_uploaded_file( $f['tmp_name'] ) ) {
            throw new RuntimeException( 'The chunk upload failed. Please retry.', 400 );
        }
        $csize = (int) filesize( $f['tmp_name'] );
        if ( $csize < 1 || $csize > self::CHUNK_MAX_BYTES ) {
            throw new RuntimeException( 'Invalid chunk size.', 413 );
        }

        $total = $csize;
        $files = glob( $dir . '/part-*' );
        if ( is_array( $files ) ) {
            foreach ( $files as $p ) {
                if ( basename( $p ) === 'part-' . $index ) {
                    continue;
                }
                $total += (int) filesize( $p );
            }
        }
        if ( $total > (int) $meta['size'] + 1048576 ) {
            throw new RuntimeException( 'More data than declared was received.', 413 );
        }

        $part = $dir . '/part-' . $index;
        $tmp  = $dir . '/tmp-' . $index;
        if ( ! move_uploaded_file( $f['tmp_name'], $tmp ) ) {
            throw new RuntimeException( 'Could not store the chunk.', 500 );
        }
        if ( ! @rename( $tmp, $part ) ) {
            @unlink( $tmp );
            throw new RuntimeException( 'Could not store the chunk.', 500 );
        }
        @touch( $dir );
        wp_send_json_success( array( 'index' => $index ) );
    }

    private static function step_finish() {
        self::rate_limit( 'fin', 40 );
        $id   = isset( $_POST['id'] ) ? (string) $_POST['id'] : '';
        $dir  = self::job_dir( $id );
        $meta = self::read_meta( $dir );

        if ( ! isset( $_POST['expected'] ) || ! preg_match( '/^\d{1,4}$/', (string) $_POST['expected'] ) ) {
            throw new RuntimeException( 'Invalid chunk count.', 400 );
        }
        $expected = (int) $_POST['expected'];
        if ( $expected < 1 || $expected > self::MAX_INDEX + 1 ) {
            throw new RuntimeException( 'Invalid chunk count.', 400 );
        }

        @ignore_user_abort( true );
        @set_time_limit( 300 );

        $missing = array();
        for ( $i = 0; $i < $expected; $i++ ) {
            if ( ! is_file( $dir . '/part-' . $i ) ) {
                $missing[] = $i;
            }
        }
        if ( $missing ) {
            throw new SG_Upload_Missing( 'Some chunks are missing.', $missing );
        }

        // From here on the directory is ours to delete whatever happens.
        self::register_cleanup( $dir );

        $ext       = (string) $meta['ext'];
        $assembled = $dir . '/assembled.' . $ext;
        $out       = fopen( $assembled, 'wb' );
        if ( ! $out ) {
            throw new RuntimeException( 'Server storage is not available.', 500 );
        }
        for ( $i = 0; $i < $expected; $i++ ) {
            $in = fopen( $dir . '/part-' . $i, 'rb' );
            if ( ! $in ) {
                fclose( $out );
                throw new RuntimeException( 'Could not read a chunk.', 500 );
            }
            stream_copy_to_stream( $in, $out );
            fclose( $in );
            @unlink( $dir . '/part-' . $i );
        }
        fclose( $out );
        clearstatcache();

        $size = (int) filesize( $assembled );
        if ( $size !== (int) $meta['size'] ) {
            throw new RuntimeException( 'The uploaded size does not match the declared size.', 422 );
        }
        if ( ! self::magic_ok( $ext, self::read_head( $assembled ) ) ) {
            throw new RuntimeException( 'The file content does not match its type.', 415 );
        }

        list( $base ) = self::split_name( (string) $meta['name'] );
        $path = $assembled;
        if ( $ext === 'tif' || $ext === 'tiff' || $ext === 'eps' ) {
            self::rate_limit( 'conv', 20 );
            $png = $dir . '/converted.png';
            self::convert_to_png( $assembled, $ext === 'eps' ? 'eps' : 'tiff', $png );
            @unlink( $assembled );
            $path = $png;
            $name = $base . '.png';
            $type = 'image/png';
        } elseif ( $ext === 'png' ) {
            $name = $base . '.png';
            $type = 'image/png';
        } elseif ( $ext === 'jpg' || $ext === 'jpeg' ) {
            $name = $base . '.' . $ext;
            $type = 'image/jpeg';
        } else { // pdf, ai
            $name = $base . '.pdf';
            $type = 'application/pdf';
        }
        clearstatcache();

        $handler = 'wp_ajax_nopriv_nbdesigner_customer_upload';
        if ( ! has_action( $handler ) ) {
            throw new RuntimeException( 'The designer upload handler is not available.', 500 );
        }
        $_FILES['file'] = array(
            'name'     => $name,
            'type'     => $type,
            'tmp_name' => $path,
            'error'    => 0,
            'size'     => (int) filesize( $path ),
        );
        // The core handler outputs its JSON itself and ends with wp_die().
        do_action( $handler );
        wp_die();
    }
}

class SG_Upload_Missing extends RuntimeException {
    public $missing = array();

    public function __construct( $message, $missing ) {
        parent::__construct( $message, 409 );
        $this->missing = $missing;
    }
}

SG_Upload::init();
