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

	function compute(kind, w, h, name) {
		var rule = rules[kind], c = compiled[kind];
		if (!rule || !c || !(w > 0) || !(h > 0)) { return 0; }
		var factor = 0, i;
		name = decode(name);
		for (i = 0; i < c.opts.length; i++) {
			if (c.opts[i][0].test(name)) { factor = c.opts[i][1]; break; }
		}
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
