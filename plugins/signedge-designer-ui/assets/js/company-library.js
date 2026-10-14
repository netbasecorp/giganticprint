/* Image Zone: "Your company images" block in the designer Photos tab.
 * Only loaded for logged-in members of an approved B2B company (window.SG_IZ is printed by PHP).
 * A click on a thumbnail fetches the original from gp_iz_file and feeds it to the designer's
 * own uploadFile() so placement, auto-size, preview and the low-res warning all apply. */
(function () {
    'use strict';
    var CFG = window.SG_IZ;
    if (!CFG || !CFG.ajaxUrl || !CFG.nonce) { return; }

    var block = null;
    var grid = null;
    var msg = null;
    var loaded = false;
    var busyId = 0;

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) { e.className = cls; }
        if (text !== undefined && text !== null) { e.textContent = text; }
        return e;
    }

    function setMsg(text, isErr) {
        if (!msg) { return; }
        msg.textContent = text || '';
        msg.className = 'sg-iz-msg' + (isErr ? ' sg-iz-err' : '');
        msg.style.display = text ? '' : 'none';
    }

    function getScope() {
        try {
            var n = document.querySelector('[ng-controller=designCtrl]');
            return n && window.angular ? window.angular.element(n).scope() : null;
        } catch (e) { return null; }
    }

    function request(action, cb) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('_nonce', CFG.nonce);
        fd.append('store_id', CFG.storeId);
        var x = new XMLHttpRequest();
        x.open('POST', CFG.ajaxUrl);
        x.withCredentials = true;
        x.onload = function () {
            var j = null;
            try { j = JSON.parse(x.responseText); } catch (e) { j = null; }
            if (j && j.success) { cb(null, j.data); return; }
            cb((j && j.data && j.data.message) || 'Could not load your company images.');
        };
        x.onerror = function () { cb('Network error. Please try again.'); };
        x.send(fd);
    }

    function render(items) {
        grid.innerHTML = '';
        if (!items.length) {
            setMsg('No company images yet. Add them in your account: B2B portal, Image Zone.', false);
            return;
        }
        setMsg('', false);
        for (var i = 0; i < items.length; i++) { grid.appendChild(card(items[i])); }
    }

    function card(it) {
        var c = el('div', 'sg-iz-item');
        c.setAttribute('data-iz-id', it.id);
        c.title = it.name;
        var t = el('div', 'sg-iz-thumb');
        var img = document.createElement('img');
        img.alt = it.name;
        img.src = it.thumb;
        t.appendChild(img);
        var cap = el('div', 'sg-iz-cap');
        cap.appendChild(el('span', 'sg-iz-name', it.name));
        cap.appendChild(el('span', 'sg-iz-dim', it.width && it.height ? it.width + ' x ' + it.height + ' px' : it.ext.toUpperCase()));
        c.appendChild(t);
        c.appendChild(cap);
        c.addEventListener('click', function () { useImage(it, c, t); });
        return c;
    }

    function useImage(it, card, thumb) {
        if (busyId) { return; }
        var scope = getScope();
        if (!scope || typeof scope.uploadFile !== 'function') {
            setMsg('The designer is not ready yet. Please try again in a moment.', true);
            return;
        }
        busyId = it.id;
        card.className += ' sg-iz-loading';
        thumb.setAttribute('data-loading', 'Loading...');
        setMsg('Loading ' + it.name + '...', false);

        function done(err) {
            busyId = 0;
            card.className = card.className.replace(/\s*sg-iz-loading/g, '');
            thumb.removeAttribute('data-loading');
            setMsg(err || '', !!err);
        }

        var x = new XMLHttpRequest();
        x.open('GET', it.url);
        x.withCredentials = true;
        x.responseType = 'blob';
        x.onload = function () {
            if (x.status !== 200 || !x.response) { done('Could not load ' + it.name + ' (' + x.status + ').'); return; }
            try {
                var file = new File([x.response], it.name, { type: it.mime || x.response.type });
                var sc = getScope() || scope;
                sc.uploadFile([file]);
                done('');
            } catch (e) {
                done('Could not add ' + it.name + ' to the design.');
            }
        };
        x.onerror = function () { done('Network error while loading ' + it.name + '.'); };
        x.send();
    }

    function load() {
        refreshRefs();
        setMsg('Loading your company images...', false);
        request('gp_iz_list', function (err, data) {
            if (err) { setMsg(err, true); return; }
            loaded = true;
            render(data.items || []);
        });
    }

    // Angular may re-render the Photos tab and replace our node by a clone without listeners: always use the live node
    function refreshRefs() {
        var b = document.getElementById('sg-iz');
        if (!b) { return false; }
        block = b;
        msg = b.querySelector('.sg-iz-msg');
        grid = b.querySelector('.sg-iz-grid');
        return !!(msg && grid);
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        while (t && t !== document) {
            if (t.className && typeof t.className === 'string' && /\bsg-iz-head\b/.test(t.className)) {
                if (refreshRefs()) { toggle(); }
                return;
            }
            t = t.parentNode;
        }
    });

    function toggle() {
        var open = /\bsg-iz-open\b/.test(block.className);
        if (open) {
            block.className = block.className.replace(/\s*sg-iz-open/g, '');
            return;
        }
        block.className += ' sg-iz-open';
        load();
    }

    function build() {
        block = el('div');
        block.id = 'sg-iz';
        var head = el('button', 'sg-iz-head');
        head.type = 'button';
        head.appendChild(el('span', '', 'Your company images'));
        head.appendChild(el('span', 'sg-iz-caret', '▼'));
        var body = el('div', 'sg-iz-body');
        msg = el('div', 'sg-iz-msg');
        msg.style.display = 'none';
        grid = el('div', 'sg-iz-grid');
        body.appendChild(msg);
        body.appendChild(grid);
        block.appendChild(head);
        block.appendChild(body);
        return block;
    }

    function mount(attempt) {
        if (document.getElementById('sg-iz')) { return; }
        var form = document.querySelector('#tab-photo .form-upload');
        if (!form) {
            if (attempt < 60) { setTimeout(function () { mount(attempt + 1); }, 500); }
            return;
        }
        var anchor = form.nextElementSibling;
        if (!anchor || !/\ballow-size\b/.test(anchor.className)) { anchor = form; }
        anchor.parentNode.insertBefore(build(), anchor.nextSibling);
    }

    mount(0);
}());

