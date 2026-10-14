<?php
/**
 * B5 data changes (idempotent, backup of every touched value, --restore supported):
 *   scripts/data/run.sh b5-woo-checkout.php [--dry-run|--apply|--restore=<backup.json>]
 *
 * PLACEHOLDERS the customer must confirm before go-live (all marked here and in the CHG entry):
 *  1. Payment: offline gateway "Pay by invoice" (WooCommerce BACS renamed, no bank account). PayPal Payments stays installed but NOT connected.
 *  2. Shipping zone "United States": "Pickup - Fort Pierce, FL" (free) and flat rate "Ground shipping - quoted" 0.00.
 *  3. Legal pages: Terms of Service, Refund Policy, Privacy Policy, Accessibility = neutral DRAFT text for Gigantic Prints to review.
 *  4. Track your order page (WooCommerce order tracking shortcode).
 * Also: admin can download print-ready design files of an order (PNG + PDF without watermark + uploaded files),
 *       PC Designer notification recipients (was blank) = the address WooCommerce e-mails already use,
 *       wind slits option label says the real flat charge.
 * Admin e-mail recipients of WooCommerce are NOT touched.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b5-woo-checkout' );
global $wpdb;
$T_SETS = $wpdb->prefix . 'nbdesigner_options';

/* ---- 1. payment ------------------------------------------------------------------ */
GP_Data::say( '-- payment: Pay by invoice (BACS renamed)' );
$bacs = get_option( 'woocommerce_bacs_settings', array() );
if ( ! is_array( $bacs ) ) { $bacs = array(); }
$bacs['enabled']      = 'yes';
$bacs['title']        = 'Pay by invoice';
$bacs['description']  = 'Place your order and we will send you a PayPal invoice before production starts.';
$bacs['instructions'] = 'We will send a PayPal invoice before production.';
if ( ! isset( $bacs['account_details'] ) ) { $bacs['account_details'] = array(); }
GP_Data::option_set( 'woocommerce_bacs_settings', $bacs );

/* ---- 2. shipping ------------------------------------------------------------------ */
GP_Data::say( '-- shipping zone United States (placeholders)' );
$zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $z ) { if ( 'United States' === $z['zone_name'] ) { $zone = new WC_Shipping_Zone( $z['zone_id'] ); } }
if ( ! $zone ) {
	GP_Data::say( '  create zone "United States" (country US)' );
	if ( GP_Data::writing() ) {
		$zone = new WC_Shipping_Zone( 0 );
		$zone->set_zone_name( 'United States' );
		$zone->set_zone_order( 0 );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		GP_Data::note_zone_created( $zone->get_id() );
	}
}
$wanted = array(
	'local_pickup' => array( 'title' => "Pickup \xE2\x80\x93 Fort Pierce, FL", 'tax_status' => 'none', 'cost' => '0' ),
	'flat_rate'    => array( 'title' => "Ground shipping \xE2\x80\x94 quoted",  'tax_status' => 'none', 'cost' => '0' ),
);
foreach ( $wanted as $method_id => $settings ) {
	$inst = 0;
	if ( $zone ) {
		foreach ( $zone->get_shipping_methods() as $m ) { if ( $m->id === $method_id ) { $inst = (int) $m->get_instance_id(); } }
		if ( ! $inst && GP_Data::writing() ) { $inst = (int) $zone->add_shipping_method( $method_id ); GP_Data::say( "  added $method_id (instance $inst)" ); }
		elseif ( ! $inst ) { GP_Data::say( "  would add $method_id" ); }
	} else { GP_Data::say( "  would add $method_id" ); }
	if ( $inst ) {
		$name = 'woocommerce_' . $method_id . '_' . $inst . '_settings';
		$cur  = get_option( $name, array() );
		if ( ! is_array( $cur ) ) { $cur = array(); }
		GP_Data::option_set( $name, array_merge( $cur, $settings ) );
		$wpdb->update( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'is_enabled' => 1 ), array( 'instance_id' => $inst ) );
	}
}

/* ---- 3. design files for the admin + notifications recipient ---------------------- */
GP_Data::say( '-- PC Designer: print-ready files for the admin' );
// The admin downloads through wp-admin > Customer Design (admin.php?page=nbdesigner_detail_order); the 'customer download' group stays OFF.
foreach ( array( 'nbdesigner_download_design_png', 'nbdesigner_download_design_pdf', 'nbdesigner_download_design_upload_file' ) as $o ) { GP_Data::option_set( $o, '0' ); }
GP_Data::option_set( 'nbdesigner_enable_pdf_watermark', 'no' ); // print-ready PDFs must not be watermarked
$recip = get_option( 'nbdesigner_notifications_emails', '' );
if ( '' === trim( (string) $recip ) ) { GP_Data::option_set( 'nbdesigner_notifications_emails', get_option( 'admin_email' ) ); }
else { GP_Data::say( '  nbdesigner_notifications_emails already set: unchanged' ); }

