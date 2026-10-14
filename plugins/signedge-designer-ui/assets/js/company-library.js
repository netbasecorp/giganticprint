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
        setMsg('Loading your company images...', false);
        request('gp_iz_list', function (err, data) {
            if (err) { setMsg(err, true); return; }
            loaded = true;
            render(data.items || []);
        });
    }

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
        head.addEventListener('click', toggle);
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
