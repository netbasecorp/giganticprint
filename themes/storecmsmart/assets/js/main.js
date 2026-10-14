/* GiganticPrints — main.js */
(function () {
  'use strict';

  // Categories dropdown toggle (fallback nav)
  document.querySelectorAll('[data-dropdown]').forEach(function (dd) {
    var trigger = dd.querySelector('.nav-link');
    if (!trigger) return;

    var close = function () { dd.setAttribute('aria-expanded', 'false'); };
    var open  = function () { dd.setAttribute('aria-expanded', 'true'); };

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      dd.getAttribute('aria-expanded') === 'true' ? close() : open();
    });

    dd.querySelectorAll('.nav-dropdown-item, .nav-dropdown-footer').forEach(function (item) {
      item.addEventListener('click', function () { setTimeout(close, 80); });
    });

    document.addEventListener('click', function (e) {
      if (!dd.contains(e.target)) close();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  });

  // Smooth scroll for in-page anchors
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (id.length > 1 && document.querySelector(id)) {
        e.preventDefault();
        document.querySelector(id).scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // Product & Item card behaviour:
  //   Desktop (hover:hover): CSS handles image swap / CTA reveal.
  //   Touch: first tap reveals, second tap navigates, auto-collapse 5s.
  var hasHover = window.matchMedia('(hover: hover)').matches;

  document.querySelectorAll('.product-card, .item-card').forEach(function (card) {
    card.addEventListener('click', function (e) {
      if (hasHover) {
        // Desktop — let the href navigate naturally (don't prevent default)
        return;
      }
      if (!card.classList.contains('is-revealed')) {
        e.preventDefault();
        document.querySelectorAll('.product-card.is-revealed, .item-card.is-revealed').forEach(function (c) {
          if (c !== card) c.classList.remove('is-revealed');
        });
        card.classList.add('is-revealed');
        clearTimeout(card._collapse);
        card._collapse = setTimeout(function () { card.classList.remove('is-revealed'); }, 5000);
      }
      // Second tap: allow navigation (do not prevent default)
    });
  });

  // Update cart badge via WooCommerce fragments (AJAX)
  document.body.addEventListener('wc_fragments_refreshed', function () {
    var count = typeof wc_cart_fragments_params !== 'undefined'
      ? (window.cartItemCount || 0) : 0;
    document.querySelectorAll('.nbd-icon-btn .badge').forEach(function (b) {
      if (count > 0) { b.textContent = count; b.style.display = ''; }
      else           { b.style.display = 'none'; }
    });
  });

  // ── Mobile navigation drawer ──────────────────────────────
  var menuToggle    = document.getElementById('menu-toggle');
  var mobilePanel   = document.getElementById('mobile-nav-panel');
  var mobileOverlay = document.getElementById('mobile-overlay');
  var mobileClose   = document.getElementById('mobile-nav-close');

  function openMobileMenu() {
    document.body.classList.add('mobile-nav-open');
    if (menuToggle)    menuToggle.setAttribute('aria-expanded', 'true');
    if (mobilePanel)   { mobilePanel.setAttribute('aria-hidden', 'false'); mobilePanel.focus(); }
    if (mobileOverlay) mobileOverlay.setAttribute('aria-hidden', 'false');
  }
  function closeMobileMenu() {
    document.body.classList.remove('mobile-nav-open');
    if (menuToggle)    menuToggle.setAttribute('aria-expanded', 'false');
    if (mobilePanel)   mobilePanel.setAttribute('aria-hidden', 'true');
    if (mobileOverlay) mobileOverlay.setAttribute('aria-hidden', 'true');
    if (menuToggle)    menuToggle.focus();
  }

  if (menuToggle)    menuToggle.addEventListener('click', openMobileMenu);
  if (mobileClose)   mobileClose.addEventListener('click', closeMobileMenu);
  if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.body.classList.contains('mobile-nav-open')) closeMobileMenu();
  });

  // Close panel when a nav link is tapped
  if (mobilePanel) {
    mobilePanel.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { setTimeout(closeMobileMenu, 100); });
    });
  }

  // ── Scroll-to-top button ──────────────────────────────────
  var scrollBtn = document.createElement('button');
  scrollBtn.className = 'scroll-top-btn';
  scrollBtn.setAttribute('aria-label', 'Back to top');
  scrollBtn.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
  document.body.appendChild(scrollBtn);
  window.addEventListener('scroll', function () {
    scrollBtn.classList.toggle('visible', window.scrollY > 400);
  }, { passive: true });
  scrollBtn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

})();