/* ---- 4. wind slits label (price unchanged) --------------------------------------- */
GP_Data::say( '-- wind slits label: real flat charge' );
$ids = $wpdb->get_col( "SELECT id FROM $T_SETS" );
foreach ( $ids as $sid ) {
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT fields FROM $T_SETS WHERE id = %d", $sid ) );
	if ( false === strpos( (string) $raw, 'Wind Slits' ) ) { continue; }
	$f = maybe_unserialize( $raw );
	if ( ! is_array( $f ) || serialize( $f ) !== $raw ) { GP_Data::say( "  set $sid: serialization does not round-trip, SKIPPED" ); continue; }
	$changed = false;
	foreach ( $f['fields'] as &$fl ) {
		if ( empty( $fl['general']['title'] ) || ! preg_match( '/wind\s*slits/i', $fl['general']['title'] ) || empty( $fl['general']['attributes']['options'] ) ) { continue; }
		foreach ( $fl['general']['attributes']['options'] as &$op ) {
			if ( isset( $op['name'] ) && false !== stripos( $op['name'], '/sqft' ) ) { $op['name'] = preg_replace( '#\s*/\s*sq\s*ft#i', '', $op['name'] ); $changed = true; }
		}
		unset( $op );
	}
	unset( $fl );
	if ( $changed ) { GP_Data::row_update( $T_SETS, array( 'id' => $sid ), array( 'fields' => serialize( $f ), 'modified' => current_time( 'mysql' ) ), 'wind slits label' ); }
}

/* ---- 5. pages ----------------------------------------------------------------------- */
GP_Data::say( '-- pages (draft texts for Gigantic Prints to review)' );
$draft = '<p><strong>Draft &ndash; to be reviewed by Gigantic Prints before go-live.</strong></p>';
$terms = $draft . '
<h2>1. About these terms</h2>
<p>These terms apply to every order placed on this website with Gigantic Prints ("we", "us"). By placing an order you accept them.</p>
<h2>2. Orders and pricing</h2>
<p>Prices are shown in US dollars and are calculated from the product, size and options you choose. Large or special jobs may be quoted separately. We start production after we have received payment or an approved invoice.</p>
<h2>3. Artwork and proofs</h2>
<p>You are responsible for the artwork you upload (content, spelling, rights to use it, size and resolution). We warn you when an image has a low resolution, but we print what you approve. A proof is available on request before production.</p>
<h2>4. Colour and materials</h2>
<p>Printed colours can differ slightly from what you see on a screen, and between materials and production runs. Small variations are normal and are not a defect.</p>
<h2>5. Production and delivery</h2>
<p>Production times are estimates. Shipping cost and delivery time are confirmed before production. Risk passes to you when the order is handed to the carrier or picked up.</p>
<h2>6. Changes and cancellations</h2>
<p>Orders are made to measure. You can change or cancel an order until production has started; after that the order cannot be cancelled.</p>
<h2>7. Problems with an order</h2>
<p>Please tell us within 7 days of delivery. See our Refund Policy for what we reprint or refund.</p>
<h2>8. Liability</h2>
<p>To the extent permitted by law our liability is limited to the price paid for the affected order.</p>
<h2>9. Contact</h2>
<p>Gigantic Prints, 2101 Orange Ave, Ft. Pierce, FL &middot; hoyle@giganticprints.com &middot; 772-429-8818</p>';
$refund = $draft . '
<h2>Our promise</h2>
<p>If your order arrives damaged, or does not match the file you approved, we will reprint it or refund it.</p>
<h2>What we cover</h2>
<ul><li>Print defects (streaks, wrong size, wrong material or wrong finishing compared to your order).</li><li>Damage during shipping (please keep the packaging and send photos).</li></ul>
<h2>What we do not cover</h2>
<ul><li>Mistakes in the artwork you approved (spelling, resolution, colours you chose).</li><li>Small colour differences between screen and print.</li><li>Orders that were made to your own measurements and are no longer wanted.</li></ul>
<h2>How to claim</h2>
<p>Contact us within 7 days of delivery with your order number and photos. We answer within 2 business days and tell you whether we reprint or refund.</p>
<h2>Refunds</h2>
<p>Refunds go back to the original payment method (or the PayPal invoice) within 10 business days after we approve the claim.</p>';
$privacy = $draft . '
<h2>What we collect</h2>
<p>Your name, e-mail, phone, billing and shipping address, the artwork you upload, and the orders you place. We use cookies for the shopping cart and login.</p>
<h2>How we use it</h2>
<p>To produce and deliver your order, to send you invoices and order updates, and to answer your questions. We do not sell your data.</p>
<h2>Who sees it</h2>
<p>Our team, our payment provider (PayPal), and carriers that deliver your order. Uploaded artwork is stored on our servers and on our cloud storage provider only to print your order.</p>
<h2>Your choices</h2>
<p>You can ask us to export or delete your account data by contacting hoyle@giganticprints.com.</p>';
$access = $draft . '
<h2>Accessibility</h2>
<p>We want everyone to be able to order from this website. If you have trouble using any part of it, tell us at hoyle@giganticprints.com or 772-429-8818 and we will help you place your order.</p>';
$terms_id = GP_Data::post_upsert( 'page', 'terms', 'Terms of Service', $terms );
GP_Data::post_upsert( 'page', 'refund-policy', 'Refund Policy', $refund, 'publish', 'refund_returns' );
$privacy_id = GP_Data::post_upsert( 'page', 'privacy-policy', 'Privacy Policy', $privacy );
GP_Data::post_upsert( 'page', 'accessibility', 'Accessibility', $access );
GP_Data::post_upsert( 'page', 'track-order', 'Track your order', '<p>Enter your order number and the e-mail address you used to place the order.</p>[woocommerce_order_tracking]' );
// checkout notice 'I agree to the Terms and Conditions and Privacy Policy' links
if ( ! empty( $terms_id ) ) { GP_Data::option_set( 'woocommerce_terms_page_id', (string) $terms_id ); }
if ( ! empty( $privacy_id ) ) { GP_Data::option_set( 'wp_page_for_privacy_policy', (string) $privacy_id ); }
GP_Data::finish();
