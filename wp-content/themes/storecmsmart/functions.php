<?php
defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------
   1. ENQUEUE STYLES / SCRIPTS
--------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'giga_enqueue' );
function giga_enqueue() {
	$ver = wp_get_theme()->get( 'Version' );
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'giga-montserrat',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap',
		[], null
	);

	wp_enqueue_style( 'giga-main', $uri . '/assets/css/main.css', [ 'giga-montserrat' ], $ver );

	wp_enqueue_script( 'giga-main', $uri . '/assets/js/main.js', [], $ver, true );

	$js_data = [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ];
	if ( function_exists( 'wc_get_cart_url' ) ) {
		$js_data['cartUrl']    = wc_get_cart_url();
		$js_data['shopUrl']    = get_permalink( wc_get_page_id( 'shop' ) );
		$js_data['accountUrl'] = get_permalink( wc_get_page_id( 'myaccount' ) );
	}
	wp_localize_script( 'giga-main', 'gigaData', $js_data );

	if ( function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}

/* ---------------------------------------------------------------
   2. THEME SUPPORT
--------------------------------------------------------------- */
add_action( 'after_setup_theme', 'giga_setup' );
function giga_setup() {
	load_theme_textdomain( 'storecmsmart', get_template_directory() . '/languages' );

	add_theme_support( 'custom-logo', [
		'height'      => 96,
		'width'       => 300,
		'flex-height' => true,
		'flex-width'  => true,
	] );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption' ] );
	add_theme_support( 'customize-selective-refresh-widgets' );

	add_theme_support( 'woocommerce', [
		'thumbnail_image_width' => 600,
		'single_image_width'    => 900,
	] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

/* ---------------------------------------------------------------
   3. NAVIGATION MENUS
   Megamenu plugins hook tự động vào wp_nav_menu() qua location 'primary'.
--------------------------------------------------------------- */
add_action( 'after_setup_theme', 'giga_register_menus' );
function giga_register_menus() {
	register_nav_menus( [
		'utility'         => __( 'Utility Bar', 'storecmsmart' ),
		'primary'         => __( 'Primary Navigation', 'storecmsmart' ),
		'footer-products' => __( 'Footer — Products', 'storecmsmart' ),
		'footer-account'  => __( 'Footer — Account', 'storecmsmart' ),
	] );
}

/* ---------------------------------------------------------------
   4. SIDEBAR (tránh PHP warning khi WooCommerce gọi get_sidebar)
--------------------------------------------------------------- */
add_action( 'widgets_init', 'giga_register_sidebars' );
function giga_register_sidebars() {
	register_sidebar( [
		'name'          => __( 'Shop Sidebar', 'storecmsmart' ),
		'id'            => 'shop-sidebar',
		'before_widget' => '<div class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	] );
}

/* ---------------------------------------------------------------
   5. HELPERS
--------------------------------------------------------------- */
function giga_cart_count() {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		return WC()->cart->get_cart_contents_count();
	}
	return 0;
}

function giga_get_rollover_image_url( $product_id ) {
	$gallery_ids = get_post_meta( $product_id, '_product_image_gallery', true );
	if ( $gallery_ids ) {
		$ids = explode( ',', $gallery_ids );
		if ( ! empty( $ids[0] ) ) {
			return wp_get_attachment_image_url( (int) $ids[0], 'woocommerce_single' );
		}
	}
	return '';
}

function giga_option( $key, $fallback = '' ) {
	return get_theme_mod( $key, $fallback );
}

function giga_breadcrumb() {
	$sep  = '<span class="bc-sep" aria-hidden="true">›</span>';
	$home = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'storecmsmart' ) . '</a>';
	$crumbs = [ $home ];

	if ( is_single() ) {
		$cats = get_the_category();
		if ( $cats ) {
			$crumbs[] = '<a href="' . esc_url( get_category_link( $cats[0]->term_id ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
		}
		$crumbs[] = esc_html( get_the_title() );

	} elseif ( is_page() ) {
		foreach ( array_reverse( (array) get_post_ancestors( get_the_ID() ) ) as $anc_id ) {
			$crumbs[] = '<a href="' . esc_url( get_permalink( $anc_id ) ) . '">' . esc_html( get_the_title( $anc_id ) ) . '</a>';
		}
		$crumbs[] = esc_html( get_the_title() );

	} elseif ( is_category() ) {
		$crumbs[] = esc_html( single_cat_title( '', false ) );

	} elseif ( is_tag() ) {
		$crumbs[] = esc_html( single_tag_title( '', false ) );

	} elseif ( is_author() ) {
		$crumbs[] = esc_html( get_the_author() );

	} elseif ( is_date() ) {
		if ( is_year() )       $crumbs[] = esc_html( get_the_date( 'Y' ) );
		elseif ( is_month() )  $crumbs[] = esc_html( get_the_date( 'F Y' ) );
		else                   $crumbs[] = esc_html( get_the_date() );

	} elseif ( is_search() ) {
		/* translators: %s: search query */
		$crumbs[] = sprintf( esc_html__( 'Search: %s', 'storecmsmart' ), esc_html( get_search_query() ) );

	} elseif ( is_404() ) {
		$crumbs[] = esc_html__( '404 — Not found', 'storecmsmart' );

	} elseif ( is_home() && ! is_front_page() ) {
		$blog_id  = (int) get_option( 'page_for_posts' );
		$crumbs[] = $blog_id ? esc_html( get_the_title( $blog_id ) ) : esc_html__( 'Blog', 'storecmsmart' );
	}

	if ( count( $crumbs ) <= 1 ) return;

	echo '<nav class="giga-bc" aria-label="' . esc_attr__( 'Breadcrumb', 'storecmsmart' ) . '">';
	echo implode( ' ' . $sep . ' ', $crumbs );
	echo '</nav>';
}

/* ---------------------------------------------------------------
   6. CUSTOMIZER
--------------------------------------------------------------- */
add_action( 'customize_register', 'giga_customizer' );
function giga_customizer( $wp_customize ) {

	// ── Helper: register a batch of text settings + controls ──────
	$register = function( $section_id, array $fields ) use ( $wp_customize ) {
		foreach ( $fields as $id => $args ) {
			$wp_customize->add_setting( $id, [
				'default'           => $args['default'] ?? '',
				'sanitize_callback' => ! empty( $args['textarea'] ) ? 'wp_kses_post' : 'sanitize_text_field',
				'transport'         => 'postMessage',
			] );
			$wp_customize->add_control( $id, [
				'label'   => $args['label'],
				'section' => $section_id,
				'type'    => ! empty( $args['textarea'] ) ? 'textarea' : 'text',
			] );
		}
	};

	// ── SECTION: Header — Utility Bar ────────────────────────────
	$wp_customize->add_section( 'giga_header', [
		'title'    => __( '⬆ Header — Utility Bar', 'storecmsmart' ),
		'priority' => 25,
	] );
	$register( 'giga_header', [
		'giga_util_bar_text' => [ 'label' => __( 'Utility bar text (HTML OK)', 'storecmsmart' ), 'textarea' => true,
			'default' => 'Ordering open <strong>24 / 7</strong> · Ships nationwide' ],
	] );

	// ── SECTION: Homepage Hero ─────────────────────────────────────
	$wp_customize->add_section( 'giga_hero', [
		'title'    => __( '🏠 Homepage — Hero', 'storecmsmart' ),
		'priority' => 30,
	] );
	$register( 'giga_hero', [
		'giga_hero_eyebrow'  => [ 'label' => __( 'Eyebrow text',        'storecmsmart' ), 'default' => 'Now accepting 24-hour orders' ],
		'giga_hero_title'    => [ 'label' => __( 'Hero title (HTML OK)', 'storecmsmart' ), 'default' => 'Large-format signs,<br><span class="accent">printed in 24 hours.</span>', 'textarea' => true ],
		'giga_hero_desc'     => [ 'label' => __( 'Hero description',     'storecmsmart' ), 'default' => 'Upload your artwork or design online. Real-time pricing, real-time preview. Ships to anywhere in the US.', 'textarea' => true ],
		'giga_hero_cta_text' => [ 'label' => __( 'Primary CTA text',    'storecmsmart' ), 'default' => 'Browse products' ],
		'giga_hero_cta_url'  => [ 'label' => __( 'Primary CTA URL',     'storecmsmart' ), 'default' => '' ],
		'giga_b2b_url'       => [ 'label' => __( 'B2B Apply URL',       'storecmsmart' ), 'default' => '#' ],
	] );

	// Hero visual — animated panel text (used when no image is uploaded)
	$register( 'giga_hero', [
		'giga_hero_visual_l1'      => [ 'label' => __( 'Hero panel — Line 1 (leave blank = site name word 1)', 'storecmsmart' ), 'default' => '' ],
		'giga_hero_visual_l2'      => [ 'label' => __( 'Hero panel — Line 2 (leave blank = site name word 2)', 'storecmsmart' ), 'default' => '' ],
		'giga_hero_visual_tagline' => [ 'label' => __( 'Hero panel — Tagline',                                 'storecmsmart' ), 'default' => '24-hour production' ],
	] );

	// Hero visual — image upload (optional; falls back to CSS animated visual)
	$wp_customize->add_setting( 'giga_hero_image', [
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'postMessage',
	] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'giga_hero_image', [
		'label'       => __( 'Hero visual image (replaces animated panel)', 'storecmsmart' ),
		'description' => __( 'Recommended: 800×640 px or larger. Leave empty to use the default animated design.', 'storecmsmart' ),
		'section'     => 'giga_hero',
	] ) );

	// ── SECTION: Footer — Brand column ────────────────────────────
	$wp_customize->add_section( 'giga_footer_brand', [
		'title'    => __( '🦶 Footer — Brand', 'storecmsmart' ),
		'priority' => 50,
	] );
	$register( 'giga_footer_brand', [
		'giga_footer_tagline'       => [ 'label' => __( 'Tagline / description',                          'storecmsmart' ), 'textarea' => true, 'default' => '' ],
		'giga_footer_powered_title' => [ 'label' => __( '"Powered by" bold text (leave blank to hide)',   'storecmsmart' ), 'default' => '' ],
		'giga_footer_powered_sub'   => [ 'label' => __( '"Powered by" subtitle',                         'storecmsmart' ), 'default' => '' ],
		'giga_parent_brand_name'    => [ 'label' => __( 'Parent brand name in Account links (blank = hide)', 'storecmsmart' ), 'default' => '' ],
		'giga_parent_brand_url'     => [ 'label' => __( 'Parent brand URL',                               'storecmsmart' ), 'default' => '#' ],
	] );

	// ── SECTION: Footer — Contact ─────────────────────────────────
	$wp_customize->add_section( 'giga_footer_contact', [
		'title'    => __( '🦶 Footer — Contact', 'storecmsmart' ),
		'priority' => 51,
	] );
	$register( 'giga_footer_contact', [
		'giga_city'          => [ 'label' => __( 'City / Region (used in hero & category text)',   'storecmsmart' ), 'default' => '' ],
		'giga_footer_phone'      => [ 'label' => __( 'Phone (display)',                            'storecmsmart' ), 'default' => '' ],
		'giga_footer_phone_href' => [ 'label' => __( 'Phone (digits only, for tel: link)',         'storecmsmart' ), 'default' => '' ],
		'giga_footer_email'      => [ 'label' => __( 'Email address',                              'storecmsmart' ), 'default' => '' ],
		'giga_footer_address'    => [ 'label' => __( 'Physical address',                           'storecmsmart' ), 'default' => '' ],
		'giga_footer_hours'      => [ 'label' => __( 'Business hours',                             'storecmsmart' ), 'default' => 'Mon–Fri · 9:00 AM – 5:00 PM' ],
	] );

	// ── SECTION: Footer — Bottom bar ─────────────────────────────
	$wp_customize->add_section( 'giga_footer_bottom', [
		'title'    => __( '🦶 Footer — Bottom bar', 'storecmsmart' ),
		'priority' => 52,
	] );
	$register( 'giga_footer_bottom', [
		'giga_footer_copyright_name' => [ 'label' => __( 'Copyright name (empty = use site title)', 'storecmsmart' ), 'default' => '' ],
		'giga_footer_copyright'      => [ 'label' => __( 'Copyright suffix (after "© year Name ·")',  'storecmsmart' ),
			'default' => 'A nbDesign company. All rights reserved.' ],
	] );
}

/* ---------------------------------------------------------------
   7. WOOCOMMERCE — tắt page title mặc định, wrap content
--------------------------------------------------------------- */
add_filter( 'woocommerce_show_page_title', '__return_false' );

add_action( 'woocommerce_before_main_content', 'giga_woo_open', 5 );
add_action( 'woocommerce_after_main_content',  'giga_woo_close', 15 );
function giga_woo_open()  { echo '<div class="woo-inner">'; }
function giga_woo_close() { echo '</div>'; }

// Move breadcrumb into page-hero (rendered manually in woocommerce.php)
add_action( 'init', function() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
}, 20 );
