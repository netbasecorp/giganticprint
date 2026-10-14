<?php
defined( 'ABSPATH' ) || exit;

require_once get_stylesheet_directory() . '/inc/gp-from-price.php';
require_once get_stylesheet_directory() . '/inc/gp-redirects.php';

/* ---------------------------------------------------------------
   SHOP PAGE: force page-shop.php for the WooCommerce shop page
   BLOG PAGE: force page-blog.php for the WordPress Posts Page
   (Both override template_include at priority 99 so our custom
   templates win over WooCommerce / WordPress defaults.)
--------------------------------------------------------------- */
add_filter( 'template_include', function ( $template ) {
	// WooCommerce shop
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$custom = get_stylesheet_directory() . '/page-shop.php';
		if ( file_exists( $custom ) ) return $custom;
	}
	// WordPress Posts Page (Settings → Reading → "Posts page")
	if ( is_home() && ! is_front_page() ) {
		$custom = get_stylesheet_directory() . '/page-blog.php';
		if ( file_exists( $custom ) ) return $custom;
	}
	return $template;
}, 99 );

/* ---------------------------------------------------------------
   GALLERY ITEM CPT + TAXONOMY + SPAN METABOX
--------------------------------------------------------------- */
add_action( 'init', 'giga_register_gallery_cpt' );
function giga_register_gallery_cpt() {
	register_post_type( 'gallery_item', [
		'labels'        => [
			'name'          => 'Gallery Items',
			'singular_name' => 'Gallery Item',
			'add_new_item'  => 'Add New Gallery Item',
			'edit_item'     => 'Edit Gallery Item',
			'not_found'     => 'No gallery items found.',
		],
		'public'        => false,
		'show_ui'       => true,
		'show_in_menu'  => true,
		'show_in_rest'  => true,
		'supports'      => [ 'title', 'thumbnail' ],
		'menu_icon'     => 'dashicons-format-gallery',
		'menu_position' => 5,
		'rewrite'       => false,
	] );

	register_taxonomy( 'gallery_type', 'gallery_item', [
		'labels'        => [
			'name'          => 'Gallery Types',
			'singular_name' => 'Gallery Type',
			'add_new_item'  => 'Add New Type',
			'edit_item'     => 'Edit Type',
		],
		'hierarchical'  => true,
		'public'        => false,
		'show_ui'       => true,
		'show_in_rest'  => true,
		'rewrite'       => false,
	] );
}

