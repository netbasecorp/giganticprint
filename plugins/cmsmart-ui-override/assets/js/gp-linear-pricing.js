/**
 * Browser mirror of GP_Linear_Pricing: pole pocket / rope priced per linear foot from the chosen size.
 * Rules come from window.GP_LINEAR (printed by PHP before this file).
 */
(function () {
	'use strict';

	var cfg = window.GP_LINEAR;
	if (!cfg || !cfg.rules) { return; }

	var rules = cfg.rules;
	var compiled = {};
	Object.keys(rules).forEach(function (kind) {
		var r = rules[kind];
		try {
			compiled[kind] = {
				title: new RegExp(r.title, 'i'),
				opts: (r.options || []).map(function (o) { return [new RegExp(o[0], 'i'), Number(o[1])]; })
			};
		} catch (e) { /* invalid pattern: skip rule */ }
	});

	function decode(s) {
		return String(s == null ? '' : s).replace(/&amp;/g, '&').replace(/\\(.)/g, '$1');
	}

	function factorFor(kind, name) {
		var c = compiled[kind], i;
		if (!c) { return 0; }
		name = decode(name);
		for (i = 0; i < c.opts.length; i++) {
			if (c.opts[i][0].test(name)) { return c.opts[i][1]; }
		}
		return 0;
	}

	function compute(kind, w, h, name) {
		var rule = rules[kind], c = compiled[kind];
		if (!rule || !c || !(w > 0) || !(h > 0)) { return 0; }
		var factor = factorFor(kind, name);
		if (!(factor > 0)) { return 0; }
		var feet = rule.measure === 'perimeter' ? 2 * (w + h) / 12 : w / 12;
		var v = factor * feet * Number(rule.rate || 0) + Number(rule.setup || 0);
		return Math.round(v * 100 + 1e-9) / 100;
	}

	function kindFor(title) {
		title = decode(title);
		var k;
		for (k in compiled) {
			if (compiled.hasOwnProperty(k) && compiled[k].title.test(title)) { return k; }
		}
		return '';
	}

	function currentSize(scope) {
		var fields = (scope.options && scope.options.fields) || [];
		var states = scope.nbd_fields || {};
		var i, f, st, w, h;
		for (i = 0; i < fields.length; i++) {
			f = fields[i];
			if (!f || f.nbd_type !== 'dimension') { continue; }
			st = states[f.id];
			if (!st) { continue; }
			w = parseFloat(st.width);
			h = parseFloat(st.height);
			if (!(w > 0) || !(h > 0)) { continue; }
			if (st.enable === false || st.enable === 'n' || (f.general && f.general.enabled === 'n')) { continue; }
			return { w: w, h: h };
		}
		return null;
	}

	function targetFields(scope) {
		var out = [], seen = [], list = (scope.options && scope.options.fields) || [], i, f;
		function add(x) { if (x && seen.indexOf(x) < 0) { seen.push(x); out.push(x); } }
		for (i = 0; i < list.length; i++) {
			f = list[i];
			if (!f || !f.general) { continue; }
			add(f);
			if (typeof scope.get_field === 'function') {
				try { add(scope.get_field(f.id)); } catch (e) { /* ignore */ }
			}
		}
		return out;
	}

	function patch(scope) {
		var size = currentSize(scope);
		try { relabel(scope, size); } catch (e) { /* labels are cosmetic */ }
		if (!size) { return; }
		var fields = targetFields(scope), i, f, kind, opts, j, price, slots, k;
		var qb = scope.options && scope.options.quantity_breaks;
		var nBreaks = (qb && qb.length) ? qb.length : 0;
		for (i = 0; i < fields.length; i++) {
			f = fields[i];
			if (!f.general || !f.general.attributes || !f.general.attributes.options) { continue; }
			kind = kindFor(f.general.title);
			if (!kind) { continue; }
			opts = f.general.attributes.options;
			for (j in opts) {
				if (!opts.hasOwnProperty(j) || !opts[j]) { continue; }
				price = compute(kind, size.w, size.h, opts[j].name).toFixed(2);
				if (!opts[j].price || typeof opts[j].price !== 'object') { opts[j].price = []; }
				slots = Object.keys(opts[j].price);
				for (k = 0; k <= nBreaks; k++) { if (slots.indexOf(String(k)) < 0) { slots.push(String(k)); } }
				for (k = 0; k < slots.length; k++) { opts[j].price[slots[k]] = price; }
			}
		}
	}

	// ---- Honest option button labels (rule text from live rates + amount for the current size) ----
	var sym = cfg.symbol || '$';

	function money(v) { return sym + Number(v).toFixed(2); }

	function ruleText(kind, name) {
		var rule = rules[kind], short = decode(name).replace(/\s*[—–-]+\s*quoted.*$/i, '').trim();
		var t = short + ': ' + money(rule.rate || 0) + '/ft';
		if (Number(rule.setup || 0) > 0) { t += ' + ' + money(rule.setup) + ' setup'; }
		return t;
	}

	function setText(el, txt) { if (el.textContent !== txt) { el.textContent = txt; } }

	function syncSelectedVal(fieldEl) {
		var val = fieldEl.querySelector('.cui-selected-val'), on = fieldEl.querySelector('.nbd-label-wrap input[type="radio"]:checked');
		var lbl = on && on.nextElementSibling, clone;
		if (!val || !lbl || lbl.tagName !== 'LABEL') { return; }
		clone = lbl.cloneNode(true);
		Array.prototype.forEach.call(clone.querySelectorAll('.gp-lin-rule'), function (n) { n.parentNode.removeChild(n); });
		if (val.innerHTML !== clone.innerHTML) { val.innerHTML = clone.innerHTML; }
	}

	function relabel(scope, size) {
		var fields = (scope.options && scope.options.fields) || [], i, f, kind, opts, fieldEl, radios, r, key, opt, name, factor, host, badge, rule, txt, swatch, label, amount;
		for (i = 0; i < fields.length; i++) {
			f = fields[i];
			if (!f || !f.general || !f.general.attributes || !f.general.attributes.options) { continue; }
			kind = kindFor(f.general.title);
			if (!kind) { continue; }
			opts = f.general.attributes.options;
			fieldEl = document.querySelector('[data-id="' + f.id + '"]');
			if (!fieldEl) { continue; }
			radios = fieldEl.querySelectorAll('input[type="radio"]');
			for (r = 0; r < radios.length; r++) {
				key = radios[r].value;
				opt = opts[key];
				if (!opt) { continue; }
				name = opt.name;
				factor = factorFor(kind, name);
				swatch = radios[r].closest ? radios[r].closest('.nbd-swatch-label-wrap') : null;
				label = swatch || (radios[r].id ? fieldEl.querySelector('label[for="' + radios[r].id + '"]') : null);
				if (!label) { continue; }
				host = (swatch && swatch.querySelector('.nbd-swatch-description')) || label;
				badge = host.querySelector('.gp-lin-badge');
				if (!badge) {
					// Drop any stale flat-price badge written by the theme script.
					Array.prototype.forEach.call(host.querySelectorAll('.cui-sw-price, .cui-label-price'), function (n) { n.parentNode.removeChild(n); });
					badge = document.createElement('span');
					badge.className = 'gp-lin-badge ' + (swatch ? 'cui-sw-price' : 'cui-label-price');
					host.appendChild(badge);
				}
				rule = host.querySelector('.gp-lin-rule');
				if (!rule) {
					rule = document.createElement('small');
					rule.className = 'gp-lin-rule';
					host.appendChild(rule);
				}
				if (!(factor > 0)) {
					setText(badge, 'Free');
					setText(rule, '');
					badge.className = badge.className.replace(/\s*\S*--free/, '') + (swatch ? ' cui-sw-price--free' : ' cui-label-price--free');
					continue;
				}
				badge.className = badge.className.replace(/\s*\S*--free/, '');
				txt = ruleText(kind, name);
				if (size) {
					amount = compute(kind, size.w, size.h, name);
					setText(badge, '+' + money(amount));
					setText(rule, txt);
				} else {
					setText(badge, txt);
					setText(rule, '');
				}
				if (label.getAttribute('title') !== txt) { label.setAttribute('title', txt); }
			}
			syncSelectedVal(fieldEl);
		}
	}

	function hasLinearField(scope) {
		var list = (scope.options && scope.options.fields) || [], i;
		for (i = 0; i < list.length; i++) {
			if (list[i] && list[i].general && kindFor(list[i].general.title)) { return true; }
		}
		return false;
	}

	function findScope() {
		if (!window.angular) { return null; }
		var el = null, s;
		if (window.nbOption && window.nbOption.crtlId) { el = document.getElementById(window.nbOption.crtlId); }
		if (!el) { el = document.querySelector('[ng-controller^="optionCtrl"]'); }
		if (!el) { return null; }
		try { s = window.angular.element(el).scope(); } catch (e) { return null; }
		return s && s.options && s.options.fields && typeof s.calculate_price === 'function' ? s : null;
	}

	function install(scope) {
		if (scope.__gpLinear || !hasLinearField(scope)) { return; }
		scope.__gpLinear = true;
		var original = scope.calculate_price;
		scope.calculate_price = function () {
			try { patch(scope); } catch (e) { /* never block pricing */ }
			return original.apply(this, arguments);
		};
		// Size changes go through check_valid() -> calculate_price(), so the wrapper re-runs on its own.
		// Re-run once now: the first pass may have happened before this script installed.
		// The theme's swatch script and Angular ng-if may render buttons late: relabel again shortly.
		[300, 900, 2000].forEach(function (ms) {
			setTimeout(function () { try { relabel(scope, currentSize(scope)); } catch (e) { /* ignore */ } }, ms);
		});
		scope.$applyAsync(function () {
			if (typeof scope.check_valid === 'function') { scope.check_valid(); } else { scope.calculate_price(); }
		});
	}

	var tries = 0;
	function poll() {
		var scope = findScope();
		if (scope) {
			try {
				install(scope);
			} catch (e) { /* ignore */ }
			return;
		}
		if (++tries < 120) { setTimeout(poll, 250); }
	}
	poll();
})();
