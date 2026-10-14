<?php
/**
 * Plugin Name: CMSmart UI Override
 * Description: Override the printing option UI of the CMSmart plugin without modifying the original plugin files.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: cmsmart-ui-override
 */

defined( 'ABSPATH' ) || exit;

define( 'CMSMART_UI_OVERRIDE_PATH', plugin_dir_path( __FILE__ ) );
define( 'CMSMART_UI_OVERRIDE_URL',  plugin_dir_url( __FILE__ ) );

class Cmsmart_UI_Override {

    public function __construct() {
        // Inject theme-{slug} body class so CSS layout rules can be scoped per theme
        add_filter( 'body_class', array( $this, 'add_theme_body_class' ) );

        // Run after all plugins are loaded to ensure CMSmart has already registered its scripts/styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );

        // Add custom classes to each field wrapper for easier CSS targeting
        add_filter( 'nbo_field_class', array( $this, 'add_field_class' ), 10, 2 );

        // Hook before/after the option fields block
        add_action( 'nbo_before_fields', array( $this, 'before_fields' ) );
        add_action( 'nbo_after_fields',  array( $this, 'after_fields' ) );

        // Capture swatch option prices for client-side injection
        add_filter( 'nbo_product_options', array( $this, 'capture_swatch_prices' ), 99, 2 );
        add_action( 'wp_footer', array( $this, 'swatch_price_js' ), 25 );

        // Replace default WooCommerce rating with compact custom rating row
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
        add_action( 'woocommerce_single_product_summary', array( $this, 'custom_product_rating' ), 10 );
    }

    /**
     * Enqueue override CSS, JS, and web fonts.
     */
    public function add_theme_body_class( $classes ) {
        $slug = sanitize_html_class( get_template() );
        if ( $slug && ! in_array( 'theme-' . $slug, $classes, true ) ) {
            $classes[] = 'theme-' . $slug;
        }
        return $classes;
    }

    public function enqueue_assets() {
        if ( ! is_product() ) {
            return;
        }

        // Google Fonts: Plus Jakarta Sans (display), Inter (body), JetBrains Mono (numbers)
        wp_enqueue_style(
            'cui-google-fonts',
            'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap',
            array(),
            null
        );

        wp_enqueue_style(
            'cmsmart-ui-override',
            CMSMART_UI_OVERRIDE_URL . 'assets/css/override.css',
            array( 'cui-google-fonts' ),
            '1.5.2'
        );

        wp_enqueue_script(
            'cmsmart-ui-override',
            CMSMART_UI_OVERRIDE_URL . 'assets/js/override.js',
            array( 'jquery' ),
            '1.5.2',
            true
        );
    }

    /**
     * Append type-based classes to each field wrapper for CSS targeting.
     */
    public function add_field_class( $class, $field_data ) {
        $class .= ' cui-field';

        if ( ! empty( $field_data['appearance']['display_type'] ) ) {
            $class .= ' cui-field--' . sanitize_html_class( $field_data['appearance']['display_type'] );
        }

        return $class;
    }

    /**
     * Output HTML injected before the option fields.
     * This fires inside .nbo-fields-wrapper which is inside ng-controller="optionCtrl",
     * so Angular expressions and directives work here.
     */
    public function before_fields() {
        $currency_symbol = get_woocommerce_currency_symbol();
        ?>
        <div class="cui-pre-field">

            <?php /* Price showcase — ng-bind-html reads the Angular scope directly */ ?>
            <div class="cui-price-showcase">
                <div class="cui-price-left">
                    <div class="cui-price-eyebrow"><?php esc_html_e( 'Total Price', 'cmsmart-ui-override' ); ?></div>
                    <div class="cui-price-amount">
                        <span class="cui-price-value"
                              ng-bind-html="total_cart_price | to_trusted"
                              ng-show="total_cart_price"></span>
                        <span class="cui-price-placeholder"
                              ng-show="!total_cart_price">
                            <?php echo esc_html( $currency_symbol ); ?>--
                        </span>
                    </div>
                    <div class="cui-price-sub">
                        <span ng-show="_qty > 1">{{_qty}} <?php esc_html_e( 'items', 'cmsmart-ui-override' ); ?></span>
                        <span ng-show="!_qty || _qty <= 1"><?php esc_html_e( '1 item', 'cmsmart-ui-override' ); ?></span>
                    </div>
                </div>
                <div class="cui-price-right">
                    <div class="cui-price-per-item" ng-show="total_cart_item_price_num">
                        <div class="cui-per-item-label"><?php esc_html_e( 'per item', 'cmsmart-ui-override' ); ?></div>
                        <div class="cui-per-item-value" ng-bind-html="final_price | to_trusted"></div>
                    </div>
                    <div class="cui-qty-badge" ng-show="_qty > 1">
                        ×<span>{{_qty}}</span>
                    </div>
                </div>
            </div>


        </div>
        <?php
    }