/* Company templates: "Save as company template" + "Load template" toolbar (#sg-tpl-bar) in the designer.
 * Only loaded for members of an approved B2B company (window.SG_TPL is printed by PHP).
 * Own toolbar and own busy overlay: independent of the art bar (#sg-artbar) and the mobile quick flow (#sg-m). */
(function () {
    'use strict';
    var T = window.SG_TPL;
    if (!T || !T.ajaxUrl || !T.nonce) { return; }

    var FALLBACK_EXPORT = ['objectCaching', 'itemId', 'selectable', 'editable', 'lockMovementX', 'lockMovementY', 'lockScalingX', 'lockScalingY', 'lockRotation', 'rtl', 'elementUpload', 'forceLock', 'isBg', 'isBgImg', 'is_uppercase', 'available_color', 'available_color_list', 'color_link_group', 'isOverlay', 'isAlwaysOnTop', 'ilr', 'oos', 'evented', 'ptFontSize', 'origin_src', 'reverse', 'spacing', 'radius', 'field_mapping', 'isQrcode', 'qrContent', 'isBarcode', 'barCodeContent', 'v_card', 'isMask', 'maskId', 'strokeWidth', 'absolutePositioned', 'perPixelTargetFind', 'centeredScaling', 'origin_url', 'origin_width', 'origin_height', 'cropped', 'layerName', 'origin_pdf', 'isTemMask', 'srcChanged', 'isEmptyMask', 'lockMask'];
    var THUMB_MAX = 320;
    var THUMB_BYTES = 60000;

    var bar = null, listEl = null, saveBtn = null, msgEl = null, busyEl = null;
    var busy = false;
    var autoDone = false;

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) { e.className = cls; }
        if (text !== undefined && text !== null) { e.textContent = text; }
        return e;
    }

    function getScope() {
        try {
            var n = document.querySelector('[ng-controller=designCtrl]');
            return n && window.angular ? window.angular.element(n).scope() : null;
        } catch (e) { return null; }
    }

    function getNbo() {
        try {
            if (!window.angular) { return null; }
            var n = null;
            if (window.nbOption && window.nbOption.crtlId) { n = document.getElementById(window.nbOption.crtlId); }
            if (!n) { n = document.querySelector('#nbo-options-wrap [ng-controller]') || document.querySelector('#nbo-options-wrap'); }
            return n ? (window.angular.element(n).scope() || null) : null;
        } catch (e) { return null; }
    }

    function optionsReady() {
        try {
            var s = getNbo();
            return !!(s && s.nbd_fields && Object.keys(s.nbd_fields).length > 0 && window.nbOption && window.nbOption.initialed);
        } catch (e) { return false; }
    }

    function productId() {
        var m = /[?&]product_id=(\d+)/.exec(window.location.search);
        if (m) { return parseInt(m[1], 10); }
        try {
            var s = getScope();
            var id = s && s.settings && (s.settings.product_id || (s.settings.product_data && s.settings.product_data.product_id));
            return id ? parseInt(id, 10) : 0;
        } catch (e) { return 0; }
    }

    function runInScope(scope, fn) {
        try {
            if (scope && (scope.$$phase || (scope.$root && scope.$root.$$phase))) { scope.$evalAsync(fn); }
            else if (scope && typeof scope.$apply === 'function') { scope.$apply(fn); }
            else { fn(); }
        } catch (e) { /* never throw into the digest */ }
    }

    function currentCanvas(scope) {
        var st = scope && scope.stages && scope.stages[scope.currentStage];
        return st && st.canvas && typeof st.canvas.getObjects === 'function' ? st.canvas : null;
    }

    function hasArt(scope) {
        if (!scope || !scope.stages) { return false; }
        for (var i = 0; i < scope.stages.length; i++) {
            var c = scope.stages[i] && scope.stages[i].canvas;
            if (c && typeof c.getObjects === 'function') {
                var o = c.getObjects();
                for (var j = 0; j < o.length; j++) { if (!o[j].excludeFromExport) { return true; } }
            }
        }
        return false;
    }

    /* ---- request ---- */
    function request(action, fields, cb) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('_nonce', T.nonce);
        fd.append('store_id', T.storeId);
        for (var k in fields) { if (Object.prototype.hasOwnProperty.call(fields, k)) { fd.append(k, fields[k]); } }
        var x = new XMLHttpRequest();
        x.open('POST', T.ajaxUrl);
        x.withCredentials = true;
        x.onload = function () {
            var j = null;
            try { j = JSON.parse(x.responseText); } catch (e) { j = null; }
            if (j && j.success) { cb(null, j.data); return; }
            cb((j && j.data && j.data.message) || (x.status === 413 ? 'The template is too large.' : 'Request failed (' + x.status + ').'));
        };
        x.onerror = function () { cb('Network error. Please try again.'); };
        x.send(fd);
    }

    /* ---- messages / busy overlay ---- */
    function setMsg(text, isErr) {
        if (!msgEl) { return; }
        msgEl.textContent = text || '';
        msgEl.className = 'sg-tpl-msg' + (isErr ? ' sg-tpl-err' : '');
        msgEl.style.display = text ? '' : 'none';
        if (text && !isErr) {
            clearTimeout(setMsg.t);
            setMsg.t = setTimeout(function () { if (msgEl && msgEl.textContent === text) { msgEl.style.display = 'none'; } }, 6000);
        }
    }

    function showBusy(text) {
        if (!busyEl) {
            busyEl = el('div');
            busyEl.id = 'sg-tpl-busy';
            busyEl.setAttribute('role', 'status');
            busyEl.setAttribute('aria-live', 'polite');
            var card = el('div', 'sg-tpl-busy-card');
            card.appendChild(el('span', 'sg-tpl-spinner'));
            card.appendChild(el('span', 'sg-tpl-busy-text'));
            busyEl.appendChild(card);
            document.body.appendChild(busyEl);
        }
        busyEl.querySelector('.sg-tpl-busy-text').textContent = text;
        busyEl.className = 'sg-tpl-busy-on';
    }

    function hideBusy() { if (busyEl) { busyEl.className = ''; } }

    /* ---- build the config ---- */
    function exportProps(scope) {
        return scope && scope.includeExport && scope.includeExport.length ? scope.includeExport : FALLBACK_EXPORT;
    }

    function readOptions(nbo) {
        var out = {};
        if (!nbo || !nbo.nbd_fields) { return out; }
        Object.keys(nbo.nbd_fields).forEach(function (id) {
            var f = nbo.nbd_fields[id];
            if (!f || !f.enable) { return; }
            var o = {};
            if (f.value !== undefined) { o.value = f.value; }
            if (f.width !== undefined) { o.width = f.width; }
            if (f.height !== undefined) { o.height = f.height; }
            if (f.value_name !== undefined) { o.value_name = f.value_name; }
            out[id] = o;
        });
        return out;
    }

    function dimensionFieldId(nbo) {
        if (!nbo || !nbo.fields || !nbo.nbd_fields) { return null; }
        for (var i = 0; i < nbo.fields.length; i++) {
            var f = nbo.fields[i];
            if (f && f.nbd_type === 'dimension' && nbo.nbd_fields[f.id] && nbo.nbd_fields[f.id].enable) { return f.id; }
        }
        return null;
    }

    function artMode() {
        var b = document.querySelector('#sg-artbar [data-sg-art].sg-active');
        return b ? b.getAttribute('data-sg-art') : null;
    }

    function buildConfig(scope) {
        var nbo = getNbo();
        var props = exportProps(scope);
        var stages = [];
        for (var i = 0; i < scope.stages.length; i++) {
            var c = scope.stages[i] && scope.stages[i].canvas;
            stages.push(c && typeof c.toJSON === 'function' ? c.toJSON(props) : null);
        }
        var qty = nbo ? parseInt(nbo.quantity, 10) : NaN;
        var dims = null;
        var did = dimensionFieldId(nbo);
        if (did !== null) { dims = { fieldId: did, width: nbo.nbd_fields[did].width, height: nbo.nbd_fields[did].height }; }
        return {
            v: 1,
            product_id: productId(),
            qty: isNaN(qty) || qty < 1 ? 1 : qty,
            options: readOptions(nbo),
            stages: stages,
            dims: dims,
            artMode: artMode()
        };
    }

    function buildThumb(scope) {
        try {
            var c = scope.stages[0] && scope.stages[0].canvas;
            if (!c || typeof c.toDataURL !== 'function') { return ''; }
            var mult = Math.min(1, THUMB_MAX / Math.max(c.getWidth ? c.getWidth() : c.width, c.getHeight ? c.getHeight() : c.height, 1));
            var q = 0.6, url = '';
            for (var n = 0; n < 4; n++) {
                url = c.toDataURL({ format: 'jpeg', quality: q, multiplier: mult });
                if (url.length <= THUMB_BYTES) { return url; }
                mult *= 0.75;
                q = Math.max(0.4, q - 0.1);
            }
            return '';
        } catch (e) { return ''; }
    }

    /* ---- save ---- */
    function save() {
        if (busy) { return; }
        var scope = getScope();
        if (!scope || !scope.stages || !currentCanvas(scope)) { setMsg('The designer is not ready yet. Please try again in a moment.', true); return; }
        var pid = productId();
        if (!pid) { setMsg('Could not detect the product of this design.', true); return; }
        var name = window.prompt('Name of the company template:', '');
        if (name === null) { return; }
        name = name.replace(/^\s+|\s+$/g, '');
        if (!name) { setMsg('Please enter a template name.', true); return; }

        var cfg;
        try { cfg = JSON.stringify(buildConfig(scope)); }
        catch (e) { setMsg('Could not read the design.', true); return; }
        if (cfg.length > 2097152) { setMsg('This design is too large to save as a template (over 2 MB).', true); return; }

        busy = true;
        saveBtn.disabled = true;
        setMsg('Saving template...', false);
        request('gp_tpl_save', { name: name, product_id: pid, config: cfg, thumb: buildThumb(scope) }, function (err) {
            busy = false;
            saveBtn.disabled = false;
            if (err) { setMsg(err, true); return; }
            setMsg('Template "' + name + '" saved.', false);
            loadList();
        });
    }

    /* ---- list ---- */
    function loadList() {
        var pid = productId();
        if (!pid || !listEl) { return; }
        request('gp_tpl_list', { product_id: pid, no_thumb: 1 }, function (err, data) {
            if (err) { return; }
            var items = (data && data.items) || [];
            listEl.innerHTML = '';
            var first = el('option', '', items.length ? 'Load template...' : 'No templates for this product');
            first.value = '';
            listEl.appendChild(first);
            for (var i = 0; i < items.length; i++) {
                var o = el('option', '', items[i].name);
                o.value = String(items[i].id);
                listEl.appendChild(o);
            }
            listEl.disabled = !items.length;
        });
    }

    /* ---- load ---- */
    function applyOptionValues(nbo, cfg) {
        var opts = cfg.options || {};
        Object.keys(opts).forEach(function (id) {
            var live = nbo.nbd_fields && nbo.nbd_fields[id];
            var saved = opts[id];
            if (!live || !saved) { return; }
            if (saved.value !== undefined) { live.value = saved.value; }
            if (saved.value_name !== undefined) { live.value_name = saved.value_name; }
            if (saved.width !== undefined) { live.width = saved.width; }
            if (saved.height !== undefined) { live.height = saved.height; }
        });
        // same sequence as the size edit in override.js
        if (cfg.dims && cfg.dims.fieldId !== undefined && nbo.nbd_fields[cfg.dims.fieldId] && typeof nbo.update_dimensionvalue === 'function') {
            var f = nbo.nbd_fields[cfg.dims.fieldId];
            if (cfg.dims.width !== undefined) { f.width = cfg.dims.width; }
            if (cfg.dims.height !== undefined) { f.height = cfg.dims.height; }
            nbo.update_dimensionvalue(cfg.dims.fieldId, 'width');
            nbo.update_dimensionvalue(cfg.dims.fieldId, 'height');
        }
        var q = parseInt(cfg.qty, 10);
        if (q > 0 && nbo.quantity !== undefined && nbo.quantity !== q) {
            nbo.quantity = q;
            if (typeof nbo.change_quantity === 'function') { nbo.change_quantity(); }
        }
        if (typeof nbo.check_valid === 'function') { nbo.check_valid(); }
        if (typeof nbo.applyOptions === 'function') { nbo.applyOptions(); }
    }

    // Poll for the canvas that applyOptions() rebuilds (like waitForFreshCanvasThenAdd in override.js).
    function waitForCanvas(scope, oldCanvas, attempt, cb) {
        var c = currentCanvas(scope);
        if (c && c !== oldCanvas) { setTimeout(cb, 250); return; }
        if (attempt >= 8 && c) { cb(); return; }   // no rebuild happened (same size): use the current canvas
        if (attempt >= 30) { cb(); return; }
        setTimeout(function () { waitForCanvas(scope, oldCanvas, attempt + 1, cb); }, 300);
    }

    function loadStages(scope, stages, cb) {
        var i = 0;
        function next() {
            while (i < stages.length && (!stages[i] || !scope.stages[i] || !scope.stages[i].canvas)) { i++; }
            if (i >= stages.length) { cb(); return; }
            var c = scope.stages[i].canvas;
            var json = stages[i];
            i++;
            var finished = false;
            function done() { if (finished) { return; } finished = true; try { c.renderAll(); } catch (e) { /* ignore */ } next(); }
            try { c.loadFromJSON(json, done); } catch (e) { done(); return; }
            setTimeout(done, 15000);   // never hang on a broken image
        }
        next();
    }

    function refreshAfterLoad(scope) {
        runInScope(scope, function () {
            if (typeof scope.updateCorners === 'function') { scope.updateCorners(); }
            if (typeof scope.renderStage === 'function') { scope.renderStage(); }
            if (typeof scope.afterObjectModify === 'function') { scope.afterObjectModify(); }
        });
        var nbo = getNbo();
        if (nbo) {
            runInScope(nbo, function () {
                if (typeof nbo.calculate_price === 'function') { nbo.calculate_price(); }
                if (typeof nbo.check_valid === 'function') { nbo.check_valid(); }
            });
        }
        try { window.dispatchEvent(new CustomEvent('sg:tpl-loaded')); } catch (e) { /* old browser */ }
    }

    function applyTemplate(item, done) {
        var cfg = item.config;
        var scope = getScope();
        var nbo = getNbo();
        if (!cfg || !scope || !nbo || !nbo.nbd_fields) { done('The designer is not ready yet. Please try again.'); return; }
        if (parseInt(item.product_id, 10) !== productId()) { done('This template belongs to another product.'); return; }
        var old = currentCanvas(scope);
        runInScope(nbo, function () { applyOptionValues(nbo, cfg); });
        waitForCanvas(scope, old, 0, function () {
            var s = getScope() || scope;
            loadStages(s, cfg.stages || [], function () {
                refreshAfterLoad(s);
                setTimeout(function () { refreshAfterLoad(s); done(''); }, 700);
            });
        });
    }

    function loadTemplate(id, ask, after) {
        if (busy) { if (after) { after(); } return; }
        var scope = getScope();
        if (ask && hasArt(scope) && !window.confirm('Loading a template replaces the artwork on the canvas. Continue?')) {
            if (after) { after(); }
            return;
        }
        busy = true;
        showBusy('Loading template...');
        request('gp_tpl_get', { id: id }, function (err, data) {
            if (err) { busy = false; hideBusy(); setMsg(err, true); if (after) { after(); } return; }
            applyTemplate(data.item, function (e2) {
                busy = false;
                hideBusy();
                setMsg(e2 || 'Template "' + data.item.name + '" loaded.', !!e2);
                if (after) { after(); }
            });
        });
    }

    /* ---- autoload: ?gp_tpl=<id> ---- */
    function autoload(attempt) {
        if (autoDone) { return; }
        var m = /[?&]gp_tpl=(\d+)/.exec(window.location.search);
        if (!m) { autoDone = true; return; }
        var scope = getScope();
        if (optionsReady() && currentCanvas(scope)) {
            autoDone = true;
            loadTemplate(parseInt(m[1], 10), false);
            return;
        }
        if (attempt === 0) { showBusy('Loading template...'); }
        if (attempt < 120) { setTimeout(function () { autoload(attempt + 1); }, 500); }
        else { autoDone = true; hideBusy(); }
    }

    /* ---- UI ---- */
    function build() {
        bar = el('div');
        bar.id = 'sg-tpl-bar';
        bar.appendChild(el('span', 'sg-tpl-label', 'Templates'));
        saveBtn = el('button', 'sg-tpl-btn', 'Save as company template');
        saveBtn.type = 'button';
        saveBtn.id = 'sg-tpl-save';
        saveBtn.addEventListener('click', save);
        bar.appendChild(saveBtn);
        listEl = el('select', 'sg-tpl-select');
        listEl.id = 'sg-tpl-list';
        listEl.setAttribute('aria-label', 'Load template');
        var o = el('option', '', 'Load template...');
        o.value = '';
        listEl.appendChild(o);
        listEl.addEventListener('change', function () {
            var id = parseInt(listEl.value, 10);
            if (!id) { return; }
            loadTemplate(id, true, function () { listEl.value = ''; });
        });
        bar.appendChild(listEl);
        msgEl = el('div', 'sg-tpl-msg');
        msgEl.style.display = 'none';
        bar.appendChild(msgEl);
        document.body.appendChild(bar);
    }

    /* ---- hide the public template UI (company setting) ---- */
    function hidePublicTemplates() {
        var tabs = document.querySelectorAll('li.tab, .tab, [data-type="template"], [data-type="globalTemplate"]');
        for (var i = 0; i < tabs.length; i++) {
            var t = tabs[i];
            var txt = (t.textContent || '').replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
            var idc = (t.id || '') + ' ' + (typeof t.className === 'string' ? t.className : '');
            var isTpl = (t.tagName === 'LI' && /^templates?$/i.test(txt)) || /nav-templates|tab-product-template|nbd-template/i.test(idc) || /template/i.test(t.getAttribute('data-type') || '');
            if (!isTpl || t.getAttribute('data-sg-tpl-hidden')) { continue; }
            t.style.display = 'none';
            t.setAttribute('data-sg-tpl-hidden', '1');
            if (/\bactive\b/.test(t.className)) {
                t.className = t.className.replace(/\bactive\b/g, '');
                var other = document.querySelector('#nav-typos, #nav-photos, #nav-cliparts');
                if (other && typeof other.click === 'function') { other.click(); }
            }
        }
    }

    function watchPublicTemplates() {
        if (!T.hidePublic) { return; }
        document.body.classList.add('sg-hide-public-templates');
        var n = 0;
        var iv = setInterval(function () {
            hidePublicTemplates();
            if (++n > 40) { clearInterval(iv); }   // the tabs render late (Angular); 20 s is plenty
        }, 500);
    }

    function boot() {
        if (document.getElementById('sg-tpl-bar')) { return; }
        build();
        watchPublicTemplates();
        loadList();
        autoload(0);
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); }
    else { boot(); }
}());
