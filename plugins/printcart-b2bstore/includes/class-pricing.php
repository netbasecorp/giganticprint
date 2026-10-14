<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Pricing {

    public static function init() {
        add_filter( 'woocommerce_get_price_html',         array( __CLASS__, 'maybe_show_tier_price' ), 10, 2 );
        add_filter( 'woocommerce_product_get_price',      array( __CLASS__, 'apply_tier_price' ),      10, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'apply_tier_price' ),      10, 2 );
        /* Bulk (quantity-based) discount applied at cart level */
        add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_bulk_discount_to_cart' ), 20 );
        /* Flag NBDesigner cart items early so the price filters never discount them */
        add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'flag_nbo_cart_item' ), 20 );
        add_filter( 'woocommerce_add_cart_item',              array( __CLASS__, 'flag_nbo_cart_item' ), 20 );
        /* Patch CMSmart/NBDesigner product-page total display */
        add_action( 'wp_footer', array( __CLASS__, 'cmsmart_total_discount_patch' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Get effective discount % for current / given user                  */
    /* Always returns max(personal tier discount, team store discount)    */
    /* ---------------------------------------------------------------- */
    public static function get_effective_discount( $user_id = null ) {
        if ( null === $user_id ) $user_id = get_current_user_id();
        if ( ! $user_id ) return 0;

        $tiers = PCS_DB::get_tiers_from_rules();

        /* Personal tier discount */
        $tier              = PCS_DB::get_customer_tier( $user_id );
        $personal_discount = isset( $tiers[ $tier ]['discount'] ) ? (float) $tiers[ $tier ]['discount'] : 0;

        /* Store/team discount — in case user is a B2B store member */
        $store_discount = self::get_team_store_discount( $user_id, $tiers );

        return max( $personal_discount, $store_discount );
    }

    /* Return the highest discount from any approved B2B store this user belongs to */
    private static function get_team_store_discount( $user_id, $tiers ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.tier FROM {$wpdb->prefix}pcs_b2b_members m
             INNER JOIN {$wpdb->prefix}pcs_b2b_stores s ON s.id = m.store_id
             WHERE m.user_id = %d AND s.status IN ('active','approved')
             ORDER BY m.joined_at DESC LIMIT 1",
            absint( $user_id )
        ) );
        if ( ! $row ) return 0;
        return isset( $tiers[ $row->tier ]['discount'] ) ? (float) $tiers[ $row->tier ]['discount'] : 0;
    }

    /* ---------------------------------------------------------------- */
    /* Apply tier price to product price filter                           */
    /* ---------------------------------------------------------------- */
    /* Plain / catalogue products only: cart items are priced exactly once by
       apply_bulk_discount_to_cart() and flagged here so this filter never
       discounts them again. */
    /* Cart product objects owned by the cart pricing (spl_object_id => object).
       The object is kept so the id cannot be reused by a different product. */
    private static $cart_priced = array();

    public static function flag_cart_product( $product ) {
        if ( is_object( $product ) ) self::$cart_priced[ spl_object_id( $product ) ] = $product;
    }

    public static function is_cart_product( $product ) {
        return is_object( $product ) && isset( self::$cart_priced[ spl_object_id( $product ) ] );
    }

    /* Flag NBDesigner cart items as soon as they are created / restored (core runs at priority 1) */
    public static function flag_nbo_cart_item( $cart_item ) {
        if ( is_array( $cart_item ) && isset( $cart_item['nbo_meta'], $cart_item['data'] ) ) {
            self::flag_cart_product( $cart_item['data'] );
        }
        return $cart_item;
    }

    /* Single source of the discounted unit price: raw * (1 - (tier + bulk) / 100), rounded.
       Pure function of $raw, so applying it again to the same raw value is idempotent. */
    public static function discounted_unit_price( $raw, $user_id = null, $qty = 1 ) {
        $raw  = (float) $raw;
        $bulk = self::get_bulk_discount_for_qty( $qty );
        $tier = ( get_option( 'pcs_apply_in_cart', '1' ) === '0' ) ? 0 : self::get_effective_discount( $user_id );
        $rate = max( 0, min( 100, (float) $tier + (float) $bulk ) );
        if ( $rate <= 0 || $raw <= 0 ) return $raw;
        return round( $raw * ( 1 - $rate / 100 ), wc_get_price_decimals() );
    }

    public static function apply_tier_price( $price, $product ) {
        if ( is_admin() && ! wp_doing_ajax() ) return $price;
        /* Setting: apply discounts in cart */
        if ( get_option( 'pcs_apply_in_cart', '1' ) === '0' ) return $price;
        /* Cart items are priced by apply_bulk_discount_to_cart (tier + bulk, once) */
        if ( self::is_cart_product( $product ) ) return $price;

        $user_id  = get_current_user_id();
        $discount = self::get_effective_discount( $user_id );
        if ( $discount <= 0 || empty( $price ) ) return $price;
        return $price * ( 1 - $discount / 100 );
    }

    /* ---------------------------------------------------------------- */
    /* Show tier price in product HTML                                    */
    /* ---------------------------------------------------------------- */
    public static function maybe_show_tier_price( $price_html, $product ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $price_html;
        }

        if ( get_option( 'pcs_show_tier_badge', '1' ) === '0' ) return $price_html;

        $user_id  = get_current_user_id();
        if ( ! $user_id ) return $price_html;

        $discount = self::get_effective_discount( $user_id );
        if ( $discount <= 0 ) return $price_html;

        $tier      = PCS_DB::get_customer_tier( $user_id );
        $tiers     = PCS_DB::get_tiers_from_rules();
        $label     = isset( $tiers[ $tier ]['label'] ) ? $tiers[ $tier ]['label'] : '';

        /* Compact badge: just the discount % */
        $badge = sprintf(
            '<span class="pcs-tier-badge" style="font-size:11px;margin-left:6px;background:#dbeafe;color:#1e40af;padding:2px 7px;border-radius:9999px;font-weight:700;vertical-align:middle;">-%d%%</span>',
            $discount
        );

        return $price_html . $badge;
    }

    /* ---------------------------------------------------------------- */
    /* Cart pricing: the ONE place tier + bulk discount is applied to     */
    /* cart items (priority 20, after core NBDesigner). Always starts     */
    /* from the RAW unit price, so repeated calculate_totals is idempotent */
    /* ---------------------------------------------------------------- */
    public static function apply_bulk_discount_to_cart( $cart ) {
        if ( is_admin() && ! wp_doing_ajax() ) return;
        if ( ! $cart || $cart->is_empty() ) return;

        $user_id = get_current_user_id();

        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            if ( ! is_object( $product ) ) continue;

            /* RAW undiscounted unit price: NBDesigner stores it in nbo_meta['price'] */
            $is_nbo = isset( $cart_item['nbo_meta']['price'] );
            $raw    = $is_nbo ? (float) $cart_item['nbo_meta']['price'] : (float) $product->get_price( 'edit' );
            if ( $raw <= 0 ) continue;

            /* From now on apply_tier_price leaves this object alone */
            self::flag_cart_product( $product );

            $new = self::discounted_unit_price( $raw, $user_id, (int) $cart_item['quantity'] );
            /* Nothing to apply: keep plain items untouched; NBDesigner items already carry raw */
            if ( ! $is_nbo && $new == $raw ) continue;
            $product->set_price( $new );
        }
    }

    /* Return the extra bulk discount % for a given quantity */
    public static function get_bulk_discount_for_qty( $qty ) {
        global $wpdb;
        /* Find the best matching rule: highest min-qty that is still <= $qty */
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT discount_value FROM {$wpdb->prefix}pcs_pricing_rules
             WHERE rule_type = 'bulk' AND status = 'active'
               AND CAST(target_value AS UNSIGNED) <= %d
             ORDER BY CAST(target_value AS UNSIGNED) DESC, priority DESC
             LIMIT 1",
            absint( $qty )
        ) );
        return $row ? (float) $row->discount_value : 0;
    }

    /* Active bulk rules as [ ['minQty' => int, 'extra' => float], ... ] (ascending minQty) */
    public static function get_bulk_rules() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT CAST(target_value AS UNSIGNED) AS min_qty, discount_value AS extra
             FROM {$wpdb->prefix}pcs_pricing_rules
             WHERE rule_type = 'bulk' AND status = 'active' AND target_value != ''
             ORDER BY CAST(target_value AS UNSIGNED) ASC"
        );
        $out = array();
        foreach ( (array) $rows as $r ) {
            $out[] = array( 'minQty' => (int) $r->min_qty, 'extra' => (float) $r->extra );
        }
        return $out;
    }

    /* ---------------------------------------------------------------- */
    /* Get all tier rules for display                                     */
    /* ---------------------------------------------------------------- */
    public static function get_tier_rules() {
        return PCS_DB::get_pricing_rules( array( 'rule_type' => 'tier', 'per_page' => 50, 'status' => 'active' ) );
    }

    /* ---------------------------------------------------------------- */
    /* CMSmart/NBDesigner product-page price patch                        */
    /* Intercepts #nbd-option-total JS display and shows discounted price */
    /* ---------------------------------------------------------------- */
    public static function cmsmart_total_discount_patch() {
        if ( ! is_product() ) return;

        $user_id  = get_current_user_id();
        if ( ! $user_id ) return;

        $discount = self::get_effective_discount( $user_id );
        if ( $discount <= 0 ) return;

        $decimals = wc_get_price_decimals();

        /* Pass active bulk rules to JS so product page can show correct combined discount */
        global $wpdb;
        $bulk_rows = $wpdb->get_results(
            "SELECT CAST(target_value AS UNSIGNED) AS min_qty, discount_value AS extra
             FROM {$wpdb->prefix}pcs_pricing_rules
             WHERE rule_type = 'bulk' AND status = 'active' AND target_value != ''
             ORDER BY CAST(target_value AS UNSIGNED) ASC"
        );
        $bulk_rules_json = wp_json_encode( array_map( function( $r ) {
            return array( 'minQty' => (int) $r->min_qty, 'extra' => (float) $r->extra );
        }, $bulk_rows ) );
        ?>
<script>
(function($) {
    var PCS_TIER_DISC = <?php echo (float) $discount; ?>;   /* tier % */
    var PCS_DECIMALS  = <?php echo (int) $decimals; ?>;
    var PCS_SYMBOL    = <?php echo wp_json_encode( html_entity_decode( get_woocommerce_currency_symbol() ) ); ?>;
    var PCS_DECSEP    = <?php echo wp_json_encode( wc_get_price_decimal_separator() ); ?>;
    var PCS_THOUSAND  = <?php echo wp_json_encode( wc_get_price_thousand_separator() ); ?>;
    var PCS_BULK_RULES =<?php echo $bulk_rules_json; ?>;  /* [{minQty:5,extra:10}, ...] */

    /* Get bulk extra % for a given quantity */
    function pcsBulkExtra(qty) {
        var best = 0;
        for (var i = 0; i < PCS_BULK_RULES.length; i++) {
            if (qty >= PCS_BULK_RULES[i].minQty && PCS_BULK_RULES[i].extra > best) {
                best = PCS_BULK_RULES[i].extra;
            }
        }
        return best;
    }

    /* Next bulk rule user could reach (for upsell hint) */
    function pcsNextBulkRule(qty) {
        var next = null;
        for (var i = 0; i < PCS_BULK_RULES.length; i++) {
            var r = PCS_BULK_RULES[i];
            if (r.minQty > qty && (!next || r.minQty < next.minQty)) next = r;
        }
        return next;
    }

    /* Total discount rate given qty */
    function pcsTotalRate(qty) {
        return (PCS_TIER_DISC + pcsBulkExtra(qty)) / 100;
    }

    var PCS_BADGE = '<span style="font-size:11px;background:#dbeafe;color:#1e40af;'
                  + 'padding:2px 6px;border-radius:9999px;margin-left:6px;font-weight:700;'
                  + 'vertical-align:middle;">-<?php echo (int) $discount; ?>%</span>';

    /* ---- NUMERIC price data from the NBO Angular scope (raw, undiscounted) ----
       The scope is never patched: the discount is applied ONCE here, to the raw
       numbers, and mirrors the cart: unit = round(raw_unit * (1 - rate)), total = unit * qty.
       Returns {total, qty} or null while the scope has no numeric total. */
    function pcsGetScopeNumbers() {
        var el = document.getElementById('nbd-option-total');
        if (!el || !window.angular) return null;
        try {
            var scope = angular.element(el).scope();
            if (!scope) return null;
            var total = parseFloat(scope.total_cart_item_price_num);
            if (isNaN(total) || total <= 0) return null;
            var qty = parseInt(scope.quantity, 10);
            if (isNaN(qty) || qty < 1) qty = 1;
            return { total: total, qty: qty };
        } catch(e) { return null; }
    }

    /* Get qty from WooCommerce standard quantity input (for simple products) */
    function pcsGetQtyFromInput() {
        var v = parseInt($('.quantity input[type="number"], input.qty, input[name="quantity"]').val()) || 1;
        return v > 0 ? v : 1;
    }

    function pcsFmt(num) {
        var parts = Math.abs(parseFloat(num)).toFixed(PCS_DECIMALS).split('.');
        var intPart = PCS_THOUSAND ? parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, PCS_THOUSAND) : parts[0];
        return (parseFloat(num) < 0 ? '-' : '') + PCS_SYMBOL + intPart + (parts.length > 1 ? PCS_DECSEP + parts[1] : '');
    }

    /* Locale-safe fallback parser (only used when the scope has no numeric total).
       Both '.' and ',' present: the last one is the decimal mark; one kind several times:
       thousands; one kind once: thousands only when exactly 3 digits follow and 1-3
       non-zero-leading digits precede ("1.234" = 1234), else decimal ("16,63" = 16.63). */
    function pcsParsePrice(text) {
        var s = String(text || '').replace(/[^0-9.,]/g, '');
        if (!/\d/.test(s)) return NaN;
        var lastDot = s.lastIndexOf('.'), lastCom = s.lastIndexOf(',');
        var dec = null;
        if (lastDot >= 0 && lastCom >= 0) {
            dec = lastDot > lastCom ? '.' : ',';
        } else if (lastDot >= 0 || lastCom >= 0) {
            var sep = lastDot >= 0 ? '.' : ',';
            var parts = s.split(sep);
            if (parts.length === 2) {
                dec = (parts[1].length === 3 && /^[1-9]\d{0,2}$/.test(parts[0])) ? null : sep;
            }
        }
        if (dec === '.') s = s.replace(/,/g, '');
        else if (dec === ',') s = s.replace(/\./g, '').replace(',', '.');
        else s = s.replace(/[.,]/g, '');
        return parseFloat(s);
    }

    /* Trim a % to at most 2 decimals ("10", "12.5") */
    function pcsPct(n) {
        return String(parseFloat((parseFloat(n) || 0).toFixed(2)));
    }

    /* ---- Single update function: reads total from top price (always reliable) ---- */
    function pcsUpdateB2B() {
        /* Detect whether CMSmart options are present.
           Simple products already have B2B price via WC filter — showing
           "Your B2B price" would double-discount. But upsell hint is still needed. */
        var hasCmsmart = document.getElementById('nbd-option-total') !== null
                      || $('tr.nbo-final-price').length > 0;

        if (!hasCmsmart) {
            $('#pcs-top-b2b, #pcs-b2b-box').remove();

            /* For simple products: show upsell hint OR applied-discount banner */
            var qty1         = pcsGetQtyFromInput();
            var next1        = pcsNextBulkRule(qty1);
            var currentBulk  = pcsBulkExtra(qty1);
            var $hint        = $('#pcs-bulk-hint-simple');

            var newHtml = '';
            if (next1) {
                /* Not yet qualifying — show upsell */
                newHtml = '<p id="pcs-bulk-hint-simple" style="margin:6px 0 10px;font-size:12px;">'
                    + '🎁 Buy <strong>' + next1.minQty + '+</strong> items for extra <strong>-' + next1.extra + '%</strong> off!'
                    + '</p>';
            } else if (currentBulk > 0) {
                /* Qualifying — show applied banner */
                var combined = PCS_TIER_DISC + currentBulk;
                newHtml = '<div id="pcs-bulk-hint-simple" style="'
                    + 'display:inline-flex;align-items:center;gap:7px;'
                    + 'background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;'
                    + 'padding:7px 14px;margin:8px 0 12px;font-size:13px;font-weight:600;color:#1e40af;">'
                    + '<span style="font-size:15px;">✅</span>'
                    + 'Bulk discount applied! Tier -' + PCS_TIER_DISC + '% + Quantity -' + currentBulk + '% = <strong>-' + combined + '% total</strong>'
                    + '</div>';
            }

            if (newHtml) {
                if ($hint.length) { $hint.replaceWith(newHtml); }
                else { $('.summary .price, .entry-summary .price').first().after(newHtml); }
            } else {
                $hint.remove();
            }
            return;
        }

        var $amount = $('.summary .price .woocommerce-Price-amount, .summary .price .amount').first();
        if (!$amount.length) return;

        /* RAW retail total for the current quantity: numeric scope values first,
           the (undiscounted) visible top price + Subtotal row only as a fallback */
        var totalPrice, qty = 1;
        var nums = pcsGetScopeNumbers();
        if (nums) {
            totalPrice = nums.total;
            qty        = nums.qty;
        } else {
            totalPrice = pcsParsePrice($amount.text());
            var $subRow = $('tr').filter(function() {
                return $(this).find('td').first().text().trim().toLowerCase().indexOf('subtotal') >= 0;
            }).first();
            if ($subRow.length) {
                var qm = $subRow.find('td').last().text().match(/(\d+)\s*items?/i);
                qty = qm ? parseInt(qm[1], 10) : 1;
            }
        }
        if (isNaN(totalPrice) || totalPrice <= 0) return;

        /* --- Combined discount, applied ONCE (same math as the cart:
               unit = round(raw_unit * (1 - rate)), total = unit * qty) --- */
        var bulkExtra = pcsBulkExtra(qty);
        var rate      = pcsTotalRate(qty);   /* (tier + bulk) / 100 */
        var b2bUnit   = parseFloat((totalPrice / qty * (1 - rate)).toFixed(PCS_DECIMALS));
        var b2bTotal  = b2bUnit * qty;

        /* --- TOP display --- */
        var topLabel = pcsFmt(b2bTotal);
        var $topWrap = $('#pcs-top-b2b');
        if ($topWrap.length) {
            $topWrap.find('.pcs-b2b-top-val').text(topLabel);
            $topWrap.find('.pcs-b2b-top-badge').text('-' + pcsPct(PCS_TIER_DISC + bulkExtra) + '%');
        } else {
            $amount.closest('p.price, .price').after(
                '<p id="pcs-top-b2b" style="margin:4px 0 12px;font-size:14px;line-height:1.8;">'
                + '<span style="color:#374151;">Your B2B price: </span>'
                + '<strong class="pcs-b2b-top-val" style="color:#2563eb;font-size:1.1em;">' + topLabel + '</strong>'
                + '<span class="pcs-b2b-top-badge" style="font-size:11px;background:#dbeafe;color:#1e40af;'
                + 'padding:2px 7px;border-radius:9999px;margin-left:6px;font-weight:700;">-' + pcsPct(PCS_TIER_DISC + bulkExtra) + '%</span>'
                + '</p>'
            );
        }

        /* --- BOTTOM box --- */
        var $final = $('tr.nbo-final-price');
        if (!$final.length) return;

        var boxLabel = pcsFmt(b2bTotal) + ' / ' + qty + ' item' + (qty > 1 ? 's' : '');

        /* Discount breakdown text */
        var discountInfo = '';
        if (bulkExtra > 0) {
            discountInfo = 'Tier -' + pcsPct(PCS_TIER_DISC) + '% + Quantity -' + pcsPct(bulkExtra) + '% = -' + pcsPct(PCS_TIER_DISC + bulkExtra) + '% total';
        }

        /* Upsell hint: tell user what bulk rule they can reach next */
        var nextRule = pcsNextBulkRule(qty);
        var upsellHtml = '';
        if (nextRule) {
            upsellHtml = '<div style="font-size:11px;color:#059669;margin-top:4px;">'
                       + '🎁 Buy ' + nextRule.minQty + '+ items for extra -' + nextRule.extra + '% off!'
                       + '</div>';
        }

        var $box = $('#pcs-b2b-box');
        if ($box.length) {
            $box.find('.pcs-b2b-total').text(boxLabel);
            $box.find('.pcs-b2b-disc-info').text(discountInfo);
            $box.find('.pcs-b2b-upsell').html(upsellHtml);
        } else {
            $final.closest('table').after(
                '<div id="pcs-b2b-box" style="background:#eff6ff;border:1px solid #bfdbfe;'
                + 'border-top:2px solid #2563eb;padding:10px 14px;margin-top:1px;font-size:14px;'
                + 'width:100%;box-sizing:border-box;">'
                + '<div style="display:flex;justify-content:space-between;align-items:center;">'
                +   '<b style="color:#1e40af;">💙 Your B2B price</b>'
                +   '<strong class="pcs-b2b-total" style="color:#2563eb;font-size:1.08em;">' + boxLabel + '</strong>'
                + '</div>'
                + (discountInfo ? '<div class="pcs-b2b-disc-info" style="font-size:11px;color:#6b7280;margin-top:3px;text-align:right;">' + discountInfo + '</div>' : '<div class="pcs-b2b-disc-info"></div>')
                + '<div class="pcs-b2b-upsell">' + upsellHtml + '</div>'
                + '</div>'
            );
        }
    }

    var pcsDebounce;
    function pcsScheduleRefresh(delay) {
        clearTimeout(pcsDebounce);
        pcsDebounce = setTimeout(pcsUpdateB2B, delay || 200);
    }

    $(document).ready(function() {
        /* Poll until Angular app is ready */
        var attempts = 0;
        var poll = setInterval(function() {
            if (pcsGetScopeNumbers() || ++attempts > 50) {
                clearInterval(poll);
                pcsScheduleRefresh(400);
            }
        }, 100);

        /* ---- MutationObserver on .summary .price ----
           This is the ONLY reliable trigger: Angular always updates this
           when options or qty change → fires for BOTH cases */
        if (window.MutationObserver) {
            var topPrice = document.querySelector('.summary .price');
            if (topPrice) {
                new MutationObserver(function() {
                    pcsScheduleRefresh(200);
                }).observe(topPrice, { childList: true, subtree: true, characterData: true });
            }
        }

        /* Fallback events */
        $(document).on('change.nbo found_variation woocommerce_variation_has_changed', function() {
            pcsScheduleRefresh(300);
        });
        $(document).on('change input keyup', 'input[type="number"]', function() {
            pcsScheduleRefresh(400);
        });
        /* Watch standard WC quantity input (simple products — upsell hint) */
        $(document).on('change input', '.quantity input.qty, .quantity input[type="number"]', function() {
            pcsScheduleRefresh(300);
        });
    });
})(jQuery);
</script>
        <?php
    }
}