    /**
     * Category breadcrumb row above the product title.
     * Finds the deepest category the product belongs to, then shows at most
     * "PARENT · CHILD" (2 levels).  Never dumps all categories.
     */
    public function product_category_label() {
        global $product;
        if ( ! $product ) return;

        $is_bestseller = has_term( 'bestseller', 'product_tag', $product->get_id() );
        $cat_ids       = $product->get_category_ids();
        $path          = array();

        if ( ! empty( $cat_ids ) ) {
            // Pick whichever assigned category sits deepest in the hierarchy
            $deepest_id = null;
            $max_depth  = -1;
            foreach ( $cat_ids as $id ) {
                $depth = count( get_ancestors( $id, 'product_cat' ) );
                if ( $depth > $max_depth ) {
                    $max_depth  = $depth;
                    $deepest_id = $id;
                }
            }

            if ( $deepest_id ) {
                $term = get_term( $deepest_id, 'product_cat' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $ancestors = get_ancestors( $deepest_id, 'product_cat' );
                    // get_ancestors() returns [immediate-parent, grandparent, …]
                    if ( ! empty( $ancestors ) ) {
                        $parent = get_term( $ancestors[0], 'product_cat' );
                        if ( $parent && ! is_wp_error( $parent ) ) {
                            $path[] = esc_html( strtoupper( $parent->name ) );
                        }
                    }
                    $path[] = esc_html( strtoupper( $term->name ) );
                }
            }
        }

        if ( empty( $path ) && ! $is_bestseller ) return;

        echo '<div class="cui-product-meta">';
        if ( ! empty( $path ) ) {
            echo '<span class="cui-product-cats">' . implode( ' · ', $path ) . '</span>';
        }
        if ( $is_bestseller ) {
            echo '<span class="cui-product-badge cui-product-badge--bestseller">BESTSELLER</span>';
        }
        echo '</div>';
    }

    /**
     * Compact rating row: ★★★★★ 4.8 · 412 reviews
     * Replaces the default woocommerce_template_single_rating output.
     */
    public function custom_product_rating() {
        global $product;
        if ( ! $product || ! comments_open() ) return;

        $count  = $product->get_rating_count();
        $rating = round( floatval( $product->get_average_rating() ), 1 );

        if ( $count === 0 ) {
            echo '<div class="cui-product-rating cui-product-rating--empty">';
            echo '<a href="#reviews">' . esc_html__( 'Be the first to review', 'cmsmart-ui-override' ) . '</a>';
            echo '</div>';
            return;
        }

        $pct = ( $rating / 5 ) * 100;

        echo '<div class="cui-product-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
        echo '<span class="cui-stars" aria-label="' . esc_attr( $rating . ' out of 5' ) . '" style="--pct:' . esc_attr( $pct ) . '%"></span>';
        echo '<span class="cui-rating-score" itemprop="ratingValue">' . esc_html( number_format( $rating, 1 ) ) . '</span>';
        echo '<span class="cui-rating-sep" aria-hidden="true">·</span>';
        echo '<a href="#reviews" class="cui-rating-count"><span itemprop="reviewCount">' . esc_html( number_format( $count ) ) . '</span> ' . ( $count === 1 ? esc_html__( 'review', 'cmsmart-ui-override' ) : esc_html__( 'reviews', 'cmsmart-ui-override' ) ) . '</a>';
        echo '</div>';
    }

    /**
     * Output HTML injected after the option fields.
     */
    public function after_fields() {
        ?>
        <div class="cui-post-field">

            <?php /* Trust / guarantee row */ ?>
            <div class="cui-guarantee-row">
                <div class="cui-guarantee-item">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <?php esc_html_e( 'Quality Guaranteed', 'cmsmart-ui-override' ); ?>
                </div>
                <div class="cui-guarantee-item">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <?php esc_html_e( 'Fast Delivery', 'cmsmart-ui-override' ); ?>
                </div>
                <div class="cui-guarantee-item">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <?php esc_html_e( 'Easy Returns', 'cmsmart-ui-override' ); ?>
                </div>
            </div>

        </div>
        <?php
    }
    /**
     * Collect additional_price per option for each swatch field.
     * Stored in a property so swatch_price_js() can output it to the page.
     */
    private $swatch_prices = array();
    private $qty_breaks    = array();

