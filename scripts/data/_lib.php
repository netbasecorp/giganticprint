<?php
/**
 * Shared helpers for the Gigantic Prints data scripts (CLI only, run through scripts/data/run.sh).
 *
 * Contract for every data script:
 *   - idempotent: running it twice leaves the data in the same final state;
 *   - default mode is --dry-run (prints what would change, writes nothing);
 *   - --apply writes and saves a JSON backup of EVERY option / postmeta / row / term it touches
 *     (run.sh moves it to /root/backups/giganticprints/data/backup-<script>-<ts>.json);
 *   - --restore=<backup.json> puts the old values back (created rows/terms are deleted).
 */
if ( PHP_SAPI !== 'cli' ) { exit( 1 ); }

$_SERVER['HTTP_HOST']   = 'giganticprints.printcart.com';
$_SERVER['HTTPS']       = 'on';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define( 'WP_USE_THEMES', false );
require ( getenv( 'GP_DOCROOT' ) ?: '/home/giganticprints/public_html' ) . '/wp-load.php';
add_filter( 'pre_wp_mail', '__return_true' ); // never send mail from a data script

class GP_Data {
	public static $mode   = 'dry';   // dry | apply | restore
	public static $name   = '';
	public static $ops    = array();
	public static $arg    = array();
	public static $restore_file = '';

	public static function init( $name ) {
		global $argv;
		self::$name = $name;
		foreach ( array_slice( $argv, 1 ) as $a ) {
			if ( '--apply' === $a ) { self::$mode = 'apply'; }
			elseif ( '--dry-run' === $a ) { self::$mode = 'dry'; }
			elseif ( 0 === strpos( $a, '--restore=' ) ) { self::$mode = 'restore'; self::$restore_file = substr( $a, 10 ); }
			else { self::$arg[] = $a; }
		}
		self::say( "== $name mode=" . self::$mode );
		if ( 'restore' === self::$mode ) { self::restore(); exit( 0 ); }
	}

	public static function say( $m ) { echo $m . "\n"; }
	public static function writing() { return 'apply' === self::$mode; }

	/* ---- options ---- */
	public static function raw_option( $name ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	}
	public static function option_set( $name, $new ) {
		$old_raw = self::raw_option( $name );
		$old     = get_option( $name, null );
		if ( null !== $old && $old === $new ) { self::say( "  option $name: unchanged" ); return false; }
		self::say( '  option ' . $name . ': ' . self::short( $old ) . ' -> ' . self::short( $new ) );
		if ( self::writing() ) {
			self::$ops[] = array( 'type' => 'option', 'name' => $name, 'existed' => null !== $old_raw, 'old_raw' => $old_raw );
			update_option( $name, $new );
		}
		return true;
	}

	/* ---- postmeta ---- */
	public static function meta_set( $post_id, $key, $new ) {
		global $wpdb;
		$old_raw = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1", $post_id, $key ) );
		$old     = get_post_meta( $post_id, $key, true );
		if ( null !== $old_raw && $old === $new ) { self::say( "  postmeta $post_id/$key: unchanged" ); return false; }
		self::say( "  postmeta $post_id/$key: " . self::short( $old ) . ' -> ' . self::short( $new ) );
		if ( self::writing() ) {
			self::$ops[] = array( 'type' => 'postmeta', 'post_id' => $post_id, 'key' => $key, 'existed' => null !== $old_raw, 'old_raw' => $old_raw );
			update_post_meta( $post_id, $key, $new );
		}
		return true;
	}

	/* ---- generic table row update (old values of the touched columns are kept) ---- */
	public static function row_update( $table, array $where, array $data, $label = '' ) {
		global $wpdb;
		$cond = array(); $args = array();
		foreach ( $where as $c => $v ) { $cond[] = "`$c` = %s"; $args[] = $v; }
		$sql = "SELECT * FROM `$table` WHERE " . implode( ' AND ', $cond );
		$old = $wpdb->get_row( $wpdb->prepare( $sql, $args ), ARRAY_A );
		if ( ! $old ) { self::say( "  row $table " . json_encode( $where ) . ': NOT FOUND' ); return false; }
		$changed = array(); $oldv = array();
		foreach ( $data as $c => $v ) { if ( (string) $old[ $c ] !== (string) $v ) { $changed[ $c ] = $v; $oldv[ $c ] = $old[ $c ]; } }
		if ( ! $changed ) { self::say( "  row $table " . json_encode( $where ) . ' ' . $label . ': unchanged' ); return false; }
		self::say( "  row $table " . json_encode( $where ) . ' ' . $label . ': ' . self::short( $oldv ) . ' -> ' . self::short( $changed ) );
		if ( self::writing() ) {
			self::$ops[] = array( 'type' => 'row', 'table' => $table, 'where' => $where, 'old' => $oldv );
			$wpdb->update( $table, $changed, $where );
		}
		return true;
	}
	public static function row_insert( $table, array $data, $id_col = 'id', $label = '' ) {
		global $wpdb;
		self::say( "  insert $table $label: " . self::short( $data ) );
		if ( self::writing() ) {
			$wpdb->insert( $table, $data );
			$id = $wpdb->insert_id;
			self::$ops[] = array( 'type' => 'insert', 'table' => $table, 'id_col' => $id_col, 'id' => $id );
			return $id;
		}
		return 0;
	}

