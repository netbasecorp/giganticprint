/**
 * CMSmart UI Override – frontend JS
 */
jQuery( function ( $ ) {
    'use strict';

    // ── Design card config ───────────────────────────────────────────────────
    var designCardCfg = [
        { id: '#startDesign',  cls: 'cui-btn-icon--design',   desc: 'Templates · fonts · clipart' },
        { id: '#startUpload',  cls: 'cui-btn-icon--upload',   desc: 'PDF, AI, PNG · 25MB'         },
        { id: '#useTemplate',  cls: 'cui-btn-icon--template', desc: 'Browse our designs'           }
    ];

    function buildDesignCards() {
        var $actionsWrap = $( '.nbd-actions-wrap' );
        if ( ! $actionsWrap.length ) return;

        var count = 0;
        designCardCfg.forEach( function ( cfg ) {
            var $btn = $( cfg.id );
            if ( ! $btn.length ) return;
            count++;
            if ( $btn.hasClass( 'cui-card-btn' ) ) return;
            $btn.addClass( 'cui-card-btn' );
            $btn.find( 'svg' ).wrap( '<span class="cui-btn-icon ' + cfg.cls + '"></span>' );
            $btn.append(
                '<span class="cui-btn-desc">' + cfg.desc + '</span>' +
                '<span class="cui-btn-free">Free</span>'
            );
        } );

        if ( count >= 3 && ! $actionsWrap.hasClass( 'cui-3cols' ) ) {
            $actionsWrap.addClass( 'cui-3cols' );
        }
    }

    // ── Get the selected option label HTML for any field type ───────────────
    // Returns HTML string (preserving .cui-sw-price spans for styling).
    function getSelectedHtml( $field ) {
        // Swatch with label (swatch-circle or default swatch)
        var $swatchChecked = $field.find( '.nbd-swatch-label-wrap input[type="radio"]:checked' );
        if ( $swatchChecked.length ) {
            // Swatches: name only (no price badge shown)
            return $swatchChecked
                .closest( '.nbd-swatch-label-wrap' )
                .find( '.nbd-swatch-title b' )
                .text().trim();
        }

        // Label / chip type — preserve .cui-sw-price span so CSS can style it
        var $labelChecked = $field.find( '.nbd-label-wrap input[type="radio"]:checked' );
        if ( $labelChecked.length ) {
            var $lbl = $labelChecked.next( 'label.nbd-label' );
            if ( $lbl.length ) {
                return $lbl.html();
            }
        }

        // Radio type
        var $radioChecked = $field.find( '.nbd-radio input[type="radio"]:checked' );
        if ( $radioChecked.length ) {
            return $radioChecked.next( 'label' ).html() || '';
        }

        // Dropdown type
        var $select = $field.find( 'select.nbo-dropdown' );
        if ( $select.length ) {
            return $select.find( 'option:selected' ).text().trim();
        }

        return '';
    }

    // ── Enhance ALL field headers: selected name on the right ────────────────
    function enhanceAllFieldHeaders() {
        $( '.nbd-option-wrapper .nbd-option-field' ).each( function () {
            var $field  = $( this );
            var $header = $field.find( '.nbd-field-header' ).first();
            if ( ! $header.length ) return;

            // Swatch-circle gets a count badge too — handle separately below
            if ( $field.hasClass( 'swatch-circle' ) ) {
                // Inject count badge once
                var $wraps = $field.find( '.nbd-swatch-label-wrap' );
                if ( $wraps.length && ! $header.find( '.cui-swatch-count' ).length ) {
                    $header.find( 'label' ).first().after(
                        '<span class="cui-swatch-count">' + $wraps.length + ' COLORS</span>'
                    );
                }
            }

            // Skip quantity field — cards already show full pricing detail
            if ( $field.find( '.cui-qty-card' ).length || $field.closest( '#nbo-quantity-option-wrap' ).length ) return;

            // Inject selected-value span once (all field types)
            if ( ! $header.find( '.cui-selected-val' ).length ) {
                $header.append( '<span class="cui-selected-val"></span>' );
            }

            function syncVal() {
                $header.find( '.cui-selected-val' ).html( getSelectedHtml( $field ) );
            }

            syncVal();
            $field.off( 'change.cuiVal' ).on( 'change.cuiVal', 'input[type="radio"], select', function () {
                setTimeout( syncVal, 0 );
            } );
        } );
    }

    // Run as soon as DOM is ready, then again after Angular/CMSmart may have rendered
    $( document ).ready( function () {
        buildDesignCards();
        enhanceAllFieldHeaders();
        setTimeout( buildDesignCards, 600 );
        setTimeout( enhanceAllFieldHeaders, 800 );
    } );

    // ── Everything below requires CMSmart print options ──────────────────────
    var $wrapper = $( '.nbd-option-wrapper' );
    if ( ! $wrapper.length ) return;

    // Hide WooCommerce qty input — CMSmart quantity cards handle quantity selection
    $( 'form.cart .quantity' ).hide();

    // ── Gallery pulse animation when swatch option changes ───────────────────
    var $flexslider = $( '.woocommerce-product-gallery .flexslider' );
    var galleryTimer;

    $wrapper.on( 'change', 'input[type="radio"]', function () {
        if ( ! $flexslider.length ) return;
        clearTimeout( galleryTimer );
        $flexslider.removeClass( 'cui-option-change' );
        void $flexslider[0].offsetWidth;
        $flexslider.addClass( 'cui-option-change' );
        galleryTimer = setTimeout( function () {
            $flexslider.removeClass( 'cui-option-change' );
        }, 520 );
    } );

    // ── Inject total price into ADD TO CART button ───────────────────────────
    var $cartBtn = $( 'form.cart .single_add_to_cart_button' );
    if ( $cartBtn.length && ! $cartBtn.find( '.cui-cart-price' ).length ) {
        $cartBtn.append(
            '<span class="cui-cart-sep">|</span>' +
            '<span class="cui-cart-price"></span>'
        );
    }
    var $cartBtnPrice = $cartBtn.find( '.cui-cart-price' );

    function syncCartPrice( html ) {
        if ( html && html.trim() && html.trim() !== '$--' ) {
            $cartBtnPrice.html( html );
        }
    }

    // ── Flash animation on Angular-driven price updates ──────────────────────
    var $priceEl  = $wrapper.find( '.cui-price-value' );
    var lastPrice = '';

    if ( $priceEl.length ) {
        var priceObserver = new MutationObserver( function () {
            var current = $priceEl.text();
            if ( current !== lastPrice && current.trim() !== '' ) {
                lastPrice = current;
                $priceEl.addClass( 'cui-price-flash' );
                setTimeout( function () {
                    $priceEl.removeClass( 'cui-price-flash' );
                }, 600 );
            }
            syncCartPrice( $priceEl.html() );
        } );

        priceObserver.observe( $priceEl[0], {
            childList:     true,
            subtree:       true,
            characterData: true
        } );
    }
} );
