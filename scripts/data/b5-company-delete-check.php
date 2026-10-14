<?php
/**
 * B5 company deletion check: deleting a company purges its Image Zone (rows + files + folder),
 * templates, private product assignments, per-company settings and member rows.
 *   scripts/data/run.sh b5-company-delete-check.php [--dry-run|--apply]
 *
 * Dry run: only checks that the classes, tables and the pcs_store_deleted hooks exist.
 * --apply (creates and ALWAYS removes temporary data): store 'GP Delete Test' owned by gp_test_reseller with
 * two images stored through PCS_Company_Images::store_file(), a template row, a private product assignment and a
 * pcs_store_settings entry; deleted through PCS_DB::delete_store(), then everything is asserted gone.
 *
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b5-company-delete-check' );
global $wpdb;

$GLOBALS['gp_b5d_fail'] = 0;

function gp_b5d_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b5d_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b5d_count( $table, $store_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE store_id = %d", $store_id ) );
}

$store_id = 0;
$deleted  = false;
$tmp_png  = '/tmp/gp-del-test-' . getmypid() . '.png';
$fake_pid = 987654321;

try {

	GP_Data::say( '-- 1. classes, tables, hooks' );
	$have = class_exists( 'PCS_Company_Images' ) && class_exists( 'PCS_Company_Templates' ) && class_exists( 'PCS_Private_Products' ) && class_exists( 'PCS_DB' );
	gp_b5d_assert( 'classes loaded', $have );
	if ( $have ) {
		foreach ( array( 'PCS_Company_Images', 'PCS_Company_Templates', 'PCS_Private_Products' ) as $c ) {
			$tbl = $c::table();
			gp_b5d_assert( "$c table $tbl exists", $tbl === $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $tbl ) . "'" ) );
			gp_b5d_assert( "$c::purge_store hooked on pcs_store_deleted", false !== has_action( 'pcs_store_deleted', array( $c, 'purge_store' ) ) );
		}
		gp_b5d_assert( 'Imagick available', class_exists( 'Imagick' ) );
	}

	GP_Data::say( '-- 2. delete purges everything' );
	if ( ! GP_Data::writing() ) {
		GP_Data::say( '  SKIP (needs --apply: creates and removes a temporary store with rows and files)' );
	} elseif ( ! $have || ! class_exists( 'Imagick' ) ) {
		gp_b5d_assert( 'prerequisites for the apply part', false );
	} else {
		$owner = get_user_by( 'login', 'gp_test_reseller' );
		gp_b5d_assert( 'test user gp_test_reseller exists', (bool) $owner );

		if ( $owner ) {
			if ( PCS_DB::get_store_by_user( $owner->ID ) ) {
				gp_b5d_assert( 'gp_test_reseller has no store yet (refusing to touch an existing one)', false );
			} else {
				$store_id = (int) PCS_DB::save_store( array(
					'user_id'       => $owner->ID,
					'company_name'  => 'GP Delete Test',
					'company_slug'  => 'gp-delete-test-temp',
					'tier'          => 'b2b_a',
					'status'        => 'active',
					'contact_name'  => 'GP Test',
					'contact_email' => $owner->user_email,
				) );
				gp_b5d_assert( 'temporary store created', $store_id > 0 );
			}
		}

		if ( $store_id > 0 ) {
			PCS_DB::add_member( $store_id, $owner->ID, 'member', 0 );

			$im = new Imagick();
			$im->newImage( 1200, 800, new ImagickPixel( '#cc3366' ), 'png' );
			$im->writeImage( $tmp_png );
			$im->clear();

			$paths = array();
			for ( $i = 1; $i <= 2; $i++ ) {
				$item = PCS_Company_Images::store_file( $tmp_png, 'GP Delete Test ' . $i . '.png', $store_id, $owner->ID );
				gp_b5d_assert( "image $i stored", is_array( $item ) && ! empty( $item['id'] ), is_wp_error( $item ) ? $item->get_error_message() : '' );
				if ( is_array( $item ) && ! empty( $item['id'] ) ) {
					$p = PCS_Company_Images::file_path( PCS_Company_Images::get( $item['id'] ) );
					$paths[] = $p;
					$paths[] = $p . '-t.jpg';
				}
			}
			$dir = PCS_Company_Images::store_dir( $store_id );
			gp_b5d_assert( 'folder and 4 files (2 originals, 2 thumbnails) exist', is_dir( $dir ) && 4 === count( array_filter( $paths, 'is_file' ) ) );
			// stray nested file, must go with the folder
			@mkdir( $dir . '/sub' );
			file_put_contents( $dir . '/sub/stray.txt', 'x' );

			// a sibling folder that must survive
			$sib = PCS_Company_Images::base_dir() . '/' . ( $store_id + 1000000 );
			wp_mkdir_p( $sib );
			file_put_contents( $sib . '/keep.txt', 'keep' );

			$now = gmdate( 'Y-m-d H:i:s' );
			$wpdb->insert( PCS_Company_Templates::table(), array(
				'store_id' => $store_id, 'user_id' => $owner->ID, 'name' => 'GP Delete Test tpl', 'product_id' => $fake_pid,
				'config' => '{"objects":[]}', 'thumb' => '', 'created_at' => $now, 'updated_at' => $now,
			), array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ) );
			gp_b5d_assert( 'template row created', 1 === gp_b5d_count( PCS_Company_Templates::table(), $store_id ) );

			gp_b5d_assert( 'private product assigned', PCS_Private_Products::assign( $store_id, $fake_pid ) && 1 === gp_b5d_count( PCS_Private_Products::table(), $store_id ) );
			PCS_Private_Products::set_store_setting( $store_id, 'hide_public_templates', 1 );
			$all = get_option( 'pcs_store_settings', array() );
			gp_b5d_assert( 'store setting created', is_array( $all ) && isset( $all[ $store_id ] ) );
			gp_b5d_assert( 'member row exists', gp_b5d_count( $wpdb->prefix . 'pcs_b2b_members', $store_id ) >= 1 );

			PCS_DB::delete_store( $store_id );
			$deleted = true;

			gp_b5d_assert( 'store row gone', 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_stores WHERE id = %d", $store_id ) ) );
			gp_b5d_assert( 'member rows gone', 0 === gp_b5d_count( $wpdb->prefix . 'pcs_b2b_members', $store_id ) );
			gp_b5d_assert( 'image rows gone', 0 === gp_b5d_count( PCS_Company_Images::table(), $store_id ) );
			gp_b5d_assert( 'image files gone', 0 === count( array_filter( $paths, 'file_exists' ) ) );
			gp_b5d_assert( 'folder gone (incl. .htaccess, index.php, nested)', ! file_exists( $dir ) );
			gp_b5d_assert( 'sibling folder untouched', is_file( $sib . '/keep.txt' ) );
			gp_b5d_assert( 'template rows gone', 0 === gp_b5d_count( PCS_Company_Templates::table(), $store_id ) );
			gp_b5d_assert( 'private product assignments gone', 0 === gp_b5d_count( PCS_Private_Products::table(), $store_id ) );
			$all = get_option( 'pcs_store_settings', array() );
			gp_b5d_assert( 'store setting gone', ! is_array( $all ) || ! array_key_exists( $store_id, $all ) );
		}
	}

} finally {

	GP_Data::say( '-- cleanup' );
	@unlink( $tmp_png );
	if ( $store_id > 0 ) {
		$sib = PCS_Company_Images::base_dir() . '/' . ( $store_id + 1000000 );
		@unlink( $sib . '/keep.txt' );
		@rmdir( $sib );
		if ( ! $deleted ) {
			PCS_DB::delete_store( $store_id ); // also purges everything through the hook
		}
		// belt and braces, in case a hook failed
		PCS_Company_Images::purge_store( $store_id );
		PCS_Company_Templates::purge_store( $store_id );
		PCS_Private_Products::purge_store( $store_id );
		$wpdb->delete( $wpdb->prefix . 'pcs_b2b_stores', array( 'id' => $store_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'pcs_b2b_members', array( 'store_id' => $store_id ), array( '%d' ) );
		GP_Data::say( '  temporary store ' . $store_id . ' and its data removed' );
	}
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b5d_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b5d_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b5d_fail'] ? 1 : 0 );