add_action( 'add_meta_boxes', 'giga_gallery_span_metabox' );
function giga_gallery_span_metabox() {
	add_meta_box( 'gallery_span', 'Grid Span Size', 'giga_gallery_span_cb', 'gallery_item', 'side', 'high' );
}
function giga_gallery_span_cb( $post ) {
	$span = get_post_meta( $post->ID, '_gallery_span', true ) ?: 'normal';
	wp_nonce_field( 'giga_gallery_span', 'giga_gallery_span_nonce' );
	echo '<select name="gallery_span" style="width:100%">';
	foreach ( [ 'normal' => 'Normal (1×1)', 'wide' => 'Wide (2×1)', 'tall' => 'Tall (1×2)', 'big' => 'Big (2×2)' ] as $val => $label ) {
		printf( '<option value="%s"%s>%s</option>', $val, selected( $span, $val, false ), $label );
	}
	echo '</select>';
	echo '<p style="margin-top:8px;font-size:11px;color:#666">Controls how much grid space this image occupies.</p>';
}
add_action( 'save_post_gallery_item', 'giga_gallery_save_span' );
function giga_gallery_save_span( $post_id ) {
	if ( ! isset( $_POST['giga_gallery_span_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['giga_gallery_span_nonce'], 'giga_gallery_span' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	update_post_meta( $post_id, '_gallery_span', sanitize_text_field( $_POST['gallery_span'] ?? 'normal' ) );
}

/* ---------------------------------------------------------------
   GALLERY BULK UPLOAD ADMIN PAGE
--------------------------------------------------------------- */
add_action( 'admin_menu', 'giga_gallery_bulk_menu' );
function giga_gallery_bulk_menu() {
	add_submenu_page(
		'edit.php?post_type=gallery_item',
		'Bulk Upload',
		'⬆ Bulk Upload',
		'upload_files',
		'gallery-bulk-upload',
		'giga_gallery_bulk_page'
	);
}

add_action( 'admin_enqueue_scripts', 'giga_gallery_bulk_enqueue' );
function giga_gallery_bulk_enqueue( $hook ) {
	if ( strpos( $hook, 'gallery-bulk-upload' ) === false ) return;
	wp_enqueue_media();
}

function giga_gallery_bulk_page() {
	$results  = [];
	$created  = 0;
	$has_post = isset( $_POST['giga_bulk_submit'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'giga_bulk_upload' );

	if ( $has_post ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$span     = sanitize_text_field( $_POST['gallery_span'] ?? 'normal' );
		$type_id  = intval( $_POST['gallery_type_id'] ?? 0 );
		$new_type = sanitize_text_field( $_POST['new_type_name'] ?? '' );

		/* Create new type if typed */
		if ( $new_type && ! $type_id ) {
			$term = wp_insert_term( $new_type, 'gallery_type' );
			if ( ! is_wp_error( $term ) ) $type_id = $term['term_id'];
		}

		/* ── Handle direct file uploads ── */
		if ( ! empty( $_FILES['gallery_images']['name'][0] ) ) {
			$files = $_FILES['gallery_images'];
			$count = count( $files['name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				if ( $files['error'][ $i ] !== UPLOAD_ERR_OK ) {
					$results[] = [ 'ok' => false, 'name' => $files['name'][ $i ], 'msg' => 'Upload error ' . $files['error'][ $i ] ];
					continue;
				}
				$_FILES['_giga_tmp'] = [
					'name'     => $files['name'][ $i ],
					'type'     => $files['type'][ $i ],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => $files['error'][ $i ],
					'size'     => $files['size'][ $i ],
				];
				$up = wp_handle_upload( $_FILES['_giga_tmp'], [ 'test_form' => false ] );
				if ( isset( $up['file'] ) ) {
					$title     = ucwords( strtolower( str_replace( [ '-', '_' ], ' ', pathinfo( $files['name'][ $i ], PATHINFO_FILENAME ) ) ) );
					$ft        = wp_check_filetype( basename( $up['file'] ) );
					$attach_id = wp_insert_attachment( [
						'guid'           => $up['url'],
						'post_mime_type' => $ft['type'],
						'post_title'     => $title,
						'post_status'    => 'inherit',
					], $up['file'] );
					wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $up['file'] ) );
					$pid = giga_create_gallery_item( $title, $attach_id, $span, $type_id );
					if ( $pid ) { $results[] = [ 'ok' => true, 'name' => $title ]; $created++; }
					else $results[] = [ 'ok' => false, 'name' => $title, 'msg' => 'Could not create post' ];
				} else {
					$results[] = [ 'ok' => false, 'name' => $files['name'][ $i ], 'msg' => $up['error'] ?? 'Unknown error' ];
				}
			}
		}

		/* ── Handle existing media library IDs ── */
		if ( ! empty( $_POST['attachment_ids'] ) ) {
			$ids = array_unique( array_filter( array_map( 'intval', preg_split( '/[\s,]+/', $_POST['attachment_ids'] ) ) ) );
			foreach ( $ids as $aid ) {
				$att = get_post( $aid );
				if ( ! $att || $att->post_type !== 'attachment' ) {
					$results[] = [ 'ok' => false, 'name' => "ID $aid", 'msg' => 'Not found in media library' ];
					continue;
				}
				$title = $att->post_title ?: ucwords( strtolower( str_replace( [ '-', '_' ], ' ', pathinfo( get_attached_file( $aid ), PATHINFO_FILENAME ) ) ) );
				$pid   = giga_create_gallery_item( $title, $aid, $span, $type_id );
				if ( $pid ) { $results[] = [ 'ok' => true, 'name' => $title ]; $created++; }
				else $results[] = [ 'ok' => false, 'name' => $title, 'msg' => 'Could not create post' ];
			}
		}
	}

	$gallery_types = get_terms( [ 'taxonomy' => 'gallery_type', 'hide_empty' => false ] );
	?>
	<div class="wrap">
	<h1 class="wp-heading-inline">Gallery — Bulk Upload</h1>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gallery_item' ) ); ?>" class="page-title-action">← All Items</a>
	<hr class="wp-header-end">

	<?php if ( $has_post && ! empty( $results ) ) : $ok = array_filter( $results, fn( $r ) => $r['ok'] ); ?>
	<div class="notice notice-<?php echo count( $ok ) ? 'success' : 'error'; ?> is-dismissible">
		<p><strong><?php echo count( $ok ); ?> of <?php echo count( $results ); ?> items created.</strong></p>
		<ul style="margin:0 0 8px 20px;list-style:disc">
		<?php foreach ( $results as $r ) : ?>
			<li><?php echo $r['ok'] ? '✅' : '❌'; ?> <?php echo esc_html( $r['name'] ); ?><?php if ( ! $r['ok'] ) echo ' — <em>' . esc_html( $r['msg'] ) . '</em>'; ?></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data" style="max-width:700px;margin-top:20px">
		<?php wp_nonce_field( 'giga_bulk_upload' ); ?>

		<div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:24px 28px;margin-bottom:16px">

			<h3 style="margin-top:0">1. Select Images</h3>

			<p><strong>Upload new images</strong></p>
			<div id="giga-dropzone" style="border:2px dashed #aaa;border-radius:6px;padding:32px;text-align:center;cursor:pointer;background:#fafafa;transition:border .2s" onclick="document.getElementById('giga-file-input').click()">
				<p style="margin:0 0 8px;font-size:28px">📂</p>
				<p style="margin:0;font-size:14px;color:#555">Click to choose files, or drag &amp; drop here</p>
				<p style="margin:4px 0 0;font-size:12px;color:#999">You can select multiple images at once (JPG, PNG, WebP)</p>
				<p id="giga-file-count" style="margin:8px 0 0;font-size:13px;font-weight:600;color:#2271b1;display:none"></p>
			</div>
			<input type="file" name="gallery_images[]" id="giga-file-input" multiple accept="image/*" style="display:none">

			<p style="margin:20px 0 6px"><strong>— or — pick from Media Library</strong></p>
			<div style="display:flex;gap:8px;align-items:center">
				<input type="text" name="attachment_ids" id="giga-att-ids" placeholder="IDs appear here after selection…" class="regular-text" readonly style="flex:1;background:#f9f9f9">
				<button type="button" class="button button-secondary" id="giga-open-media">Open Media Library</button>
			</div>
			<div id="giga-thumb-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px"></div>
		</div>

		<div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:24px 28px;margin-bottom:16px">
			<h3 style="margin-top:0">2. Settings (applied to all)</h3>
			<table class="form-table" style="margin:0">
				<tr>
					<th style="width:160px"><label for="gallery_type_id">Gallery Type</label></th>
					<td>
						<select name="gallery_type_id" id="gallery_type_id" style="min-width:200px">
							<option value="">— No type —</option>
							<?php foreach ( $gallery_types as $gt ) : ?>
								<option value="<?php echo $gt->term_id; ?>"><?php echo esc_html( $gt->name ); ?> (<?php echo $gt->count; ?>)</option>
							<?php endforeach; ?>
						</select>
						&nbsp;&nbsp;<strong>or create new:</strong>&nbsp;
						<input type="text" name="new_type_name" placeholder="e.g. Vehicle Wraps" style="width:180px">
					</td>
				</tr>
				<tr>
					<th><label for="gallery_span">Grid Span</label></th>
					<td>
						<select name="gallery_span" id="gallery_span">
							<option value="normal">Normal (1×1) — default</option>
							<option value="wide">Wide (2×1)</option>
							<option value="tall">Tall (1×2)</option>
							<option value="big">Big (2×2)</option>
						</select>
						<p class="description">You can change individual items later in the gallery list.</p>
					</td>
				</tr>
			</table>
		</div>

		<p>
			<input type="submit" name="giga_bulk_submit" class="button button-primary button-hero" value="⬆ Upload &amp; Create Gallery Items">
		</p>
	</form>
	</div>

	<script>
	(function () {
		/* ── File input → dropzone feedback ── */
		var input   = document.getElementById('giga-file-input');
		var zone    = document.getElementById('giga-dropzone');
		var counter = document.getElementById('giga-file-count');

		input.addEventListener('change', function () {
			var n = input.files.length;
			counter.textContent = n + ' image' + (n !== 1 ? 's' : '') + ' selected';
			counter.style.display = n ? '' : 'none';
			zone.style.borderColor = n ? '#2271b1' : '#aaa';
		});

		zone.addEventListener('dragover',  function (e) { e.preventDefault(); zone.style.borderColor = '#2271b1'; });
		zone.addEventListener('dragleave', function ()  { zone.style.borderColor = '#aaa'; });
		zone.addEventListener('drop',      function (e) {
			e.preventDefault();
			input.files = e.dataTransfer.files;
			input.dispatchEvent(new Event('change'));
		});

		/* ── Media Library multi-select ── */
		var attInput  = document.getElementById('giga-att-ids');
		var thumbWrap = document.getElementById('giga-thumb-preview');
		var mediaBtn  = document.getElementById('giga-open-media');

		function openMediaFrame() {
			var frame = wp.media({
				title: 'Select Gallery Images',
				multiple: true,
				library: { type: 'image' }
			});
			frame.on('select', function () {
				var sel = frame.state().get('selection');
				var ids = [], thumbs = [];
				sel.each(function (a) {
					ids.push(a.id);
					var url = a.attributes.sizes && a.attributes.sizes.thumbnail
						? a.attributes.sizes.thumbnail.url
						: a.attributes.url;
					thumbs.push('<img src="' + url + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;border:2px solid #2271b1" title="' + (a.attributes.title || '') + '">');
				});
				attInput.value = ids.join(',');
				thumbWrap.innerHTML = thumbs.join('');
			});
			frame.open();
		}

		if (mediaBtn) {
			mediaBtn.addEventListener('click', function () {
				if (typeof wp !== 'undefined' && wp.media) {
					openMediaFrame();
				} else {
					/* wp.media not ready yet — wait for it */
					var tries = 0;
					var wait = setInterval(function () {
						tries++;
						if (typeof wp !== 'undefined' && wp.media) {
							clearInterval(wait);
							openMediaFrame();
						} else if (tries > 20) {
							clearInterval(wait);
							alert('Media Library could not load. Please refresh the page and try again.');
						}
					}, 200);
				}
			});
		}
	})();
	</script>
	<?php
}

function giga_create_gallery_item( $title, $attach_id, $span, $type_id ) {
	$pid = wp_insert_post( [
		'post_title'  => $title,
		'post_type'   => 'gallery_item',
		'post_status' => 'publish',
	] );
	if ( ! $pid || is_wp_error( $pid ) ) return false;
	set_post_thumbnail( $pid, $attach_id );
	update_post_meta( $pid, '_gallery_span', $span ?: 'normal' );
	if ( $type_id ) wp_set_post_terms( $pid, [ $type_id ], 'gallery_type' );
	return $pid;
}

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

	wp_enqueue_style( 'giga-main', $uri . '/assets/css/main.css', [ 'giga-montserrat' ],
		filemtime( get_template_directory() . '/assets/css/main.css' ) );

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

	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$shop_id  = (int) wc_get_page_id( 'shop' );
		$crumbs[] = $shop_id ? esc_html( get_the_title( $shop_id ) ) : esc_html__( 'Shop', 'storecmsmart' );

	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$crumbs[] = '<a href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">' . esc_html__( 'Shop', 'storecmsmart' ) . '</a>';
		$crumbs[] = esc_html( single_term_title( '', false ) );

	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$crumbs[] = '<a href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">' . esc_html__( 'Shop', 'storecmsmart' ) . '</a>';
		$crumbs[] = esc_html( get_the_title() );
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
		'giga_util_bar_text'    => [ 'label' => __( 'Utility bar text (HTML OK)',          'storecmsmart' ), 'textarea' => true,
			'default' => 'Ordering open <strong>24 / 7</strong> · Ships nationwide' ],
		'giga_util_link1_label' => [ 'label' => __( 'Util link 1 — label',                 'storecmsmart' ), 'default' => 'FAQs' ],
		'giga_util_link1_url'   => [ 'label' => __( 'Util link 1 — URL (blank = hide)',    'storecmsmart' ), 'default' => '' ],
		'giga_util_link2_label' => [ 'label' => __( 'Util link 2 — label',                 'storecmsmart' ), 'default' => 'How-to videos' ],
		'giga_util_link2_url'   => [ 'label' => __( 'Util link 2 — URL (blank = hide)',    'storecmsmart' ), 'default' => '' ],
		'giga_util_link3_label' => [ 'label' => __( 'Util link 3 — label',                 'storecmsmart' ), 'default' => 'Track order' ],
		'giga_util_link3_url'   => [ 'label' => __( 'Util link 3 — URL (blank = hide)',    'storecmsmart' ), 'default' => '' ],
	] );

	// ── SECTION: Social Media ─────────────────────────────────────
	$wp_customize->add_section( 'giga_social', [
		'title'    => __( '📱 Social Media', 'storecmsmart' ),
		'priority' => 26,
	] );
	$register( 'giga_social', [
		'giga_social_instagram' => [ 'label' => __( 'Instagram URL (blank = hide)', 'storecmsmart' ), 'default' => '' ],
		'giga_social_facebook'  => [ 'label' => __( 'Facebook URL (blank = hide)',  'storecmsmart' ), 'default' => '' ],
		'giga_social_youtube'   => [ 'label' => __( 'YouTube URL (blank = hide)',   'storecmsmart' ), 'default' => '' ],
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

	// ── SECTION: Contact Page ─────────────────────────────────────
	$wp_customize->add_section( 'giga_contact', [
		'title'    => __( '📞 Contact Page', 'storecmsmart' ),
		'priority' => 53,
	] );
	$register( 'giga_contact', [
		'giga_contact_hero_title'     => [ 'label' => __( 'Hero heading (HTML OK, use <span class="accent"> for highlight)', 'storecmsmart' ), 'textarea' => true,
			'default' => 'Talk to us.<br><span class="accent">We actually pick up.</span>' ],
		'giga_contact_hero_desc'      => [ 'label' => __( 'Hero description',                                               'storecmsmart' ), 'textarea' => true,
			'default' => 'No chatbots. No ticket queues. Call, email, or use the form below — same team behind every order.' ],
		'giga_contact_form_shortcode' => [ 'label' => __( 'Contact form shortcode — e.g. [wpforms id="1"] (blank = use page content)', 'storecmsmart' ), 'textarea' => true, 'default' => '' ],
		'giga_contact_map_src'        => [ 'label' => __( 'Google Maps embed URL — paste src= value from Maps > Share > Embed a map (blank = hide map)', 'storecmsmart' ), 'textarea' => true, 'default' => '' ],
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

	// ── SECTION: About Page ───────────────────────────────────────
	$wp_customize->add_section( 'giga_about', [
		'title'    => __( 'ℹ About Page', 'storecmsmart' ),
		'priority' => 54,
	] );

	// SignEdge brand logo (image upload)
	$wp_customize->add_setting( 'signedge_brand_logo', [
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'signedge_brand_logo', [
		'label'       => __( 'SignEdge brand logo (in "Powered by" block)', 'storecmsmart' ),
		'description' => __( 'Logo hiển thị trong block tối trên trang About. Khuyến nghị: PNG nền trong, tối đa 360×160 px.', 'storecmsmart' ),
		'section'     => 'giga_about',
	] ) );

	// ── SECTION: Blog / Single Post ──────────────────────────────
	$wp_customize->add_section( 'giga_blog', [
		'title'    => __( '📝 Blog & Single Post', 'storecmsmart' ),
		'priority' => 55,
	] );

	$wp_customize->add_setting( 'giga_newsletter_shortcode', [
		'default'           => '',
		'sanitize_callback' => 'wp_kses_post',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'giga_newsletter_shortcode', [
		'label'       => __( 'Newsletter form shortcode', 'storecmsmart' ),
		'description' => __( 'Dán shortcode form đăng ký (WPForms, Mailchimp…). Để trống dùng form mặc định.', 'storecmsmart' ),
		'section'     => 'giga_blog',
		'type'        => 'textarea',
	] );

	$wp_customize->add_setting( 'giga_city', [
		'default'           => 'Fort Pierce, FL',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'giga_city', [
		'label'   => __( 'City / location tag (shown in article meta)', 'storecmsmart' ),
		'section' => 'giga_blog',
		'type'    => 'text',
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

add_action( 'init', function() {
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
}, 20 );

// Increase WooCommerce gallery thumbnail size (default is 100×100)
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', function( $size ) {
	return [ 'width' => 180, 'height' => 180, 'crop' => 1 ];
} );

// Wrap gallery + summary in .product-top so sticky stops at the summary bottom,
// not at the tabs/related section further down.
add_action( 'woocommerce_before_single_product_summary', function() {
	if ( is_product() ) echo '<div class="product-top">';
}, 1 );
add_action( 'woocommerce_after_single_product_summary', function() {
	if ( is_product() ) echo '</div><!-- /.product-top -->';
}, 1 );

// Single product: trigger window resize after page load so Flexslider
// recalculates gallery dimensions after our CSS has been applied.
add_action( 'wp_footer', function() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) return;
	?>
	<script>
	(function(){
		function fixGallery(){
			var gallery = document.querySelector('.single-product .woocommerce-product-gallery');
			if(!gallery) return;
			// Remove any inline width FlexSlider may have set on the viewport
			var vp = gallery.querySelector('.flex-viewport');
			if(vp){ vp.style.width=''; }
			// Dispatch resize so Flexslider/PhotoSwipe recalculate
			window.dispatchEvent(new Event('resize'));
		}
		if(document.readyState==='loading'){
			document.addEventListener('DOMContentLoaded',function(){ setTimeout(fixGallery,250); });
		} else {
			setTimeout(fixGallery,250);
		}
	})();
	</script>
	<?php
} );

// Quantity +/- buttons on cart page
add_action( 'wp_footer', function() {
	if ( ! is_cart() ) return;
	?>
	<script>
	(function(){
		function addQtyButtons(){
			document.querySelectorAll('.woocommerce table.cart .quantity:not(.qty-enhanced)').forEach(function(wrap){
				wrap.classList.add('qty-enhanced');
				var input = wrap.querySelector('input.qty');
				if(!input) return;
				var minus = document.createElement('button');
				minus.type = 'button'; minus.className = 'qty-btn qty-minus'; minus.textContent = '−';
				var plus  = document.createElement('button');
				plus.type  = 'button'; plus.className  = 'qty-btn qty-plus';  plus.textContent = '+';
				minus.addEventListener('click', function(){
					var v = parseInt(input.value)||1;
					if(v > 1){ input.value = v-1; input.dispatchEvent(new Event('change')); }
				});
				plus.addEventListener('click', function(){
					var v = parseInt(input.value)||1;
					input.value = v+1; input.dispatchEvent(new Event('change'));
				});
				wrap.insertBefore(minus, input);
				wrap.appendChild(plus);
			});
		}
		document.addEventListener('DOMContentLoaded', addQtyButtons);
		document.addEventListener('updated_cart_totals', addQtyButtons);
	})();
	</script>
	<?php
} );

/* ---------------------------------------------------------------
   START DESIGN BUTTON — redirect to /create-your-own/?product_id=X
   The plugin's #triggerDesign opens an in-page iframe popup by
   default. We intercept the click in the capture phase (before the
   plugin handler) and redirect directly to the designer URL.
--------------------------------------------------------------- */
add_action( 'wp_footer', function () {
    if ( ! is_product() ) return;
    global $post;
    $pid        = (int) $post->ID;
    $design_url = esc_url( home_url( '/create-your-own/' ) . '?product_id=' . $pid );
    ?>
    <script>
    (function () {
        var url = '<?php echo $design_url; ?>';
        function patchDesignButtons() {
            var btns = document.querySelectorAll('#triggerDesign, #startDesign, .nbdesign-button.start-design');
            btns.forEach(function (btn) {
                if (btn.dataset.sgPatched) return;
                btn.dataset.sgPatched = '1';
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    window.location.href = url;
                }, true);
            });
        }
        document.addEventListener('DOMContentLoaded', patchDesignButtons);
    })();
    </script>
    <?php
}, 99 );

/* ---------------------------------------------------------------
   EMPTY PRODUCT CATEGORY: friendly "Coming soon" block instead of
   the default "No products were found matching your selection".
--------------------------------------------------------------- */
add_action( 'wp', function () {
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found' );
		add_action( 'woocommerce_no_products_found', 'giga_category_coming_soon' );
	}
} );

function giga_category_coming_soon() {
	$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
	?>
	<div class="giga-coming-soon">
		<h2 class="giga-coming-soon__title"><?php esc_html_e( 'Coming soon', 'storecmsmart' ); ?></h2>
		<p class="giga-coming-soon__text"><?php esc_html_e( 'Products in this category are on the way. Meanwhile browse our other categories or contact us.', 'storecmsmart' ); ?></p>
		<div class="giga-coming-soon__actions">
			<a class="btn-primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse the shop', 'storecmsmart' ); ?></a>
			<a class="btn-secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'storecmsmart' ); ?></a>
		</div>
	</div>
	<?php
}