	/* ---- terms ---- */
	public static function term_snapshot( $term_id, $taxonomy ) {
		$t = get_term( $term_id, $taxonomy );
		if ( ! $t || is_wp_error( $t ) ) return null;
		return array( 'name' => $t->name, 'slug' => $t->slug, 'order' => get_term_meta( $term_id, 'order', true ) );
	}
	public static function term_update( $term_id, $taxonomy, $name, $slug, $order ) {
		$old = self::term_snapshot( $term_id, $taxonomy );
		if ( ! $old ) { self::say( "  term $term_id: NOT FOUND" ); return false; }
		$new = array( 'name' => $name, 'slug' => $slug, 'order' => (string) $order );
		if ( $old['name'] === $new['name'] && $old['slug'] === $new['slug'] && (string) $old['order'] === $new['order'] ) { self::say( "  term $term_id ($name): unchanged" ); return false; }
		self::say( "  term $term_id: " . self::short( $old ) . ' -> ' . self::short( $new ) );
		if ( self::writing() ) {
			self::$ops[] = array( 'type' => 'term', 'term_id' => $term_id, 'taxonomy' => $taxonomy, 'old' => $old );
			wp_update_term( $term_id, $taxonomy, array( 'name' => $name, 'slug' => $slug ) );
			update_term_meta( $term_id, 'order', $order );
		}
		return true;
	}
	public static function term_create( $taxonomy, $name, $slug, $order ) {
		$ex = get_term_by( 'slug', $slug, $taxonomy );
		if ( $ex ) { self::term_update( $ex->term_id, $taxonomy, $name, $slug, $order ); return $ex->term_id; }
		self::say( "  term create $taxonomy $slug ($name) order $order" );
		if ( self::writing() ) {
			$r = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
			if ( is_wp_error( $r ) ) { self::say( '  ERROR ' . $r->get_error_message() ); return 0; }
			update_term_meta( $r['term_id'], 'order', $order );
			self::$ops[] = array( 'type' => 'term_created', 'term_id' => $r['term_id'], 'taxonomy' => $taxonomy );
			return $r['term_id'];
		}
		return 0;
	}


	/* ---- posts / pages (created or updated by slug) ---- */
	public static function post_upsert( $post_type, $slug, $title, $content, $status = 'publish', $old_slug = '' ) {
		global $wpdb;
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s AND post_status <> 'trash' LIMIT 1", $post_type, $slug ) );
		if ( ! $id && $old_slug ) {
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s AND post_status <> 'trash' LIMIT 1", $post_type, $old_slug ) );
		}
		if ( $id ) {
			$old = $wpdb->get_row( $wpdb->prepare( "SELECT post_title, post_content, post_status, post_name FROM {$wpdb->posts} WHERE ID = %d", $id ), ARRAY_A );
			if ( $old['post_title'] === $title && $old['post_content'] === $content && $old['post_status'] === $status && $old['post_name'] === $slug ) {
				self::say( "  $post_type $slug (#$id): unchanged" );
				return $id;
			}
			self::say( "  $post_type #$id: '" . $old['post_title'] . "' (" . $old['post_status'] . ', ' . $old['post_name'] . ") -> '$title' ($status, $slug)" );
			if ( self::writing() ) {
				self::$ops[] = array( 'type' => 'post_update', 'id' => $id, 'old' => $old );
				wp_update_post( wp_slash( array( 'ID' => $id, 'post_title' => $title, 'post_content' => $content, 'post_status' => $status, 'post_name' => $slug ) ) );
			}
			return $id;
		}
		self::say( "  create $post_type $slug ('$title', $status)" );
		if ( self::writing() ) {
			$new = wp_insert_post( wp_slash( array( 'post_type' => $post_type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ) ) );
			if ( ! is_wp_error( $new ) && $new ) { self::$ops[] = array( 'type' => 'post_created', 'id' => (int) $new ); return (int) $new; }
		}
		return 0;
	}

