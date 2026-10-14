/**
 * Signedge Designer UI — mobile.js
 *
 * Phone order flow (viewport <= 768 px at load): a single-column container
 * #sg-m laid over the designer with
 *   1. Your artwork  - upload button, upload progress, preview + measurements
 *   2. Size          - the existing #sg-artbar (moved, not copied)
 *   3. Options       - the NBO options panel #nbo-options-wrap (moved)
 *   sticky bottom bar #sg-m-bar - live price + Add to Cart (moved).
 * The real stage/canvas stays in the DOM (shifted off-screen with a transform,
 * so fabric keeps real dimensions). "Advanced editor" reveals the core mobile
 * editor again. Does nothing above 768 px.
 *
 * Needs window.SG_DUI_INT from override.js (loaded before this file).
 */
(function () {
    'use strict';

    if (!window.matchMedia || !window.matchMedia('(max-width: 768px)').matches) return;

    var TICK_MS = 500;
    var S = {
        on: false,        // quick-order UI shown
        built: false,
        timer: null,
        n: 0,
        sig: '',          // last seen preview signature
        stableSig: '',    // signature last rendered
        pulseTimer: null
    };
    var root = null;

    window.SG_DUI_M = { active: false, focusUpload: focusUpload };

    function $(id) { return document.getElementById(id); }

    function onReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    onReady(function () {
        document.documentElement.classList.add('sg-m-html');
        start();
    });

    /* ---------------------------------------------------------------- build */
    function build() {
        if (S.built) return;
        root = document.createElement('div');
        root.id = 'sg-m';
        root.innerHTML =
            '<header class="sg-m-head"><h1 class="sg-m-title">Order your print</h1></header>' +

            '<section class="sg-m-sec" id="sg-m-sec-art">' +
                '<h2 class="sg-m-h">1. Your artwork</h2>' +
                '<button type="button" id="sg-m-upload" class="sg-m-btn sg-m-btn-primary">Upload your artwork</button>' +
                '<div id="sg-m-up-slot"></div>' +
                '<div id="sg-m-preview-wrap">' +
                    '<div id="sg-m-dim-w" class="sg-m-dim sg-m-dim-w"></div>' +
                    '<div class="sg-m-prow">' +
                        '<div id="sg-m-dim-h" class="sg-m-dim sg-m-dim-h"></div>' +
                        '<div id="sg-m-preview">' +
                            '<img alt="Artwork preview">' +
                            '<p id="sg-m-empty">Upload your artwork to see a preview</p>' +
                        '</div>' +
                    '</div>' +
                    '<p id="sg-m-measure" class="sg-m-measure"></p>' +
                '</div>' +
            '</section>' +

            '<section class="sg-m-sec" id="sg-m-sec-size">' +
                '<h2 class="sg-m-h">2. Size</h2>' +
                '<div id="sg-m-size-slot"></div>' +
            '</section>' +

            '<section class="sg-m-sec" id="sg-m-sec-opts">' +
                '<h2 class="sg-m-h">3. Options</h2>' +
                '<p id="sg-m-opts-loading" class="sg-m-note">Loading options...</p>' +
                '<div id="sg-m-opts-slot"></div>' +
            '</section>' +

            '<p class="sg-m-adv-row"><button type="button" id="sg-m-adv" class="sg-m-link">Advanced editor</button></p>' +

            '<div id="sg-m-bar">' +
                '<div class="sg-m-price"><span class="sg-m-price-label">Your price</span><span id="sg-m-price-slot"></span><span class="sg-m-price-unit">per item</span></div>' +
                '<div class="sg-m-cta" id="sg-m-cta"></div>' +
            '</div>';
        document.body.appendChild(root);

        var back = document.createElement('button');
        back.type = 'button';
        back.id = 'sg-m-back';
        back.className = 'sg-m-btn sg-m-btn-primary';
        back.textContent = 'Back to quick order';
        document.body.appendChild(back);

        $('sg-m-upload').addEventListener('click', function () {
            try {
                if (window.SG_DUI_INT) window.SG_DUI_INT.openPhotoUpload();
            } catch (e) { /* upload hook must never throw into the page */ }
        });
        $('sg-m-adv').addEventListener('click', stop);
        back.addEventListener('click', start);
        S.built = true;
    }

    /* ----------------------------------------------------------- start/stop */
    function start() {
        try { build(); } catch (e) { return; }
        S.on = true;
        window.SG_DUI_M.active = true;
        document.body.classList.add('sg-m-on');
        document.body.classList.remove('sg-m-adv');
        if (!S.timer) S.timer = setInterval(tick, TICK_MS);
        tick();
    }

    function stop() {
        S.on = false;
        window.SG_DUI_M.active = false;
        document.body.classList.remove('sg-m-on');
        document.body.classList.add('sg-m-adv');
        clearInterval(S.timer);
        S.timer = null;
        clearTimeout(S.pulseTimer);
        S.pulseTimer = null;
        try { window.dispatchEvent(new Event('resize')); } catch (e) { /* old browser */ }
    }

    /* ----------------------------------------------------------------- tick */
    function tick() {
        if (!S.on) return;
        try {
            var INT = window.SG_DUI_INT;
            var ws = document.querySelector('.nbd-workspace');
            if (INT && ws && !document.querySelector('.sg-summary')) INT.injectSummaryPanel(ws);
            adopt();
            sync(INT);
            if ((++S.n % 2) === 0 && INT) INT.updatePrice();
        } catch (e) { /* poll must never throw */ }
    }

    // Move (not copy) the live nodes of the desktop skin into #sg-m.
    function move(el, slot, afterEl) {
        if (!el || !slot || el.parentNode === slot) return;
        if (afterEl && afterEl.parentNode === slot) slot.insertBefore(el, afterEl.nextSibling);
        else slot.appendChild(el);
    }

    function adopt() {
        var btn = $('sg-add-to-cart-btn');
        if (btn) move(btn, $('sg-m-cta'));
        var price = $('sg-total-price');
        if (price) move(price, $('sg-m-price-slot'));

        var bar = $('sg-artbar');
        if (bar) {
            if (bar.parentNode !== $('sg-m-size-slot')) {
                // positionArtBars() may have left inline margins/max-width
                bar.style.removeProperty('margin-left');
                bar.style.removeProperty('margin-right');
                bar.style.removeProperty('max-width');
            }
            move(bar, $('sg-m-size-slot'));
        }
        var low = $('sg-lowres-warning');
        if (low) move(low, $('sg-m-size-slot'), bar);
        var up = $('sg-upload-progress');
        if (up) move(up, $('sg-m-up-slot'));

        var wrap = $('nbo-options-wrap');
        if (wrap && !root.contains(wrap)) {
            move(wrap, $('sg-m-opts-slot'));
        }
        var loading = $('sg-m-opts-loading');
        if (loading && wrap && root.contains(wrap) && wrap.children.length > 0) loading.style.display = 'none';
    }

    /* ----------------------------------------------------------------- sync */
    function sync(INT) {
        var scope = INT ? INT.getDesignScope() : null;
        var stage = scope && scope.stages && scope.stages[scope.currentStage];
        var canvas = stage && stage.canvas;
        var item = null;
        try {
            item = canvas && INT.getArtItem(canvas);
        } catch (e) { /* canvas being rebuilt */ }
        // only a real art image object counts (not any stray canvas object)
        var hasArt = !!item;
        var bar = $('sg-artbar');
        var barShown = !!(bar && bar.classList.contains('is-visible'));

        root.classList.toggle('sg-m-has-art', hasArt);
        root.classList.toggle('sg-m-has-size', hasArt && barShown);
        var upBtn = $('sg-m-upload');
        var label = hasArt ? 'Replace artwork' : 'Upload your artwork';
        if (upBtn.textContent !== label) upBtn.textContent = label;

        // measurements, mirrored from the art bar
        var actual = $('sg-art-actual');
        var raw = actual ? actual.textContent : '';
        var nums = /([\d.]+)\s*x\s*([\d.]+)/i.exec(raw);
        if (!nums && hasArt) {
            var dw = document.querySelector('input.nbo-dimension[ng-model*="width"]');
            var dh = document.querySelector('input.nbo-dimension[ng-model*="height"]');
            if (dw && dh && dw.value && dh.value) { nums = [null, dw.value, dh.value]; nums = [null, dw.value, dh.value]; raw = 'Actual size: ' + dw.value + ' x ' + dh.value + ' in'; }
        }
        var measure = hasArt && raw ? raw.replace(':', '') : '';
        setText($('sg-m-measure'), measure);
        setText($('sg-m-dim-w'), nums ? nums[1] + ' in' : '');
        setText($('sg-m-dim-h'), nums ? nums[2] + ' in' : '');

        // preview: re-render once the geometry has been stable for two ticks
        if (!hasArt || !canvas) {
            S.sig = ''; S.stableSig = '';
            var pimg = $('sg-m-preview').querySelector('img');
            if (pimg && pimg.hasAttribute('src')) pimg.removeAttribute('src');
            return;
        }
        var sig = signature(scope, canvas, item);
        if (sig === S.sig && sig !== S.stableSig) {
            S.stableSig = sig;
            renderPreview(canvas, item);
        }
        S.sig = sig;
    }

    function setText(el, txt) {
        if (el && el.textContent !== txt) el.textContent = txt;
    }

    function signature(scope, canvas, item) {
        var parts = [scope.currentStage, canvas.width, canvas.height];
        try {
            parts.push(canvas.getObjects().length);
            if (item) {
                parts.push(item.left, item.top, item.scaleX, item.scaleY, item.angle, item.flipX, item.flipY,
                    item.width, item.height, typeof item.getSrc === 'function' ? item.getSrc() : '');
            }
        } catch (e) { /* ignore */ }
        return parts.join('|');
    }

    function renderPreview(canvas, item) {
        var img = $('sg-m-preview').querySelector('img');
        var url = '';
        try {
            var m = Math.min(1, 720 / Math.max(canvas.width, 1));
            url = canvas.toDataURL({ format: 'png', multiplier: m });
        } catch (e) {
            // tainted canvas (cross-origin art): show the source file itself
            try { url = item && (item.origin_url || (typeof item.getSrc === 'function' ? item.getSrc() : '')); } catch (e2) { url = ''; }
        }
        if (url && img.getAttribute('src') !== url) img.setAttribute('src', url);
    }

    /* ------------------------------------------------- empty-guard hook point */
    // Called from override.js focusUploadTile(): point the customer at the
    // upload button instead of the hidden Photos tab.
    function focusUpload() {
        if (!S.on || !S.built) return false;
        var btn = $('sg-m-upload');
        try { root.scrollTop = 0; } catch (e) { /* ignore */ }
        btn.classList.add('sg-m-pulse');
        clearTimeout(S.pulseTimer);
        S.pulseTimer = setTimeout(function () { btn.classList.remove('sg-m-pulse'); }, 1800);
        return true;
    }
})();