    public function capture_swatch_prices( $options, $product_id ) {
        if ( empty( $options['fields'] ) || ! is_array( $options['fields'] ) ) {
            return $options;
        }

        foreach ( $options['fields'] as $field ) {
            if ( empty( $field['appearance']['display_type'] ) ) continue;
            $dtype = $field['appearance']['display_type'];
            // Capture both swatch ('s') and label ('l') fields
            if ( $dtype !== 's' && $dtype !== 'l' ) continue;
            if ( empty( $field['general']['attributes']['options'] ) ) continue;

            $per_key = array();
            foreach ( $field['general']['attributes']['options'] as $key => $opt ) {
                // CMSmart stores per-option price as price[0] (array, index 0 = base price)
                $price = 0;
                if ( isset( $opt['price'] ) && is_array( $opt['price'] ) && isset( $opt['price'][0] ) && $opt['price'][0] !== '' ) {
                    $price = floatval( $opt['price'][0] );
                }
                $per_key[ $key ] = array( 'price' => $price );
            }
            $this->swatch_prices[ $field['id'] ] = array(
                't'    => $dtype,  // 's' = swatch card, 'l' = label chip
                'opts' => $per_key,
            );
        }

        // Capture quantity breaks for Select box display type
        if (
            isset( $options['quantity_type'] ) &&
            $options['quantity_type'] === 's' &&
            ! empty( $options['quantity_breaks'] )
        ) {
            $product    = wc_get_product( $product_id );
            $base_price = $product ? floatval( $product->get_price() ) : 0;

            $this->qty_breaks = array(
                'dis_t'  => isset( $options['quantity_discount_type'] ) ? $options['quantity_discount_type'] : 'p',
                'base'   => $base_price,
                'breaks' => array_values( $options['quantity_breaks'] ),
            );
        }

        return $options;
    }

