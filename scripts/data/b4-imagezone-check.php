<?php
/**
 * B4 Image Zone check (company private image library).
 *   scripts/data/run.sh b4-imagezone-check.php [--dry-run|--apply]
 *
 * Dry run: only checks that the class/table/endpoints exist.
 * --apply (creates and ALWAYS removes temporary rows/files): store 'GP IZ Test' owned by gp_test_b2b with
 * gp_test_b2c as member, a 3600x2400 @150 dpi PNG stored through PCS_Company_Images::store_file(),
 * row + files + thumbnail, access rules, rejects, delete.
 *
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b4-imagezone-check' );
global $wpdb;

$GLOBALS['gp_b4iz_fail'] = 0;

function gp_b4iz_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b4iz_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b4iz_val( $v ) {
	return is_scalar( $v ) || null === $v ? var_export( $v, true ) : wp_json_encode( $v );
}

$store_id = 0;
$tmp_png  = '/tmp/gp-iz-test-' . getmypid() . '.png';
$tmp_bad  = '/tmp/gp-iz-test-' . getmypid() . '-bad.png';
$tmp_txt  = '/tmp/gp-iz-test-' . getmypid() . '.txt';

try {

	GP_Data::say( '-- 1. class, table, endpoints' );
	$have = class_exists( 'PCS_Company_Images' );
	gp_b4iz_assert( 'PCS_Company_Images loaded', $have );
	if ( $have ) {
		$tbl = PCS_Company_Images::table();
		gp_b4iz_assert( 'table ' . $tbl . ' exists', $tbl === $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $tbl ) . "'" ) );
		foreach ( array( 'gp_iz_list', 'gp_iz_upload', 'gp_iz_file', 'gp_iz_delete' ) as $a ) {
			gp_b4iz_assert( "wp_ajax_$a registered", has_action( 'wp_ajax_' . $a ) !== false );
			gp_b4iz_assert( "no wp_ajax_nopriv_$a", false === has_action( 'wp_ajax_nopriv_' . $a ) );
		}
		gp_b4iz_assert( 'Imagick available', class_exists( 'Imagick' ) );
	}

	GP_Data::say( '-- 2. upload, access, delete' );
	if ( ! GP_Data::writing() ) {
		GP_Data::say( '  SKIP (needs --apply: creates and removes a temporary store, rows and files)' );
	} elseif ( ! $have || ! class_exists( 'Imagick' ) ) {
		gp_b4iz_assert( 'prerequisites for the apply part', false );
	} else {
		$owner    = get_user_by( 'login', 'gp_test_b2b' );
		$member   = get_user_by( 'login', 'gp_test_b2c' );
		$reseller = get_user_by( 'login', 'gp_test_reseller' );
		gp_b4iz_assert( 'test users exist', $owner && $member && $reseller );

		if ( $owner && $member && $reseller ) {
			if ( PCS_DB::get_store_by_user( $owner->ID ) ) {
				gp_b4iz_assert( 'gp_test_b2b has no store yet (refusing to touch an existing one)', false );
			} else {
				$store_id = (int) PCS_DB::save_store( array(
					'user_id'       => $owner->ID,
					'company_name'  => 'GP IZ Test',
					'company_slug'  => 'gp-iz-test-temp',
					'tier'          => 'b2b_a',
					'status'        => 'active',
					'contact_name'  => 'GP Test',
					'contact_email' => $owner->user_email,
				) );
				gp_b4iz_assert( 'temporary store created', $store_id > 0 );
			}
		}

		if ( $store_id > 0 ) {
			PCS_DB::add_member( $store_id, $member->ID, 'member', 0 );

			// fixture image: 3600x2400 @150 dpi
			$im = new Imagick();
			$im->newImage( 3600, 2400, new ImagickPixel( '#3366cc' ), 'png' );
			$im->setImageUnits( Imagick::RESOLUTION_PIXELSPERINCH );
			$im->setImageResolution( 150, 150 );
			$im->writeImage( $tmp_png );
			$im->clear();
			gp_b4iz_assert( 'fixture PNG written', is_file( $tmp_png ) );

			$item = PCS_Company_Images::store_file( $tmp_png, 'GP IZ Test.png', $store_id, $member->ID );
			gp_b4iz_assert( 'store_file() succeeded', is_array( $item ) && ! empty( $item['id'] ), is_wp_error( $item ) ? $item->get_error_message() : '' );

			if ( is_array( $item ) && ! empty( $item['id'] ) ) {
				$row = PCS_Company_Images::get( $item['id'] );
				gp_b4iz_assert( 'row exists with store/uploader', $row && (int) $row->store_id === $store_id && (int) $row->user_id === (int) $member->ID );
				gp_b4iz_assert( 'width 3600 / height 2400', $row && 3600 === (int) $row->width && 2400 === (int) $row->height, $row ? $row->width . 'x' . $row->height : '' );
				gp_b4iz_assert( 'dpi 150', $row && 150 === (int) $row->dpi, $row ? gp_b4iz_val( $row->dpi ) : '' );
				gp_b4iz_assert( 'ext/mime png', $row && 'png' === $row->ext && 'image/png' === $row->mime );
				$path = PCS_Company_Images::file_path( $row );
				gp_b4iz_assert( 'original file stored', $path && is_file( $path ) && filesize( $path ) === (int) $row->bytes );
				$thumb = $path . '-t.jpg';
				$ti    = is_file( $thumb ) ? getimagesize( $thumb ) : false;
				gp_b4iz_assert( 'thumbnail exists (max 320 px, jpeg)', $ti && max( $ti[0], $ti[1] ) <= 320 && max( $ti[0], $ti[1] ) >= 300 && 'image/jpeg' === $ti['mime'], $ti ? $ti[0] . 'x' . $ti[1] : 'missing' );
				$dir = PCS_Company_Images::store_dir( $store_id );
				gp_b4iz_assert( 'folder has .htaccess (Deny from all) and index.php', is_file( $dir . '/.htaccess' ) && false !== strpos( (string) file_get_contents( $dir . '/.htaccess' ), 'Deny from all' ) && is_file( $dir . '/index.php' ) );
				gp_b4iz_assert( 'stored name is random (not the original name)', false === strpos( $row->file, 'GP' ) && 1 === preg_match( '/^[a-f0-9]{32}\.png$/', $row->file ) );
				gp_b4iz_assert( 'print label 24 x 16 in at 150 dpi', '24 x 16 in at 150 dpi' === $item['print_label'], $item['print_label'] );
				gp_b4iz_assert( 'item has thumb url and can_delete for the uploader', ! empty( $item['thumb'] ) && true === $item['can_delete'] );

				// access
				gp_b4iz_assert( 'user_store_ids(owner) contains the store', in_array( $store_id, PCS_Company_Images::user_store_ids( $owner->ID ), true ) );
				gp_b4iz_assert( 'can_access: owner', true === PCS_Company_Images::can_access( $store_id, $owner->ID ) );
				gp_b4iz_assert( 'can_access: member', true === PCS_Company_Images::can_access( $store_id, $member->ID ) );
				gp_b4iz_assert( 'can_access: gp_test_reseller denied', false === PCS_Company_Images::can_access( $store_id, $reseller->ID ) );
				gp_b4iz_assert( 'can_access: guest denied', false === PCS_Company_Images::can_access( $store_id, 0 ) );
				gp_b4iz_assert( 'list_for_store returns the image', 1 === count( PCS_Company_Images::list_for_store( $store_id, $owner->ID ) ) );
				gp_b4iz_assert( 'owner can delete a member image', PCS_Company_Images::can_delete( $row, $owner->ID ) );
				gp_b4iz_assert( 'reseller cannot delete', ! PCS_Company_Images::can_delete( $row, $reseller->ID ) );

				// rejects
				file_put_contents( $tmp_bad, 'this is not a png' );
				$r = PCS_Company_Images::store_file( $tmp_bad, 'fake.png', $store_id, $owner->ID );
				gp_b4iz_assert( 'fake PNG (bad magic bytes) rejected', is_wp_error( $r ) );
				file_put_contents( $tmp_txt, 'hello' );
				$r = PCS_Company_Images::store_file( $tmp_txt, 'notes.txt', $store_id, $owner->ID );
				gp_b4iz_assert( 'disallowed extension rejected', is_wp_error( $r ) );
				$r = PCS_Company_Images::store_file( $tmp_png, 'shell.php', $store_id, $owner->ID );
				gp_b4iz_assert( '.php rejected', is_wp_error( $r ) );
				gp_b4iz_assert( 'rejects stored nothing', 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Images::table() . ' WHERE store_id = %d', $store_id ) ) );

				// delete
				gp_b4iz_assert( 'delete_image() true', true === PCS_Company_Images::delete_image( $row ) );
				gp_b4iz_assert( 'row removed', null === PCS_Company_Images::get( $item['id'] ) );
				gp_b4iz_assert( 'original and thumbnail removed', ! file_exists( $path ) && ! file_exists( $thumb ) );
			}
		}
	}

} finally {

	GP_Data::say( '-- cleanup' );
	@unlink( $tmp_png );
	@unlink( $tmp_bad );
	@unlink( $tmp_txt );
	if ( $store_id > 0 ) {
		PCS_Company_Images::purge_store( $store_id );
		$dir = PCS_Company_Images::store_dir( $store_id );
		if ( is_dir( $dir ) ) {
			foreach ( (array) glob( $dir . '/{,.}*', GLOB_BRACE ) as $f ) {
				if ( is_file( $f ) ) {
					@unlink( $f );
				}
			}
			@rmdir( $dir );
		}
		PCS_DB::delete_store( $store_id ); // also removes the member rows
		$left = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Images::table() . ' WHERE store_id = %d', $store_id ) )
		      + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_stores WHERE id = %d", $store_id ) )
		      + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_members WHERE store_id = %d", $store_id ) );
		GP_Data::say( '  temporary store/member/image rows and files removed' . ( 0 === $left && ! is_dir( $dir ) ? '' : " (WARNING $left row(s) left or folder remains, store id $store_id)" ) );
	}
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b4iz_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b4iz_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b4iz_fail'] ? 1 : 0 );
