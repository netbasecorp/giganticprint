<?php
/**
 * B4 company template library checks.
 *   scripts/data/run.sh b4-templates-check.php [--dry-run|--apply]
 *
 * --apply only (creates temporary rows and ALWAYS removes them in a finally block):
 *   - temp store 'GP TPL Test' owned by gp_test_b2b, gp_test_b2c as member
 *   - save / list / get / rename / clone / delete through PCS_Company_Templates
 *   - access: owner + member true, gp_test_reseller + guest false
 *   - config > 2 MB and invalid JSON are rejected, unknown product rejected
 *   - the hide_public_templates setting helper round-trips (option restored afterwards)
 *
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b4-templates-check' );
global $wpdb;

$GLOBALS['gp_b4t_fail'] = 0;

function gp_b4t_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b4t_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b4t_is_err( $v, $code = '' ) {
	return is_wp_error( $v ) && ( '' === $code || $v->get_error_code() === $code );
}

/* ---- state to clean up ---------------------------------------------------- */
$store_id      = 0;
$tpl_ids       = array();
$settings_had  = false;
$settings_raw  = null;
$settings_read = false;

try {

	GP_Data::say( '-- 0. classes' );
	if ( ! class_exists( 'PCS_Company_Templates' ) || ! class_exists( 'PCS_Company_Images' ) ) {
		gp_b4t_assert( 'PCS_Company_Templates / PCS_Company_Images loaded', false );
	} elseif ( ! GP_Data::writing() ) {
		gp_b4t_assert( 'PCS_Company_Templates loaded', true );
		GP_Data::say( '  SKIP the rest (needs --apply: creates and removes a temporary store and templates)' );
	} else {
		PCS_Company_Templates::maybe_create_table();
		$tbl = PCS_Company_Templates::table();
		gp_b4t_assert( 'table exists', $tbl === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) );

		$owner    = get_user_by( 'login', 'gp_test_b2b' );
		$member   = get_user_by( 'login', 'gp_test_b2c' );
		$reseller = get_user_by( 'login', 'gp_test_reseller' );
		gp_b4t_assert( 'test users gp_test_b2b / gp_test_b2c / gp_test_reseller exist', $owner && $member && $reseller );

		$product_ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
		$product_id  = $product_ids ? (int) $product_ids[0] : 0;
		gp_b4t_assert( 'a published product exists', $product_id > 0 );

		if ( $owner && $member && $reseller && $product_id ) {
			$members_t = $wpdb->prefix . 'pcs_b2b_members';

			/* 1. temp store ---------------------------------------------------- */
			GP_Data::say( '-- 1. temporary store' );
			$store_id = (int) PCS_DB::save_store( array(
				'user_id'       => $owner->ID,
				'company_name'  => 'GP TPL Test',
				'company_slug'  => 'gp-tpl-test-temp',
				'tier'          => 'b2b_a',
				'status'        => 'active',
				'contact_name'  => 'GP Test',
				'contact_email' => $owner->user_email,
			) );
			gp_b4t_assert( 'temporary store created', $store_id > 0 );
		}

		if ( $store_id > 0 ) {
			$added = PCS_DB::add_member( $store_id, $member->ID, 'member', 0 );
			gp_b4t_assert( 'gp_test_b2c added as member', (bool) $added );

			/* 2. access ---------------------------------------------------------- */
			GP_Data::say( '-- 2. access' );
			gp_b4t_assert( 'owner has access', true === PCS_Company_Templates::can_access( $store_id, $owner->ID ) );
			gp_b4t_assert( 'member has access', true === PCS_Company_Templates::can_access( $store_id, $member->ID ) );
			gp_b4t_assert( 'gp_test_reseller has no access', false === PCS_Company_Templates::can_access( $store_id, $reseller->ID ) );
			gp_b4t_assert( 'guest (user 0) has no access', false === PCS_Company_Templates::can_access( $store_id, 0 ) );
			gp_b4t_assert( 'store is among the owner stores', in_array( $store_id, PCS_Company_Templates::user_store_ids( $owner->ID ), true ) );
			gp_b4t_assert( 'reseller has no company stores', ! in_array( $store_id, PCS_Company_Templates::user_store_ids( $reseller->ID ), true ) );

			/* 3. save / list / get ------------------------------------------------ */
			GP_Data::say( '-- 3. save / list / get' );
			$cfg_arr = array( 'v' => 1, 'product_id' => $product_id, 'qty' => 5, 'options' => array( 'f1' => array( 'value' => '2', 'value_name' => 'Gloss' ) ), 'stages' => array( array( 'objects' => array() ) ), 'dims' => null, 'artMode' => null );
			$cfg     = wp_json_encode( $cfg_arr );
			$thumb   = 'data:image/jpeg;base64,' . base64_encode( 'fake-jpeg-bytes' );

			$t1 = PCS_Company_Templates::save( $store_id, $owner->ID, '  <b>Business</b> card  ', $product_id, $cfg, $thumb );
			gp_b4t_assert( 'owner can save a template', is_array( $t1 ) && ! empty( $t1['id'] ), is_wp_error( $t1 ) ? $t1->get_error_message() : '' );
			if ( is_array( $t1 ) && ! empty( $t1['id'] ) ) {
				$tpl_ids[] = (int) $t1['id'];
				gp_b4t_assert( 'name is sanitized (no tags, trimmed)', 'Business card' === $t1['name'], $t1['name'] );
				gp_b4t_assert( 'item carries product id/name and thumbnail', $t1['product_id'] === $product_id && '' !== $t1['product_name'] && $t1['thumb'] === $thumb );
				gp_b4t_assert( 'start_url contains product_id and gp_tpl', false !== strpos( $t1['start_url'], 'product_id=' . $product_id ) && false !== strpos( $t1['start_url'], 'gp_tpl=' . $t1['id'] ) );

				$row = PCS_Company_Templates::get( $t1['id'] );
				gp_b4t_assert( 'stored config round-trips', $row && json_decode( $row->config, true ) == $cfg_arr );

				$bad_thumb = PCS_Company_Templates::save( $store_id, $member->ID, 'Bad thumb', $product_id, $cfg, 'data:text/html;base64,AAAA' );
				if ( is_array( $bad_thumb ) ) {
					$tpl_ids[] = (int) $bad_thumb['id'];
				}
				gp_b4t_assert( 'a non-JPEG thumbnail is dropped (template still saved)', is_array( $bad_thumb ) && '' === $bad_thumb['thumb'] );

				$list = PCS_Company_Templates::list_for_store( $store_id, $member->ID );
				$ids  = array_map( function ( $i ) { return $i['id']; }, $list );
				gp_b4t_assert( 'member sees the owner template in the list', in_array( $t1['id'], $ids, true ) );
				gp_b4t_assert( 'list filtered by product returns it', count( PCS_Company_Templates::list_for_store( $store_id, $member->ID, $product_id ) ) >= 1 );
				gp_b4t_assert( 'list filtered by another product is empty', 0 === count( PCS_Company_Templates::list_for_store( $store_id, $member->ID, $product_id + 999999 ) ) );
				gp_b4t_assert( 'list without thumbnails leaves thumb empty', '' === PCS_Company_Templates::list_for_store( $store_id, $member->ID, 0, false )[0]['thumb'] );

				/* update with id */
				$upd = PCS_Company_Templates::save( $store_id, $member->ID, '', $product_id, wp_json_encode( array_merge( $cfg_arr, array( 'qty' => 9 ) ) ), '', $t1['id'] );
				$row = PCS_Company_Templates::get( $t1['id'] );
				gp_b4t_assert( 'member can update the template content (name kept)', is_array( $upd ) && 9 === (int) json_decode( $row->config, true )['qty'] && 'Business card' === $row->name );

				/* 4. rename / clone ---------------------------------------------------- */
				GP_Data::say( '-- 4. rename / clone' );
				$ren = PCS_Company_Templates::rename( $t1['id'], $member->ID, 'Renamed card' );
				gp_b4t_assert( 'member can rename', is_array( $ren ) && 'Renamed card' === $ren['name'] );
				gp_b4t_assert( 'empty name is rejected', gp_b4t_is_err( PCS_Company_Templates::rename( $t1['id'], $owner->ID, '   ' ), 'gp_tpl_name' ) );
				gp_b4t_assert( 'reseller cannot rename', gp_b4t_is_err( PCS_Company_Templates::rename( $t1['id'], $reseller->ID, 'Hacked' ), 'gp_tpl_denied' ) );
				gp_b4t_assert( 'name unchanged after the denied rename', 'Renamed card' === PCS_Company_Templates::get( $t1['id'] )->name );

				$cl = PCS_Company_Templates::clone_template( $t1['id'], $member->ID );
				gp_b4t_assert( 'member can clone', is_array( $cl ) && ! empty( $cl['id'] ) && $cl['id'] !== $t1['id'] );
				if ( is_array( $cl ) && ! empty( $cl['id'] ) ) {
					$tpl_ids[] = (int) $cl['id'];
					$crow = PCS_Company_Templates::get( $cl['id'] );
					gp_b4t_assert( "clone is named 'Copy of Renamed card'", 'Copy of Renamed card' === $cl['name'] );
					gp_b4t_assert( 'clone keeps product, store and config', $crow && (int) $crow->product_id === $product_id && (int) $crow->store_id === $store_id && $crow->config === PCS_Company_Templates::get( $t1['id'] )->config );
				}
				gp_b4t_assert( 'reseller cannot clone', gp_b4t_is_err( PCS_Company_Templates::clone_template( $t1['id'], $reseller->ID ), 'gp_tpl_denied' ) );

				/* 5. validation ---------------------------------------------------------- */
				GP_Data::say( '-- 5. validation' );
				$big = '{"pad":"' . str_repeat( 'a', PCS_Company_Templates::MAX_CONFIG_BYTES ) . '"}';
				gp_b4t_assert( 'config larger than 2 MB is rejected', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $owner->ID, 'Big', $product_id, $big ), 'gp_tpl_big' ) );
				gp_b4t_assert( 'invalid JSON is rejected', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $owner->ID, 'Bad', $product_id, '{not json' ), 'gp_tpl_json' ) );
				gp_b4t_assert( 'a JSON scalar is rejected', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $owner->ID, 'Bad', $product_id, '"just a string"' ), 'gp_tpl_json' ) );
				gp_b4t_assert( 'empty config is rejected', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $owner->ID, 'Bad', $product_id, '' ), 'gp_tpl_config' ) );
				gp_b4t_assert( 'unknown product is rejected', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $owner->ID, 'Bad', 999999999, $cfg ), 'gp_tpl_product' ) );
				gp_b4t_assert( 'empty name is rejected on create', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $owner->ID, '  ', $product_id, $cfg ), 'gp_tpl_name' ) );
				gp_b4t_assert( 'reseller cannot save into the company', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, $reseller->ID, 'Nope', $product_id, $cfg ), 'gp_tpl_denied' ) );
				gp_b4t_assert( 'guest cannot save into the company', gp_b4t_is_err( PCS_Company_Templates::save( $store_id, 0, 'Nope', $product_id, $cfg ), 'gp_tpl_denied' ) );
				$count_after = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE store_id = %d", $store_id ) );
				gp_b4t_assert( 'rejected saves created no rows', $count_after === count( $tpl_ids ), $count_after . ' rows vs ' . count( $tpl_ids ) . ' expected' );

				/* 6. delete ---------------------------------------------------------------- */
				GP_Data::say( '-- 6. delete' );
				gp_b4t_assert( 'reseller cannot delete', gp_b4t_is_err( PCS_Company_Templates::delete( $t1['id'], $reseller->ID ), 'gp_tpl_denied' ) );
				gp_b4t_assert( 'still there after the denied delete', null !== PCS_Company_Templates::get( $t1['id'] ) );
				if ( isset( $cl['id'] ) ) {
					gp_b4t_assert( 'the creator (member) can delete the clone', true === PCS_Company_Templates::delete( $cl['id'], $member->ID ) );
					gp_b4t_assert( 'clone is gone', null === PCS_Company_Templates::get( $cl['id'] ) );
				}
				gp_b4t_assert( 'the company owner can delete a member template', true === PCS_Company_Templates::delete( $t1['id'], $owner->ID ) );
				gp_b4t_assert( 'template is gone', null === PCS_Company_Templates::get( $t1['id'] ) );
				gp_b4t_assert( 'deleting a missing id fails', gp_b4t_is_err( PCS_Company_Templates::delete( $t1['id'], $owner->ID ), 'gp_tpl_denied' ) );
			}

			/* 7. hide-public setting helper ------------------------------------------------- */
			GP_Data::say( '-- 7. hide_public_templates setting' );
			$settings_raw  = GP_Data::raw_option( 'pcs_store_settings' );
			$settings_had  = null !== $settings_raw;
			$settings_read = true;
			gp_b4t_assert( 'default is false', false === PCS_Company_Templates::hide_public( $store_id ) );
			$all = get_option( 'pcs_store_settings', array() );
			if ( ! is_array( $all ) ) {
				$all = array();
			}
			if ( class_exists( 'PCS_Private_Products' ) && method_exists( 'PCS_Private_Products', 'set_store_setting' ) ) {
				PCS_Private_Products::set_store_setting( $store_id, 'hide_public_templates', 1 );
			} else {
				$all[ $store_id ] = array( 'hide_public_templates' => 1 );
				update_option( 'pcs_store_settings', $all, false );
			}
			gp_b4t_assert( 'true after the setting is switched on', true === PCS_Company_Templates::hide_public( $store_id ) );
			gp_b4t_assert( 'other stores are not affected', false === PCS_Company_Templates::hide_public( $store_id + 987654 ) );
			if ( class_exists( 'PCS_Private_Products' ) && method_exists( 'PCS_Private_Products', 'set_store_setting' ) ) {
				PCS_Private_Products::set_store_setting( $store_id, 'hide_public_templates', 0 );
			} else {
				unset( $all[ $store_id ] );
				update_option( 'pcs_store_settings', $all, false );
			}
			gp_b4t_assert( 'false again after switching it off', false === PCS_Company_Templates::hide_public( $store_id ) );
		}
	}

} finally {

	/* ALWAYS clean up --------------------------------------------------------- */
	GP_Data::say( '-- cleanup' );
	if ( class_exists( 'PCS_Company_Templates' ) && $store_id > 0 ) {
		PCS_Company_Templates::purge_store( $store_id );
		$left_t = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Templates::table() . ' WHERE store_id = %d', $store_id ) );
		GP_Data::say( '  temporary templates removed' . ( 0 === $left_t ? '' : " (WARNING $left_t row(s) left)" ) );
	}
	if ( $settings_read && $settings_had ) {
		update_option( 'pcs_store_settings', maybe_unserialize( $settings_raw ), false );
		GP_Data::say( '  pcs_store_settings restored' );
	} elseif ( $settings_read ) {
		// the option did not exist before the test
		delete_option( 'pcs_store_settings' );
		GP_Data::say( '  pcs_store_settings removed (did not exist before)' );
	}
	if ( $store_id > 0 ) {
		// delete_store() removes the store row and all its member rows
		PCS_DB::delete_store( $store_id );
		$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_stores WHERE id = %d", $store_id ) )
		      + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_members WHERE store_id = %d", $store_id ) );
		GP_Data::say( '  temporary store/member rows removed' . ( 0 === $left ? '' : " (WARNING $left row(s) left, store id $store_id)" ) );
	}
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b4t_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b4t_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b4t_fail'] ? 1 : 0 );