    /**
     * Output a small inline script that appends price labels to swatch cards.
     * Runs after wp_footer so jQuery is guaranteed available.
     */
    public function swatch_price_js() {
        if ( ! is_product() ) return;
        if ( empty( $this->swatch_prices ) && empty( $this->qty_breaks ) ) return;

        $json     = ! empty( $this->swatch_prices ) ? wp_json_encode( $this->swatch_prices ) : '{}';
        $qty_json = ! empty( $this->qty_breaks )    ? wp_json_encode( $this->qty_breaks )    : 'null';
        $symbol   = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
        $decimals = intval( wc_get_price_decimals() );
        ?>
        <script>
        (function($){
            var data = <?php echo $json; ?>;
            var qtd  = <?php echo $qty_json; ?>;
            var sym  = <?php echo wp_json_encode( $symbol ); ?>;
            var dec  = <?php echo $decimals; ?>;

            function fmtSwatch(p) {
                if (p === 0) return '<span class="cui-sw-price cui-sw-price--free">Free</span>';
                var sign = p > 0 ? '+' : '-';
                return '<span class="cui-sw-price">' + sign + sym + Math.abs(p).toFixed(dec) + '</span>';
            }

            function fmtLabel(p) {
                if (p === 0) return '<span class="cui-label-price cui-label-price--free">Free</span>';
                var sign = p > 0 ? '+' : '-';
                return '<span class="cui-label-price">' + sign + sym + Math.abs(p).toFixed(dec) + '</span>';
            }

            function inject() {
                $.each(data, function(fid, field) {
                    var opts = field.opts;
                    if (field.t === 's') {
                        // Swatch card: inject price block into .nbd-swatch-description
                        $('[data-id="' + fid + '"]').find('.nbd-swatch-label-wrap').each(function() {
                            var $w = $(this);
                            var rv = $w.find('input[type="radio"]').val();
                            if (rv === undefined || !opts[rv]) return;
                            var $d = $w.find('.nbd-swatch-description');
                            if ($d.length && !$d.find('.cui-sw-price').length) {
                                $d.append(fmtSwatch(opts[rv].price));
                            }
                        });
                    } else if (field.t === 'l') {
                        // Label chip: append price span inside each label
                        $('[data-id="' + fid + '"]').find('.nbd-label-wrap input[type="radio"]').each(function() {
                            var rv = $(this).val();
                            if (rv === undefined || !opts[rv]) return;
                            var $label = $(this).next('label.nbd-label');
                            if ($label.length && !$label.find('.cui-label-price').length) {
                                $label.append(fmtLabel(opts[rv].price));
                            }
                        });
                    }
                });
            }

            function injectQty(baseOverride) {
                if (!qtd || !qtd.breaks || !qtd.breaks.length) return;
                var base   = (baseOverride > 0) ? baseOverride : qtd.base;
                var dis_t  = qtd.dis_t;
                var breaks = qtd.breaks;
                var $wrap  = $('#nbo-quantity-option-wrap .nbd-label-wrap');
                if (!$wrap.length) return;

                breaks.forEach(function(brk, i) {
                    var $input = $wrap.find('input[type="radio"][value="' + brk.val + '"]');
                    var $label = $input.next('label.nbd-label');
                    if (!$label.length) return;

                    var alreadyBuilt = $label.hasClass('cui-qty-card');
                    var perCard = base;
                    var savePct = 0;
                    var dis     = parseFloat(brk.dis);

                    if (!isNaN(dis) && dis > 0) {
                        if (dis_t === 'p') {
                            perCard = base * (1 - dis / 100);
                            savePct = Math.round(dis);
                        } else {
                            perCard = base - dis;
                            savePct = base > 0 ? Math.round(dis / base * 100) : 0;
                        }
                    }

                    if (!alreadyBuilt) {
                        // First build: full card HTML
                        var popular = (breaks.length >= 3 && i === 1);
                        var html = popular ? '<span class="cui-qty-popular">MOST POPULAR</span>' : '';
                        html += '<span class="cui-qty-num">' + brk.val + '</span>';
                        html += '<span class="cui-qty-unit">items</span>';
                        if (base > 0) {
                            html += '<span class="cui-qty-price">' + sym + perCard.toFixed(dec) + ' / card</span>';
                        }
                        if (savePct > 0) {
                            html += '<span class="cui-qty-save">SAVE ' + savePct + '%</span>';
                        }
                        $label.addClass('cui-qty-card').html(html);
                    } else if (base > 0) {
                        // Card already built — just update/inject price span
                        var $price = $label.find('.cui-qty-price');
                        var priceText = sym + perCard.toFixed(dec) + ' / card';
                        if ($price.length) {
                            $price.text(priceText);
                        } else {
                            $label.find('.cui-qty-unit').after('<span class="cui-qty-price">' + priceText + '</span>');
                        }
                    }
                });
            }

            // When WooCommerce price = 0, fall back to Angular per-item price for qty cards
            function watchPerItemPrice() {
                if (!qtd || qtd.base > 0) return;
                var $el = $('.cui-per-item-value');
                if (!$el.length) return;
                function parsePerItem() {
                    var clean = $el.text().replace(/[^0-9.,]/g, '');
                    if (clean.indexOf(',') > -1 && clean.indexOf('.') === -1) clean = clean.replace(',', '.');
                    return parseFloat(clean) || 0;
                }
                var obs = new MutationObserver(function() {
                    var p = parsePerItem();
                    if (p > 0) injectQty(p);
                });
                obs.observe($el[0], { childList: true, subtree: true, characterData: true });
                var init = parsePerItem();
                if (init > 0) injectQty(init);
            }

            function addCustomQtyCard() {
                if (!qtd || !qtd.breaks || !qtd.breaks.length) return;
                var $wrap = $('#nbo-quantity-option-wrap .nbd-label-wrap');
                if (!$wrap.length || $wrap.find('.cui-qty-custom-card').length) return;

                var $card = $(
                    '<div class="cui-qty-custom-card">' +
                    '<span class="cui-qty-num">···</span>' +
                    '<span class="cui-qty-unit">Custom qty</span>' +
                    '<input type="number" class="cui-qty-custom-input" min="1" step="1" placeholder="e.g. 75">' +
                    '</div>'
                );
                $wrap.append($card);

                var $input = $card.find('.cui-qty-custom-input');
                var qtyTimer;

                $card.on('click', function() {
                    $wrap.find('input[type="radio"]').prop('checked', false).trigger('change');
                    $card.addClass('is-active');
                    $input.show().focus();
                });

                $input.on('click', function(e) { e.stopPropagation(); });

                $input.on('input', function() {
                    var val = parseInt($(this).val(), 10);
                    if (!val || val < 1) return;
                    clearTimeout(qtyTimer);
                    qtyTimer = setTimeout(function() {
                        try {
                            var el = $('.nbdesigner_frontend_container')[0];
                            if (el && window.angular) {
                                var scope = angular.element(el).scope();
                                if (scope) {
                                    scope.$apply(function() {
                                        scope.quantity = val;
                                        if (scope.custom_qty) scope.custom_qty.value = val;
                                    });
                                    if (typeof scope.change_quantity === 'function') {
                                        scope.change_quantity();
                                        return;
                                    }
                                }
                            }
                        } catch(e) {}
                        // Fallback: trigger WooCommerce quantity change directly
                        jQuery('input[name="quantity"]').val(val).trigger('change.nbo').trigger('change');
                    }, 300);
                });

                $wrap.on('change', 'input[type="radio"]', function() {
                    $card.removeClass('is-active');
                    $input.hide().val('');
                });
            }

            $(document).ready(function(){
                inject(); injectQty(); watchPerItemPrice(); addCustomQtyCard();
                setTimeout(function(){ inject(); injectQty(); watchPerItemPrice(); addCustomQtyCard(); }, 700);
            });
        })(jQuery);
        </script>
        <?php
    }
}

new Cmsmart_UI_Override();