	/* ---- WooCommerce shipping zone created by a script (deleted again on restore) ---- */
	public static function note_zone_created( $zone_id ) {
		self::$ops[] = array( 'type' => 'wc_zone_created', 'zone_id' => (int) $zone_id );
	}

	public static function flush_caches() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_nbo\\_%' OR option_name LIKE '\\_transient\\_timeout\\_nbo\\_%' OR option_name LIKE '\\_transient\\_wc\\_product\\_loop%' OR option_name LIKE '\\_transient\\_gp\\_from\\_%'" );
		wp_cache_flush();
		if ( function_exists( 'wc_delete_product_transients' ) ) { /* per product when needed */ }
		delete_transient( 'wc_term_counts' );
		delete_transient( 'nbd_all_settings' ); // NBDesigner caches all its settings in transients
		delete_transient( 'nbd_all_frontend_settings' );
	}

	public static function finish() {
		if ( self::writing() ) {
			self::flush_caches();
			$file = ( getenv( 'GP_STAGE' ) ?: sys_get_temp_dir() ) . '/backup-' . self::$name . '-' . gmdate( 'Ymd\THis\Z' ) . '.json';
			file_put_contents( $file, wp_json_encode( array( 'script' => self::$name, 'created' => gmdate( 'c' ), 'ops' => self::$ops ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			@chmod( $file, 0600 );
			self::say( '== applied ' . count( self::$ops ) . ' change(s)' );
		} else {
			self::say( '== dry-run: nothing written' );
		}
	}

	public static function restore() {
		global $wpdb;
		$j = json_decode( file_get_contents( self::$restore_file ), true );
		if ( ! $j || empty( $j['ops'] ) ) { self::say( 'empty/invalid backup' ); exit( 1 ); }
		foreach ( array_reverse( $j['ops'] ) as $op ) {
			switch ( $op['type'] ) {
				case 'option':
					if ( $op['existed'] ) {
						if ( null === self::raw_option( $op['name'] ) ) { add_option( $op['name'], 'x' ); }
						$wpdb->update( $wpdb->options, array( 'option_value' => $op['old_raw'] ), array( 'option_name' => $op['name'] ) );
					} else { delete_option( $op['name'] ); }
					self::say( 'restored option ' . $op['name'] );
					break;
				case 'postmeta':
					if ( $op['existed'] ) {
						$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $op['old_raw'] ), array( 'post_id' => $op['post_id'], 'meta_key' => $op['key'] ) );
					} else { delete_post_meta( $op['post_id'], $op['key'] ); }
					self::say( "restored postmeta {$op['post_id']}/{$op['key']}" );
					break;
				case 'row':
					$wpdb->update( $op['table'], $op['old'], $op['where'] );
					self::say( 'restored row ' . $op['table'] . ' ' . json_encode( $op['where'] ) );
					break;
				case 'insert':
					$wpdb->delete( $op['table'], array( $op['id_col'] => $op['id'] ) );
					self::say( 'deleted inserted row ' . $op['table'] . ' #' . $op['id'] );
					break;
				case 'term':
					wp_update_term( $op['term_id'], $op['taxonomy'], array( 'name' => $op['old']['name'], 'slug' => $op['old']['slug'] ) );
					update_term_meta( $op['term_id'], 'order', $op['old']['order'] );
					self::say( 'restored term ' . $op['term_id'] );
					break;
				case 'post_update':
					wp_update_post( wp_slash( array( 'ID' => $op['id'], 'post_title' => $op['old']['post_title'], 'post_content' => $op['old']['post_content'], 'post_status' => $op['old']['post_status'], 'post_name' => $op['old']['post_name'] ) ) );
					self::say( 'restored post #' . $op['id'] );
					break;
				case 'post_created':
					wp_delete_post( $op['id'], true );
					self::say( 'deleted created post #' . $op['id'] );
					break;
				case 'wc_zone_created':
					if ( class_exists( 'WC_Shipping_Zone' ) ) { $z = new WC_Shipping_Zone( (int) $op['zone_id'] ); $z->delete(); }
					self::say( 'deleted created shipping zone #' . $op['zone_id'] );
					break;
				case 'term_created':
					wp_delete_term( $op['term_id'], $op['taxonomy'] );
					self::say( 'deleted created term ' . $op['term_id'] );
					break;
			}
		}
		self::flush_caches();
		self::say( '== restored from ' . self::$restore_file );
	}

	private static function short( $v ) {
		$s = is_scalar( $v ) || null === $v ? var_export( $v, true ) : wp_json_encode( $v );
		return strlen( $s ) > 160 ? substr( $s, 0, 157 ) . '...' : $s;
	}
}
