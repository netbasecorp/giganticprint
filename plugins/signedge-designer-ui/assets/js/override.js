/**
 * Signedge Designer UI — override.js  v1.7.0
 *
 * Responsibilities:
 *  1. Inject a right-hand summary panel (.sg-summary) into .nbd-workspace
 *  2. Add an "AI Tools — Coming Soon" button to the top-bar left menu
 *  3. Mirror selected options from #nbo-options-wrap into the summary panel
 *  4. Mirror the computed price (from Angular scope or DOM) into the summary panel
 *  5. Wire the summary "Add to Cart" button to the existing Process button
 *  6. Show a "click to select an image" overlay on an empty canvas so
 *     customers can start uploading without opening the Photos tab first
 */
(function () {
    'use strict';

    /* ----------------------------------------------------------------
       ENTRY POINT — wait for Angular/DOM to settle then initialise
    ---------------------------------------------------------------- */
    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        // None of these need Angular — inject immediately from static DOM.
        injectCategoryNav();
        injectCartButton();
        injectAuthButton();
        injectB2BModal();
        injectLoginModal();
        // Give Angular time to bootstrap; retry for up to 10 s in case it's slow.
        attemptInit(0);
    });

    function attemptInit(attempt) {
        var workspace = document.querySelector('.nbd-workspace');
        console.log('[SG-DUI] attemptInit #' + attempt + ' — workspace:', !!workspace);

        if (workspace) {
            init(workspace);
        } else if (attempt < 20) {
            // Retry every 500 ms (up to 10 s total)
            setTimeout(function () { attemptInit(attempt + 1); }, 500);
        } else {
            console.warn('[SG-DUI] .nbd-workspace not found after 10 s — giving up.');
        }
    }

    var isMobile = window.innerWidth <= 767;

    /* ----------------------------------------------------------------
       CATEGORY NAV BAR
       Injected immediately at DOM-ready (no Angular needed).
       Data comes from window.sg_categories injected by PHP.
    ---------------------------------------------------------------- */
    function injectCategoryNav(attempt) {
        if (isMobile) return;
        if (!window.sg_categories || !window.sg_categories.length) return;
        if (document.querySelector('.sg-cat-nav-inline')) return;

        // Inject into the existing .nbd-main-bar, before .menu-right
        var mainBar   = document.querySelector('.nbd-main-bar');
        var menuRight = mainBar && mainBar.querySelector('.menu-right');
        if (!mainBar || !menuRight) {
            // Angular renders .nbd-main-bar async — retry up to 10 s
            if ((attempt || 0) < 20) {
                setTimeout(function () { injectCategoryNav((attempt || 0) + 1); }, 500);
            }
            return;
        }

        var currentIds = window.sg_current_cat_ids || [];

        var container = document.createElement('div');
        container.className = 'sg-cat-nav-inline';

        window.sg_categories.forEach(function (cat) {
            var isActive = currentIds.indexOf(cat.id) >= 0;
            var a = document.createElement('a');
            a.className = 'sg-cat-item'
                + (isActive   ? ' sg-cat-active' : '')
                + (cat.is_new ? ' sg-cat-is-new'  : '');
            a.href  = cat.link;
            a.title = cat.name;

            var iconHtml;
            if (cat.image) {
                iconHtml = '<span class="sg-cat-icon"><img src="' + escHtml(cat.image)
                    + '" alt="" loading="lazy"></span>';
            } else {
                iconHtml = '<span class="sg-cat-icon"><span class="sg-cat-icon-letter">'
                    + escHtml(cat.name.charAt(0).toUpperCase()) + '</span></span>';
            }

            a.innerHTML = (cat.is_new ? '<span class="sg-cat-new-badge">NEW</span>' : '')
                        + iconHtml
                        + '<span class="sg-cat-label">' + escHtml(cat.name) + '</span>';
            container.appendChild(a);
        });

        // Slot into center of existing top bar — before .menu-right
        mainBar.insertBefore(container, menuRight);
        document.body.classList.add('sg-has-catnav');
    }

    /* ----------------------------------------------------------------
       AUTH BUTTON — "Log in" (guest) or hidden (logged in)
       PHP injects window.sg_is_logged_in, sg_login_url, sg_account_url,
       sg_user_name so we don't need an AJAX call.
    ---------------------------------------------------------------- */
    function injectAuthButton() {
        if (isMobile) return;
        if (document.querySelector('.sg-login-btn')) return;

        // Logged-in users: show a compact account pill (avatar + name → account page)
        if (window.sg_is_logged_in) {
            if (document.querySelector('.sg-account-pill')) return;
            var menuRight = document.querySelector('.nbd-main-bar .menu-right');
            if (!menuRight) {
                var retries2 = 0;
                var t2 = setInterval(function () {
                    retries2++;
                    if (document.querySelector('.nbd-main-bar .menu-right')) {
                        clearInterval(t2); injectAuthButton();
                    }
                    if (retries2 > 20) clearInterval(t2);
                }, 300);
                return;
            }
            var name   = (window.sg_user_name || 'Account').trim();
            var initials = name.split(' ').slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
            var pill = document.createElement('a');
            pill.className   = 'sg-account-pill';
            pill.href        = window.sg_account_url || '#';
            pill.title       = 'My account';
            pill.innerHTML   =
                '<span class="sg-account-avatar">' + escHtml(initials) + '</span>' +
                '<span class="sg-account-name">'   + escHtml(name)     + '</span>';
            menuRight.insertBefore(pill, menuRight.firstChild);
            return;
        }

        var menuRight = document.querySelector('.nbd-main-bar .menu-right');
        if (!menuRight) {
            var retries = 0;
            var t = setInterval(function () {
                retries++;
                if (document.querySelector('.nbd-main-bar .menu-right')) {
                    clearInterval(t); injectAuthButton();
                }
                if (retries > 20) clearInterval(t);
            }, 300);
            return;
        }

        var a = document.createElement('a');
        a.className = 'sg-login-btn';
        a.href = '#';
        a.setAttribute('title', 'Log in');
        a.innerHTML =
            '<svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>' +
                '<circle cx="12" cy="7" r="4"/>' +
            '</svg>' +
            'Log in';

        a.addEventListener('click', function (e) {
            e.preventDefault();
            openLoginModal();
        });

        menuRight.appendChild(a);
    }

    /* ----------------------------------------------------------------
       LOGIN MODAL
       Inline login popup — submits via AJAX, reloads page on success.
    ---------------------------------------------------------------- */
    function openLoginModal() {
        var modal = document.getElementById('sg-login-modal');
        if (!modal) {
            injectLoginModal();
            modal = document.getElementById('sg-login-modal');
        }
        if (!modal) return;
        // Clear previous state
        var uEl = document.getElementById('sg-login-username');
        var pEl = document.getElementById('sg-login-password');
        if (uEl) uEl.value = '';
        if (pEl) pEl.value = '';
        hideLoginError();
        resetLoginBtn();
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (uEl) setTimeout(function () { uEl.focus(); }, 80);
    }

    function closeLoginModal() {
        var modal = document.getElementById('sg-login-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function injectLoginModal() {
        if (document.getElementById('sg-login-modal')) return;

        var el = document.createElement('div');
        el.id = 'sg-login-modal';
        el.className = 'sg-login-modal-overlay';
        el.style.display = 'none';
        el.innerHTML =
            '<div class="sg-login-modal-card" role="dialog" aria-modal="true" aria-labelledby="sg-login-modal-title">' +
                '<button class="sg-login-modal-close" id="sg-login-modal-close" aria-label="Close">&times;</button>' +

                '<div class="sg-login-modal-head">' +
                    '<div class="sg-login-modal-icon" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                            '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>' +
                            '<circle cx="12" cy="7" r="4"/>' +
                        '</svg>' +
                    '</div>' +
                    '<h2 id="sg-login-modal-title">Welcome back</h2>' +
                    '<p>Sign in to your account to continue</p>' +
                '</div>' +

                '<div class="sg-login-field">' +
                    '<label for="sg-login-username">Username or Email</label>' +
                    '<input type="text" id="sg-login-username" placeholder="you@example.com" autocomplete="username">' +
                '</div>' +
                '<div class="sg-login-field">' +
                    '<label for="sg-login-password">Password</label>' +
                    '<div class="sg-login-pw-wrap">' +
                        '<input type="password" id="sg-login-password" placeholder="••••••••" autocomplete="current-password">' +
                        '<button type="button" class="sg-login-pw-toggle" id="sg-login-pw-toggle" aria-label="Show password" tabindex="-1">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                                '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>' +
                                '<circle cx="12" cy="12" r="3"/>' +
                            '</svg>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<div class="sg-login-remember">' +
                    '<label><input type="checkbox" id="sg-login-remember"> Remember me</label>' +
                    '<a href="' + escHtml(window.sg_login_url ? window.sg_login_url.replace(/\?.*/, '') + '?action=lostpassword' : '#') + '" target="_blank" class="sg-login-forgot">Forgot password?</a>' +
                '</div>' +

                '<div class="sg-login-error" id="sg-login-error" style="display:none"></div>' +

                '<button class="sg-login-submit" id="sg-login-submit">Log In</button>' +
            '</div>';

        document.body.appendChild(el);

        // Close on overlay click
        el.addEventListener('click', function (e) {
            if (e.target === el) closeLoginModal();
        });

        document.getElementById('sg-login-modal-close').addEventListener('click', closeLoginModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.getElementById('sg-login-modal').style.display !== 'none') {
                closeLoginModal();
            }
        });

        // Enter key submits
        ['sg-login-username', 'sg-login-password'].forEach(function (id) {
            var inp = document.getElementById(id);
            if (inp) inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') submitLogin();
            });
        });

        // Password visibility toggle
        document.getElementById('sg-login-pw-toggle').addEventListener('click', function () {
            var inp = document.getElementById('sg-login-password');
            if (!inp) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
        });

        document.getElementById('sg-login-submit').addEventListener('click', submitLogin);
    }

    function submitLogin() {
        var username = (document.getElementById('sg-login-username').value || '').trim();
        var password = document.getElementById('sg-login-password').value || '';
        var remember = document.getElementById('sg-login-remember').checked;

        if (!username || !password) {
            showLoginError('Please enter your username and password.');
            return;
        }

        var btn = document.getElementById('sg-login-submit');
        if (btn) { btn.disabled = true; btn.textContent = 'Logging in…'; }
        hideLoginError();

        var body = new FormData();
        body.append('action',   'sg_dui_login');
        body.append('_nonce',   window.sg_login_nonce || '');
        body.append('username', username);
        body.append('password', password);
        body.append('remember', remember ? '1' : '');

        var ajaxUrl = window.sg_ajax_url || '/wp-admin/admin-ajax.php';
        fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    // Auth cookie is now set — reload to reflect logged-in state
                    window.location.reload();
                } else {
                    var msg = (data.data && data.data.message) ? data.data.message : 'Login failed.';
                    showLoginError(msg);
                    resetLoginBtn();
                }
            })
            .catch(function () {
                showLoginError('Network error. Please try again.');
                resetLoginBtn();
            });
    }

    function showLoginError(msg) {
        var el = document.getElementById('sg-login-error');
        if (el) { el.textContent = msg; el.style.display = ''; }
    }
    function hideLoginError() {
        var el = document.getElementById('sg-login-error');
        if (el) { el.textContent = ''; el.style.display = 'none'; }
    }
    function resetLoginBtn() {
        var btn = document.getElementById('sg-login-submit');
        if (btn) { btn.disabled = false; btn.textContent = 'Log In'; }
    }

    /* ----------------------------------------------------------------
       WOOCOMMERCE CART ICON BUTTON
       Injects a cart icon link into .menu-right showing the current
       WC cart item count (data injected by PHP via window.sg_cart_*).
       The Process button is hidden by CSS but stays in DOM so the
       right-panel "Add to Cart" can still trigger scope.saveData().
    ---------------------------------------------------------------- */
    function injectCartButton() {
        if (document.querySelector('.sg-cart-btn')) return;

        var menuRight = document.querySelector('.nbd-main-bar .menu-right');
        if (!menuRight) {
            // Retry — bar may not be rendered yet
            var retries = 0;
            var t = setInterval(function () {
                retries++;
                if (document.querySelector('.nbd-main-bar .menu-right')) {
                    clearInterval(t); injectCartButton();
                }
                if (retries > 20) clearInterval(t);
            }, 300);
            return;
        }

        var cartUrl   = window.sg_cart_url   || '#';
        var cartCount = window.sg_cart_count  || 0;

        var a = document.createElement('a');
        a.className = 'sg-cart-btn';
        a.href = cartUrl;
        a.setAttribute('title', 'View Cart');
        a.setAttribute('aria-label', 'View Cart (' + cartCount + ' items)');

        a.innerHTML =
            '<svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                '<circle cx="9" cy="21" r="1"/>' +
                '<circle cx="20" cy="21" r="1"/>' +
                '<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>' +
            '</svg>' +
            (cartCount > 0
                ? '<span class="sg-cart-count">' + cartCount + '</span>'
                : '');

        // Insert as first child of .menu-right (left of share icon)
        menuRight.insertBefore(a, menuRight.firstChild);
    }

    function init(workspace) {
        disableSidebarAnimations();
        initEmptyCanvasOverlay();
        initAutoSizeFromImage();
        initUploadCaptureHook();
        initUploadThumbnailMeta();
        initFitCenterFloatingToolbar();
        initPhotosTabFirst();
        initDimensionFieldRestyle();
        initSourceTileActiveState();
        initSoleUploadSourceAutoOpen();
        if (!isMobile) {
            injectSummaryPanel(workspace);
            initB2BBadge();
            startOptionsMirror();
            startPriceMirror();
            initCanvasCentering();
        }
    }

    /* ----------------------------------------------------------------
       PHOTOS TAB FIRST + OPEN BY DEFAULT
       On signs365.com the uploaded-photo panel is always the first thing
       a customer sees. Core's sidebar defaults to whatever tab order/active
       tab the product config produces (Design/Templates/Text/Cliparts
       first, Photos further down), so customers have to notice and click
       the Photos icon themselves before they can even see their uploads.

       Core's tab-click handler (designer-modern.min.js, the `tab: function`
       inside the sidebar module) pairs each nav <li> with a
       .tabs-content panel by LIVE DOM POSITION at click time
       ($(this).index(), not by id/data-tab) — confirmed live: moving
       only #nav-photos left #tab-photo's content behind, so every tab
       after the swap ended up showing the WRONG panel (the one that used
       to sit in that slot). The nav <li> and its .tabs-content panel
       both have to move to the front together.

       One wrinkle: the very first <li> (class "layerTab" — core's
       "close side panel, show blank canvas" pseudo-tab) deliberately has
       NO matching content panel; core's own index math subtracts 1 to
       account for it. So Photos must land right AFTER layerTab (content
       index 0, the real first panel), never before it (which would
       compute to content index -1 — clicking it would show nothing).
    ---------------------------------------------------------------- */
    function initPhotosTabFirst(attempt) {
        var mainTabs = document.querySelector('.tabs-nav .main-tabs');
        var tabsContent = document.querySelector('.tabs-content');
        var navPhotos = document.getElementById('nav-photos');
        var tabPhoto = document.getElementById('tab-photo');
        if (!mainTabs || !tabsContent || !navPhotos || !tabPhoto) {
            if ((attempt || 0) < 30) {
                setTimeout(function () { initPhotosTabFirst((attempt || 0) + 1); }, 300);
            }
            return;
        }

        var layerTab = mainTabs.querySelector('li.tab.layerTab');
        var navAnchor = layerTab ? layerTab.nextElementSibling : mainTabs.querySelector('li.tab');

        if (navAnchor && navAnchor !== navPhotos) {
            mainTabs.insertBefore(navPhotos, navAnchor);
            var firstContentTab = tabsContent.querySelector(':scope > .tab');
            if (firstContentTab && firstContentTab !== tabPhoto) {
                tabsContent.insertBefore(tabPhoto, firstContentTab);
            }
        }

        if (window.jQuery && !navPhotos.__sgOpenedOnLoad) {
            navPhotos.__sgOpenedOnLoad = true;
            decideDefaultPhotosTab();
        }
    }

    // Dimension-field products (Custom Size) let the customer configure
    // that field first — auto-size-from-image reads it once they pick an
    // image, and forcing Photos open by default would just get in the way
    // of that configure-first flow. Keep core's own default tab there and
    // rely on the empty-canvas "click to select an image" overlay
    // (openPhotoUpload) to jump to Photos when the customer is ready.
    // Everything else defaults straight to Photos/Upload, signs365-style.
    // Needs its own short readiness wait: hasDimensionField() depends on
    // the NBO options scope, which can still be empty for a moment after
    // the tab DOM itself already exists.
    function decideDefaultPhotosTab(attempt) {
        var nboScope = getNboScope();
        var ready = nboScope && Array.isArray(nboScope.fields) && nboScope.fields.length > 0;
        if (!ready && (attempt || 0) < 20) {
            setTimeout(function () { decideDefaultPhotosTab((attempt || 0) + 1); }, 300);
            return;
        }
        if (!hasDimensionField()) {
            tryOpenPhotosTabDefault();
        }
    }

    // Separated from initPhotosTabFirst because the click has to actually
    // "stick" before drilling one level deeper into Upload — confirmed
    // live: firing the click immediately on load sometimes had no visible
    // effect (Printing/Product stayed active), most likely because core's
    // own plain-jQuery tab-click handler (designer-modern.min.js) hadn't
    // finished binding yet when this ran — Angular being ready (which is
    // what the rest of override.js waits on) doesn't guarantee that
    // unrelated jQuery init has also run. Verify the click took effect via
    // the resulting .active class and retry if not, instead of assuming.
    function tryOpenPhotosTabDefault(attempt) {
        var jQuery = window.jQuery;
        var navPhotos = document.getElementById('nav-photos');
        if (!jQuery || !navPhotos) return;

        jQuery('#nav-photos').triggerHandler('click');
        setTimeout(function () {
            if (navPhotos.classList.contains('active')) {
                // One more level in: reveal the "Upload" section (existing
                // uploads + drop zone) instead of leaving the customer on
                // the source-picker grid (Upload/Webcam/Pixabay/...). This
                // only switches the visible sub-panel — it does NOT pop the
                // native OS file dialog (that needs a real user gesture on
                // the actual <input type=file>, and popping it unprompted
                // on page load would be unwanted anyway).
                //
                // $scope.onClickTab (app-modern.min.js) is a literal TOGGLE:
                // `resource[tab].onclick = !resource[tab].onclick` — clicking
                // this tile again while the Upload section is ALREADY open
                // closes it back to the grid instead of doing nothing.
                // Confirmed live: reopening the empty-canvas overlay after
                // deleting a layer and clicking it again flipped the panel
                // shut. Only click when it isn't already open.
                var tabPhotoEl = document.getElementById('tab-photo');
                var scope;
                try {
                    scope = tabPhotoEl && window.angular && angular.element(tabPhotoEl).scope();
                } catch (e) { scope = null; }
                var alreadyOpen = !!(scope && scope.resource && scope.resource.photo && scope.resource.photo.onclick);
                if (!alreadyOpen) {
                    jQuery('[data-type="image-upload"]').click();
                }
                // Opened without a real click on the Upload tile, so mark it
                // active manually — otherwise no tile looks active at all
                // until the customer clicks one themselves.
                markSourceTileActive(document.querySelector('#tab-photo .item[data-type="image-upload"]'));
            } else if ((attempt || 0) < 15) {
                setTimeout(function () { tryOpenPhotosTabDefault((attempt || 0) + 1); }, 300);
            }
        }, 150);
    }

    /* ----------------------------------------------------------------
       DIM INACTIVE PHOTO SOURCE TILES
       $scope.onClickTab (app-modern.min.js) is a plain boolean TOGGLE per
       source, not "switch to this source" — clicking Image url while
       Upload's drop-zone is showing just closes Upload, it doesn't itself
       make it obvious Image url is now the active one (confirmed live:
       customers found this confusing, clicking another tile "looked like"
       it just closed the current one instead of opening a new source).
       Visually dim every OTHER tile once one is known to be active, purely
       via a plain click listener + CSS — no Angular scope reading, so it
       can't be thrown off by which of the dual [ng-controller] instances
       happens to be live.

       Clicking the CURRENTLY active tile again toggles it CLOSED (back to
       the plain grid, nothing open) — that has to clear the active state
       entirely, not re-mark the same tile active, or it stays highlighted/
       dimmed as if something were still open (confirmed live: closing
       Upload this way left every other tile wrongly dimmed even though
       the grid had gone back to its normal, nothing-open state).

       Clicking a DIFFERENT tile while one is already open ALSO just
       closes everything, rather than switching to the new one — because
       resource[tab].onclick is a single SHARED flag (tab is always the
       literal string 'photo', not the clicked source's type), so any
       click while it's true flips it to false regardless of which tile
       triggered it (confirmed live: clicking Upload while Image url was
       open reset the whole grid to bright/nothing-active instead of
       switching to Upload — matches the same "takes two clicks to switch
       sources" quirk this session already found for Upload specifically).
       So: click while something is active → always clear, no matter
       which tile was clicked; click while nothing is active → open that
       tile. A second click on that same (now newly reopened) tile is
       what actually shows its content, matching core's real behavior.
    ---------------------------------------------------------------- */
    var sgActiveSourceTile = null;

    function markSourceTileActive(tile) {
        if (!tile) return;
        var itemsGrid = tile.closest('.items');
        if (!itemsGrid) return;
        sgActiveSourceTile = tile;
        itemsGrid.classList.add('sg-has-active');
        itemsGrid.querySelectorAll('.item').forEach(function (el) {
            el.classList.toggle('sg-source-active', el === tile);
        });
    }

    function clearSourceTileActive(itemsGrid) {
        sgActiveSourceTile = null;
        if (!itemsGrid) return;
        itemsGrid.classList.remove('sg-has-active');
        itemsGrid.querySelectorAll('.item').forEach(function (el) {
            el.classList.remove('sg-source-active');
        });
    }

    function initSourceTileActiveState() {
        // Delegated on `document` (capture phase) — the same reliable
        // attachment point found for the (now-reverted) source-collapse
        // toggle; a listener on #tab-photo itself never sees clicks here
        // either, something between document and it swallows them first.
        if (document.__sgSourceActiveWired) return;
        document.__sgSourceActiveWired = true;
        document.addEventListener('click', function (e) {
            var tile = e.target.closest('#tab-photo .items .item[data-type]');
            if (!tile) return;
            if (sgActiveSourceTile) {
                clearSourceTileActive(tile.closest('.items'));
            } else {
                markSourceTileActive(tile);
            }
        }, true);
    }
    
    function isUploadTheSoleSource() {
        var itemsGrid = document.querySelector('#tab-photo .items');
        if (!itemsGrid) return false;
        var tiles = itemsGrid.querySelectorAll('.item');
        return tiles.length === 1 && tiles[0].getAttribute('data-type') === 'image-upload';
    }

    function ensureSoleUploadOpen() {
        if (!isUploadTheSoleSource()) return;
        var jQuery = window.jQuery;
        if (!jQuery) return;
        var tabPhotoEl = document.getElementById('tab-photo');
        var scope;
        try {
            scope = tabPhotoEl && window.angular && angular.element(tabPhotoEl).scope();
        } catch (e) { scope = null; }
        // Same shared-toggle quirk documented in tryOpenPhotosTabDefault
        // above — only click if it isn't already open, or it flips shut.
        var alreadyOpen = !!(scope && scope.resource && scope.resource.photo && scope.resource.photo.onclick);
        if (!alreadyOpen) {
            jQuery('[data-type="image-upload"]').click();
        }
    }
    
    

    function initSoleUploadSourceAutoOpen(attempt) {
        var uploadItem = document.querySelector('#tab-photo .item[data-type="image-upload"]');
        if (!uploadItem) {
            if ((attempt || 0) < 30) {
                setTimeout(function () { initSoleUploadSourceAutoOpen((attempt || 0) + 1); }, 300);
            }
            return;
        }
        setInterval(function () {
            var liveUploadItem = document.querySelector('#tab-photo .item[data-type="image-upload"]');
            if (liveUploadItem && !liveUploadItem.classList.contains('sg-source-hidden') && isUploadTheSoleSource()) {
                liveUploadItem.classList.add('sg-source-hidden');
            }
        }, 500);


        // Delegated on `document` (capture phase) — #nav-photos sits
        // outside .nbd-items-dropdown, so it likely isn't affected by
        // whatever swallows clicks there, but this matches the one
        // attachment point already confirmed reliable everywhere else in
        // this file rather than assuming a different one is safe here too.
        if (!document.__sgSoleUploadWired) {
            document.__sgSoleUploadWired = true;
            document.addEventListener('click', function (e) {
                if (!e.target.closest('#nav-photos')) return;
                setTimeout(ensureSoleUploadOpen, 150);
            }, true);
        }

        ensureSoleUploadOpen();
    }

    /* ----------------------------------------------------------------
       CUSTOM SIZE (DIMENSION) FIELD — REBUILD WIDTH/HEIGHT ROW
       Core (options-builder/input-2.php) renders the Width/Height
       stepper as two independent inline-block <span>s separated by a
       <br>, each holding a label + a "-[input]+" group, followed by a
       shared unit <span> and some <small> hints — no wrapper ties Width
       and Height together. Several rounds of CSS-only fixes (:has() to
       impose a flex row over that existing markup) kept rendering a
       crooked, staircase-like result live that never fully resolved.
       Simpler and more reliable: physically MOVE those existing nodes
       (not clone — Angular's compiled bindings on the -/+ buttons stay
       intact since they're attached to the DOM nodes themselves, not to
       tree position) into one new wrapper div we control outright, then
       style that wrapper in override.css (.sg-dimension-row).
    ---------------------------------------------------------------- */
    function initDimensionFieldRestyle(attempt) {
        var wraps = document.querySelectorAll('#nbo-options-wrap .nbo-dimension-wrap');
        if (!wraps.length) {
            if ((attempt || 0) < 30) {
                setTimeout(function () { initDimensionFieldRestyle((attempt || 0) + 1); }, 300);
            }
            return;
        }
        var processed = [];
        for (var i = 0; i < wraps.length; i++) {
            var td = wraps[i].closest('.nbd-field-content');
            if (td && processed.indexOf(td) === -1) {
                processed.push(td);
                restyleDimensionField(td);
            }
        }
    }

    function restyleDimensionField(td) {
        if (td.__sgDimensionRestyled) return;

        var directSpans = [];
        for (var i = 0; i < td.children.length; i++) {
            var c = td.children[i];
            var styleAttr = c.tagName === 'SPAN' && c.getAttribute('style');
            if (styleAttr && styleAttr.indexOf('inline-block') > -1) directSpans.push(c);
        }
        // Expect exactly 2: Width's wrapping span, Height's wrapping span.
        // Anything else means this <td> doesn't match the shape we expect
        // (e.g. a future core update) — leave it alone rather than guess.
        if (directSpans.length !== 2) return;

        var widthSpan = directSpans[0];
        var heightSpan = directSpans[1];
        // Capture these BEFORE any DOM mutation — nextElementSibling is a
        // live reference and would point somewhere else once nodes below
        // start getting moved/removed.
        var strayBr = widthSpan.nextElementSibling;   // the <br> originally between Width and Height
        // The shared unit text ("in"/"cm") sits right after Height's
        // wrapping span in the original markup — confirmed live via
        // DevTools it actually carries class "nbo-dimension-unit" (not
        // class-less as the template source read earlier suggested; this
        // site's build differs slightly). By position it's the only thing
        // that can be there, so just check it's a plain <span>.
        var unitSpan = heightSpan.nextElementSibling;
        var hasUnit = unitSpan && unitSpan.tagName === 'SPAN';

        var row = document.createElement('div');
        row.className = 'sg-dimension-row';
        widthSpan.parentNode.insertBefore(row, widthSpan);
        row.appendChild(widthSpan);
        row.appendChild(heightSpan);
        if (hasUnit) row.appendChild(unitSpan);
        // Left behind in the <td> after moving both spans out — a stray
        // line break that would otherwise open an unwanted gap before the
        // min/max hint text below the row.
        if (strayBr && strayBr.tagName === 'BR' && strayBr.parentNode) {
            strayBr.parentNode.removeChild(strayBr);
        }

        td.__sgDimensionRestyled = true;
    }

    /* ----------------------------------------------------------------
       EMPTY-CANVAS "CLICK TO UPLOAD" OVERLAY
       Puts a dashed "click to select an image" box on top of each
       empty stage's canvas (like signs365.com) so customers can start
       uploading immediately instead of having to find the Photos tab
       in the sidebar first. Hides itself as soon as the stage has at
       least one layer, and reappears if the canvas is cleared.

       IMPORTANT: whenever the customer picks printing options/dimensions,
       the core app rebuilds `$scope.stages` from scratch (see
       app-modern.min.js, `$scope.stages = [...]`), which makes Angular
       destroy and recreate every .stage DOM node — including whatever
       we injected into it — and attach a brand new fabric.Canvas
       instance. A one-shot injection therefore gets wiped out the first
       time the customer actually configures the product. We watch
       #nbd-stages with a MutationObserver and re-reconcile (re-inject /
       rebind) on every DOM change instead of injecting once.
    ---------------------------------------------------------------- */
    function initEmptyCanvasOverlay(attempt) {
        var stagesRoot = document.getElementById('nbd-stages');
        if (!stagesRoot) {
            if ((attempt || 0) < 20) {
                setTimeout(function () { initEmptyCanvasOverlay((attempt || 0) + 1); }, 500);
            }
            return;
        }

        var reconcileTimer = null;
        function scheduleReconcile() {
            clearTimeout(reconcileTimer);
            reconcileTimer = setTimeout(reconcileEmptyCanvasOverlays, 200);
        }

        new MutationObserver(scheduleReconcile).observe(stagesRoot, { childList: true, subtree: true });
        scheduleReconcile();

        // Belt-and-suspenders: some canvas rebuilds reuse the very same
        // <canvas> DOM node (fabric disposes + re-inits in place), which
        // produces no childList mutation for the observer to catch. A
        // cheap poll guarantees we still notice the swap and rebind.
        setInterval(reconcileEmptyCanvasOverlays, 1000);
    }

    function reconcileEmptyCanvasOverlays() {
        if (!window.angular) return;
        var ngRoot = document.querySelector('[ng-controller]');
        if (!ngRoot) return;

        var scope;
        try { scope = angular.element(ngRoot).scope(); } catch (e) { return; }
        if (!scope || !scope.stages || !scope.stages.length) return;

        // No Photos tab enabled on this product → nothing useful to link to.
        if (!document.getElementById('nav-photos')) return;

        // This overlay's whole pitch ("PLEASE SPECIFY DIMENSIONS OR CLICK TO
        // SELECT AN IMAGE") only makes sense on products with a custom-size
        // dimension field, where picking an image really does resize the
        // canvas to match it (see initAutoSizeFromImage above). On a
        // fixed-size product, clicking through it just adds the image at
        // whatever size the product already is — the promise in the text
        // would be false, and misleading about what's about to happen. Skip
        // showing it at all there; the canvas falls back to whatever the
        // core plugin renders for an empty canvas by default.
        if (!hasDimensionField()) return;

        scope.stages.forEach(function (stage, index) {
            var canvas = stage.canvas;
            // Config/dimensions not chosen yet, or fabric hasn't attached
            // its canvas to this stage — skip, a later mutation will retry.
            if (!canvas || typeof canvas.getObjects !== 'function') return;

            var stageEl = document.getElementById('stage-container-' + index);
            var designWrap = stageEl && stageEl.querySelector('.design-wrap');
            if (!designWrap) return;

            var overlay = designWrap.querySelector('.sg-empty-canvas-overlay');
            if (overlay && overlay.sgCanvas === canvas) return; // already bound to the live canvas

            if (overlay) overlay.parentNode.removeChild(overlay); // bound to a disposed canvas — rebuild

            overlay = document.createElement('div');
            overlay.className = 'sg-empty-canvas-overlay';
            overlay.sgCanvas = canvas;
            overlay.innerHTML =
                '<div class="sg-empty-canvas-box">' +
                    '<svg viewBox="0 0 24 24"><path d="M4 16l4.6-6 3.4 4 2.6-3L20 16"/><circle cx="8" cy="8" r="1.6"/>' +
                    '<rect x="3" y="4" width="18" height="16" rx="2"/></svg>' +
                    '<span>Please specify dimensions or<br>click to select an image</span>' +
                '</div>';
            designWrap.appendChild(overlay);

            (function (overlay, canvas) {
                function refresh() {
                    overlay.classList.toggle('is-visible', canvas.getObjects().length === 0);
                }
                canvas.on('object:added', refresh);
                canvas.on('object:removed', refresh);
                refresh();
            })(overlay, canvas);

            // Opens the Photos tab → Upload section so the customer can
            // pick from their existing uploads (or drag/drop a new one
            // manually) — NOT straight to the OS file dialog. Was
            // openPhotoUpload() (jumps straight to the native picker) until
            // explicitly asked to go back to just opening the tab/section
            // like before.
            overlay.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                tryOpenPhotosTabDefault();
            });
        });
    }

    // Same "does this product have a custom Width/Height field" check as
    // tryAutoSizeThenAdd() below — kept as its own small helper since it's
    // now needed before that whole flow even starts (to decide whether to
    // show the empty-canvas overlay at all).
    //
    // A dimension field existing in nboScope.fields isn't enough on its
    // own — the admin can disable/unpublish a field without deleting it
    // (confirmed live: doing that left the field out of the visible
    // sidebar entirely, yet fields still listed it, so a type-only check
    // kept showing the overlay). ng-repeat's own visibility rule for NBO
    // fields is `ng-show="field.enable && field.published"` (option-builder.php:419)
    // — checking the SAME two flags on nbd_fields[id] here mirrors exactly
    // what actually renders to the customer.
    function hasDimensionField() {
        var nboScope = getNboScope();
        if (!nboScope || !nboScope.fields || !nboScope.nbd_fields) return false;
        for (var i = 0; i < nboScope.fields.length; i++) {
            var f = nboScope.fields[i];
            if (!f || f.nbd_type !== 'dimension') continue;
            var live = nboScope.nbd_fields[f.id];
            if (live && live.enable && live.published) return true;
        }
        return false;
    }

    // Opens the Photos tab, reveals the Upload tile's drag-drop section,
    // then clicks the REAL hidden <input type="file"> inside it
    // (#tab-photo .form-upload input[type=file] — see tab-photo.php) to pop
    // the native OS file picker. Earlier version clicked
    // [data-type="image-upload"] a second time instead of the actual input
    // — that tile only switches from the source-picker grid (Upload/Image
    // url/Webcam/...) to the drag-drop view; it doesn't open the file
    // dialog itself, so nothing visibly happened (confirmed live: it just
    // left the customer looking at the same "Click or drop images here"
    // panel "My Photos" already reaches, one real click short of an
    // actual file dialog).
    function openPhotoUpload(attempt) {
        if (!window.jQuery) return;
        var jQuery = window.jQuery;
        var fileInput = document.querySelector('#tab-photo .form-upload input[type="file"]');
        if (fileInput) {
            fileInput.click();
            return;
        }
        jQuery('#nav-photos').triggerHandler('click');
        jQuery('[data-type="image-upload"]').click();
        if ((attempt || 0) < 15) {
            setTimeout(function () { openPhotoUpload((attempt || 0) + 1); }, 250);
        }
    }

    /* ----------------------------------------------------------------
       AUTO-SIZE CANVAS FROM THE CHOSEN IMAGE
       Some products expose a custom "Size" option (NBO field with
       nbd_type == 'dimension' — Width/Height inputs that directly
       drive the canvas size via applyOptions(), which rebuilds
       $scope.stages from scratch). For those products, when the
       customer actually picks an image to use — $scope.addImageFromUrl,
       called when clicking an uploaded thumbnail — read that image's
       real pixel size and set Width/Height to match (via
       nbdesigner_default_dpi), then auto-apply, mirroring signs365.com.

       Products with NO dimension field are completely unaffected: the
       check below returns before touching anything else, so this can't
       repeat the earlier mistake of firing on unrelated products/state.

       Sequencing matters: applyOptions() destroys and rebuilds the
       canvas, so the image must be added AFTER the resize finishes, not
       before — otherwise the just-placed image would be wiped out by
       the rebuild. That's why the original addImageFromUrl call is
       deferred until the freshly-sized canvas exists, instead of
       running immediately.
    ---------------------------------------------------------------- */
    function initAutoSizeFromImage(attempt) {
        if (!window.angular) return;
        var ngRoot = document.querySelector('[ng-controller]');
        if (!ngRoot) return;

        var scope;
        try { scope = angular.element(ngRoot).scope(); } catch (e) { scope = null; }

        if (!scope || typeof scope.addImageFromUrl !== 'function') {
            if ((attempt || 0) < 20) {
                setTimeout(function () { initAutoSizeFromImage((attempt || 0) + 1); }, 500);
            }
            return;
        }
        if (scope.__sgAutoSizeWrapped) return; // don't double-wrap on re-init
        scope.__sgAutoSizeWrapped = true;

        var original = scope.addImageFromUrl;
        scope.addImageFromUrl = function (dataImg, extenal, ilr, maskId) {
            if (tryAutoSizeThenAdd(scope, dataImg, extenal, ilr, maskId, original)) return;
            return original.call(scope, dataImg, extenal, ilr, maskId);
        };
    }
    
    function hideStagesForResize() {
        var stagesEl = document.getElementById('nbd-stages');
        if (stagesEl) {
            stagesEl.style.transition = 'none';
            stagesEl.style.opacity = '0';
        }
        // Guaranteed fallback reveal — never leave the canvas invisible if
        // something in the chain below bails out early (image failed to
        // load, dimension field missing after all, retries exhausted).
        setTimeout(revealStagesAfterResize, 4000);
    }

    function revealStagesAfterResize() {
        var stagesEl = document.getElementById('nbd-stages');
        if (stagesEl) {
            stagesEl.style.transition = 'opacity .25s ease-in';
            stagesEl.style.opacity = '1';
        }
    }

    // Returns true if it took over (image + dimension field both found —
    // the original add-to-canvas call will happen later, after resize).
    // Returns false to let the caller fall back to the normal behaviour
    // immediately (no dimension field on this product, or no usable URL).
    function tryAutoSizeThenAdd(scope, dataImg, extenal, ilr, maskId, original) {
        var nboScope = getNboScope();
        if (!nboScope || !nboScope.fields || !nboScope.nbd_fields ||
            typeof nboScope.update_dimensionvalue !== 'function' ||
            typeof nboScope.applyOptions !== 'function') {
            return false;
        }

        // Match option-builder.php's own visibility rule for NBO fields —
        // ng-show="field.enable && field.published" (line 419) — so a field
        // the admin disabled/unpublished (still present in `fields`, just
        // not rendered) doesn't silently drive a resize the customer never
        // even sees a Width/Height input for.
        var dimensionField = null;
        for (var i = 0; i < nboScope.fields.length; i++) {
            var f = nboScope.fields[i];
            if (f && f.nbd_type === 'dimension') {
                var live = nboScope.nbd_fields[f.id];
                if (live && live.enable && live.published) { dimensionField = f; break; }
            }
        }
        if (!dimensionField || !nboScope.nbd_fields[dimensionField.id]) return false;

        var url = (typeof dataImg === 'object' && dataImg) ? (dataImg.url || dataImg.src) : dataImg;
        if (!url) return false;

        var fallbackToNormalAdd = function () {
            original.call(scope, dataImg, extenal, ilr, maskId);
        };

        var probe = new Image();
        probe.onerror = fallbackToNormalAdd;
        probe.onload = function () {
            if (!probe.naturalWidth || !probe.naturalHeight) { fallbackToNormalAdd(); return; }

            var s = getNboScope(); // re-fetch — time has passed since the initial lookup
            if (!s || !s.nbd_fields || !s.nbd_fields[dimensionField.id]) { fallbackToNormalAdd(); return; }

            // settings.product_data.option.dpi is the per-PRODUCT DPI (admin:
            // Edit Product → Setting PCDesigner → "DPI (Dots Per Inch)"),
            // already resolved server-side with a fallback to the global
            // nbdesigner_default_dpi option, and available from page load.
            // (resource.config.dpi looks like the same value but is only
            // copied there inside _saveData() — i.e. it stays {} until the
            // customer clicks Save/Add to Cart, so it's useless here.
            // settings.nbdesigner_default_dpi is the global-only fallback and
            // ignores any per-product override — don't read that either.)
            var dpi = parseFloat(s.settings && s.settings.product_data && s.settings.product_data.option &&
                s.settings.product_data.option.dpi) || 150;
            var widthIn = Math.round((probe.naturalWidth / dpi) * 1000) / 1000;
            var heightIn = Math.round((probe.naturalHeight / dpi) * 1000) / 1000;

            function commit() {
                s.nbd_fields[dimensionField.id].width = widthIn;
                s.nbd_fields[dimensionField.id].height = heightIn;
                s.update_dimensionvalue(dimensionField.id, 'width');
                s.update_dimensionvalue(dimensionField.id, 'height');
                s.applyOptions();
            }
            // Capture the canvas BEFORE triggering the rebuild: applyOptions()
            // runs on the next digest (via $apply/$evalAsync below), not
            // synchronously, so the very first poll tick in
            // waitForFreshCanvasThenAdd would otherwise see this SAME
            // still-valid old canvas and treat it as "the rebuilt one",
            // adding the image to a canvas that's about to be torn down and
            // replaced a moment later — the image would vanish right after,
            // which looked like "first click only resizes, need a second
            // click to actually add".
            var stageBefore = scope.stages && scope.stages[scope.currentStage];
            var oldCanvas = stageBefore && stageBefore.canvas;
            hideStagesForResize();
            if (s.$$phase || (s.$root && s.$root.$$phase)) {
                s.$evalAsync(commit);
            } else {
                s.$apply(commit);
            }

            waitForFreshCanvasThenAdd(scope, dataImg, extenal, ilr, maskId, original, oldCanvas, 0);
        };
        probe.src = url;
        return true;
    }

    // Polls for the canvas that applyOptions() rebuilds (same rebuild
    // that made the empty-canvas overlay need reconciliation elsewhere
    // in this file), then places the image via the untouched, known-good
    // original addImageFromUrl. oldCanvas must actually change to a
    // different object — merely existing isn't enough (see the comment
    // where this is called).
    function waitForFreshCanvasThenAdd(scope, dataImg, extenal, ilr, maskId, original, oldCanvas, attempt) {
        var stage = scope.stages && scope.stages[scope.currentStage];
        if (stage && stage.canvas && stage.canvas !== oldCanvas && typeof stage.canvas.getObjects === 'function') {
            // applyOptions()'s canvas rebuild can take long enough that
            // resource.addImageContext — the flag every deliberate click-to-add
            // entry point sets right before calling addImageFromUrl, which the
            // upload-capture hook's addImage() wrapper relies on to tell a real
            // click apart from an automatic post-upload add — no longer reads
            // as 'manual' by the time we get here (e.g. the rebuild resets
            // `resource`). This call path only ever runs as a direct result of
            // a genuine click, so re-assert it right before the real add.
            if (scope.resource) scope.resource.addImageContext = 'manual';

            // The rebuilt canvas can still carry over the PREVIOUS image/
            // layers (confirmed live: picking a 3rd, 4th, 5th... image kept
            // stacking new ones on top instead of replacing — the core
            // plugin's dimension-resize rebuild apparently preserves
            // existing design objects across a resize, which makes sense
            // for a customer nudging Width/Height slightly, but not here:
            // this whole flow exists because ONE photo IS the entire
            // design, so a newly picked photo should always fully replace
            // whatever was there, never layer on top of it.
            stage.canvas.getObjects().slice().forEach(function (obj) {
                stage.canvas.remove(obj);
            });

            var countBefore = stage.canvas.getObjects().length;
            original.call(scope, dataImg, extenal, ilr, maskId);
            waitForObjectAddedThenStretch(scope, stage.canvas, countBefore, 0);
            return;
        }
        if (attempt < 30) {
            setTimeout(function () {
                waitForFreshCanvasThenAdd(scope, dataImg, extenal, ilr, maskId, original, oldCanvas, attempt + 1);
            }, 300);
        }
    }

    // addImage()'s actual canvas.add() happens inside an async
    // fabric.Image.fromURL callback, so there's no synchronous return value
    // to grab the newly-added object from. Poll the object count instead;
    // once it grows, the last object is the one that was just added. Then
    // call the SAME $scope.fitToStage(undefined, item) the built-in
    // right-click → "Stretch" context menu item uses (context-menu.php:28,
    // ng-click="fitToStage()") — safe here specifically because this path
    // only runs when the canvas was just resized to match this exact
    // image's aspect ratio, so filling it edge-to-edge shouldn't distort
    // it noticeably (unlike stretching an arbitrary photo onto a
    // fixed-size product with an unrelated aspect ratio).
    function waitForObjectAddedThenStretch(scope, canvas, countBefore, attempt) {
        var objects = canvas.getObjects();
        if (objects.length > countBefore) {
            var addedItem = objects[objects.length - 1];
            function stretch() {
                if (typeof scope.fitToStage === 'function') scope.fitToStage(undefined, addedItem);
            }
            if (scope.$$phase || (scope.$root && scope.$root.$$phase)) {
                scope.$evalAsync(stretch);
            } else {
                scope.$apply(stretch);
            }
            // The dimension field driving this whole flow can size the
            // canvas to something much larger than the default — at 100%
            // zoom that can overlap the left sidebar and/or the summary
            // panel (confirmed live: looked broken until manually zoomed to
            // ~75%). Reuse the same auto-fit check done at initial page
            // load. Delayed to let the $apply/$evalAsync digest above
            // actually finish rendering first.
            setTimeout(autoZoomToFit, 200);
            setTimeout(revealStagesAfterResize, 450);
            return;
        }
        if (attempt < 30) {
            setTimeout(function () {
                waitForObjectAddedThenStretch(scope, canvas, countBefore, attempt + 1);
            }, 300);
        }
    }

    /* ----------------------------------------------------------------
       FLOATING "FIT" / "CENTER" TOOLBAR (signs365-style)
       For products using a predefined size LIST rather than the
       free-form Custom dimension field — those don't get the
       auto-size-from-image flow above, so customers need a manual way
       to fit/position their uploaded image within the fixed canvas.
       Core's own "Stretch" context-menu item already does a non-uniform
       fill (fitToStage() in app-modern.min.js) — "Fit" here is a NEW,
       aspect-preserving contain-fit; "Center" only repositions, no
       rescale.

       Originally implemented as two extra right-click context-menu
       items, but the customer asked for them to be visible without a
       right-click (closer to signs365, which shows them as a permanent
       small panel under the selected image) — a small toolbar that
       floats above the selected image on canvas, shown/positioned by a
       poll loop rather than one-time event bindings, because the fabric
       canvas instance itself gets replaced whenever dimension options
       change (see waitForFreshCanvasThenAdd above / project memory) —
       anything bound to a specific canvas/object reference would go
       stale silently.
    ---------------------------------------------------------------- */
    function fitImageUniform(item, stage, canvas) {
        if (!item.width || !item.height) return;
        var scale = stage.states.scaleRange[stage.states.currentScaleIndex].ratio;
        var maxW = canvas.width / scale;
        var maxH = canvas.height / scale;
        // Math.max (not min): cover the whole canvas like Stretch does — no
        // blank canvas space left over — while still scaling both axes by
        // the SAME factor so the image itself isn't distorted. Whichever
        // axis ends up larger than the canvas just extends past its edges,
        // same as it would for any other layer positioned off-canvas —
        // nothing outside the print area shows up in the export.
        var s = Math.max(maxW / item.width, maxH / item.height);
        var originX = item.get('originX'), originY = item.get('originY');
        item.set({
            left: originX === 'center' ? maxW / 2 : 0,
            top: originY === 'center' ? maxH / 2 : 0,
            scaleX: s,
            scaleY: s
        });
        canvas.viewportCenterObjectH(item);
        canvas.viewportCenterObjectV(item);
        item.setCoords();
    }

    function centerImageOnly(item, canvas) {
        canvas.viewportCenterObjectH(item);
        canvas.viewportCenterObjectV(item);
        item.setCoords();
    }

    var sgFitCenterTarget = null; // {item, stage, canvas} — refreshed every poll tick, read at click time

    function ensureFitCenterToolbar() {
        var toolbar = document.getElementById('sg-fit-center-toolbar');
        if (toolbar) return toolbar;

        toolbar = document.createElement('div');
        toolbar.id = 'sg-fit-center-toolbar';
        toolbar.className = 'sg-fit-center-toolbar';
        toolbar.innerHTML =
            '<button type="button" class="sg-fit-center-btn" id="sg-fit-btn">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 3H4a1 1 0 0 0-1 1v5h2V5h4V3zm11 1a1 1 0 0 0-1-1h-5v2h4v4h2V4zM5 15H3v5a1 1 0 0 0 1 1h5v-2H5v-4zm14 4h-4v2h5a1 1 0 0 0 1-1v-5h-2v4z"/></svg>' +
                '<span>' + escHtml('Fit') + '</span>' +
            '</button>' +
            '<span class="sg-fit-center-sep"></span>' +
            '<button type="button" class="sg-fit-center-btn" id="sg-center-btn">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 20c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>' +
                '<span>' + escHtml('Center') + '</span>' +
            '</button>';
        // signs365/printcart-style vertical menu in the blank margin to
        // the left of the CANVAS (not a horizontal pill above the image —
        // that overlapped the "Show dimensions" ruler labels near the top
        // of the canvas; and not anchored to the image's own edge either —
        // that crowded/overlapped the canvas boundary whenever the image
        // filled most of the canvas width).
        document.body.appendChild(toolbar);

        toolbar.querySelector('#sg-fit-btn').addEventListener('click', function (e) {
            e.stopPropagation();
            runFitCenterAction(fitImageUniform);
        });
        toolbar.querySelector('#sg-center-btn').addEventListener('click', function (e) {
            e.stopPropagation();
            runFitCenterAction(function (item, stage, canvas) { centerImageOnly(item, canvas); });
        });

        return toolbar;
    }

    function runFitCenterAction(fn) {
        var target = sgFitCenterTarget;
        if (!target) return;
        var scope = target.scope, item = target.item, stage = target.stage, canvas = target.canvas;

        function run() {
            fn(item, stage, canvas);
            if (typeof scope.updateCorners === 'function') scope.updateCorners();
            if (typeof scope.renderStage === 'function') scope.renderStage();
            if (typeof scope.afterObjectModify === 'function') scope.afterObjectModify();
        }
        if (scope.$$phase || (scope.$root && scope.$root.$$phase)) {
            scope.$evalAsync(run);
        } else {
            scope.$apply(run);
        }
        // Reflect the new position/size immediately instead of waiting for
        // the next poll tick.
        setTimeout(positionFitCenterToolbar, 30);
    }

    function positionFitCenterToolbar() {
        var toolbar = ensureFitCenterToolbar();
        try {
            var stageMEl = findActiveStageMain();
            var scope = stageMEl && angular.element(stageMEl).scope();

            if (!scope || !scope.stages) {
                toolbar.classList.remove('is-visible');
                sgFitCenterTarget = null;
                return;
            }
            var stage = scope.stages[scope.currentStage];
            var canvas = stage && stage.canvas;
            var item = canvas && canvas.getActiveObject();
            // Read the fabric object's own type directly instead of the
            // Angular stage.states.isImage/isLayer flags — those are only
            // updated by core's own selection-change handler, and relying
            // on them adds a second thing that has to be "in sync" for no
            // real benefit (an image object IS an image regardless of
            // whether Angular has been notified of the selection yet).
            var isImage = item && (item.get('type') === 'image' || item.get('type') === 'custom-image');

            if (!item || !isImage || !canvas.upperCanvasEl) {
                toolbar.classList.remove('is-visible');
                sgFitCenterTarget = null;
                return;
            }

            sgFitCenterTarget = { scope: scope, item: item, stage: stage, canvas: canvas };

            var canvasRect = canvas.upperCanvasEl.getBoundingClientRect();
            // false = use the same "interactive" (oCoords) coordinate space
            // fabric itself uses for mouse hit-testing against the rendered
            // canvas element — i.e. already in on-screen pixels matching
            // canvasRect, regardless of current zoom. The other mode
            // (absolute/aCoords) is the zoom-independent logical space
            // item.left/top/width live in, which would be wrong here at
            // any zoom level other than 100%.
            var br = item.getBoundingRect(false, true);
            // Horizontal position is anchored to the CANVAS edge, not the
            // image edge — printcart/signs365 keep this menu in the blank
            // margin outside the design area so it never crowds/overlaps
            // whatever's on canvas, regardless of how much of the canvas
            // width the image itself happens to fill. Vertical position
            // still follows the selected image so it's clear which layer
            // it applies to when a stage has more than one.
            var top = canvasRect.top + br.top;

            toolbar.classList.add('is-visible');
            // ~100px is a safe estimate of the menu's own width; if there's
            // not enough blank margin to the left of the canvas itself
            // (canvas flush against the sidebar), fall back to the right
            // side of the canvas instead.
            if (canvasRect.left < 100) {
                toolbar.classList.add('sg-right');
                toolbar.style.left = canvasRect.right + 'px';
            } else {
                toolbar.classList.remove('sg-right');
                toolbar.style.left = canvasRect.left + 'px';
            }
            toolbar.style.top = top + 'px';
        } catch (err) {
            toolbar.classList.remove('is-visible');
            sgFitCenterTarget = null;
        }
    }

    function initFitCenterFloatingToolbar() {
        setInterval(positionFitCenterToolbar, 250);
    }

    /* ----------------------------------------------------------------
       UPLOADED-IMAGE LIBRARY CARDS
       Core only renders a bare thumbnail per uploaded file (.mansory-item
       inside #nbd-upload-wrap — see tab-photo.php: just an <img>, no
       filename/size/date). Reference sites (signs365.com "Image Zone")
       show each image's name, real-world size in inches, DPI and upload
       time. We can't change what the upload AJAX response contains, so:
         - filename comes from that ng-repeat item's own scope (img.des)
         - width/height in inches is read client-side from the image's
           real pixel size (Image().naturalWidth/Height) divided by the
           product's nbdesigner_default_dpi — the same conversion used
           by the auto-size-from-image feature above, so the numbers
           customers see here match what gets applied to the canvas.
         - "uploaded at" isn't tracked anywhere in the plugin's data
           model, so we timestamp it ourselves the moment we first see
           the thumbnail appear in the DOM (i.e. approximately when the
           upload finished). Thumbnails already present when the page
           loads (restored history) just don't get a date — we have no
           real value for those and won't invent one.
    ---------------------------------------------------------------- */
    // Reading resource.upload.data from an externally-fetched scope proved
    // unreliable on this page (dual [ng-controller] instances; a
    // MutationObserver watching #nbd-upload-wrap that mysteriously stopped
    // firing after the first couple of DOM changes — see project memory).
    // The one thing that DOES reliably work on this page is monkey-patching
    // $scope functions directly (same technique as the auto-size-from-image
    // feature above, confirmed working). Two are wrapped:
    //   - uploadFile(files, indexFile): the ONLY point where the browser's
    //     real File object (files[indexFile].name — the actual filename on
    //     the customer's disk) is available. The upload AJAX response
    //     (`nbdesigner_customer_upload`) only ever returns {flag, mes, src}
    //     — no original filename — so storeUploadFile(data, name) always
    //     gets name === undefined and we'd otherwise be stuck deriving a
    //     filename from the server's randomized storage URL.
    //   - storeUploadFile(data, name): fires once per successful upload, in
    //     the same order files were queued, so we pair it with the oldest
    //     queued filename.
    // We wrap both on every [ng-controller] found, in case more than one
    // instance is genuinely live; harmless no-ops on the rest.
    var sgUploadNameQueue = [];
    var sgUploadCaptureByIndex = {}; // keyed by absolute resource.upload.data index, not push order

    function initUploadCaptureHook(attempt) {
        if (!window.angular) return;
        var ctrls = document.querySelectorAll('[ng-controller]');
        var wrappedAny = false;
        for (var i = 0; i < ctrls.length; i++) {
            var scope;
            try { scope = angular.element(ctrls[i]).scope(); } catch (e) { scope = null; }
            if (!scope || scope.__sgUploadCaptureWrapped) continue;
            if (typeof scope.uploadFile !== 'function' || typeof scope.storeUploadFile !== 'function') continue;

            scope.__sgUploadCaptureWrapped = true;
            wrappedAny = true;

            (function (scope) {
                var originalUpload = scope.uploadFile;
                scope.uploadFile = function (files, indexFile) {
                    var idx = (typeof indexFile === 'number') ? indexFile : 0;
                    var file = files && files[idx];
                    // Skip SVGs: the core plugin handles them via FileReader +
                    // addSvgFromString, never calling storeUploadFile — queueing
                    // a name for them would never get shifted off and throw
                    // every later real upload's name off by one.
                    if (file && file.name && file.type && file.type.indexOf('svg') === -1) {
                        // dpi starts null and is filled in asynchronously below;
                        // this is the SAME object storeUploadFile() will shift
                        // off the queue, so mutating .dpi later is visible
                        // wherever that reference ends up (sgUploadCapture).
                        var entry = { name: file.name, dpi: null };
                        sgUploadNameQueue.push(entry);
                        if (typeof file.arrayBuffer === 'function') {
                            file.arrayBuffer().then(function (buf) {
                                entry.dpi = readEmbeddedDpi(buf);
                            }).catch(function () { /* leave dpi null — falls back to product default */ });
                        }
                    }
                    return originalUpload.apply(this, arguments);
                };

                var originalStore = scope.storeUploadFile;
                scope.storeUploadFile = function (data, name) {
                    var result = originalStore.apply(this, arguments);
                    var url = (data && typeof data === 'object') ? (data.src || data.url) : data;
                    var queued = sgUploadNameQueue.length ? sgUploadNameQueue.shift() : null;
                    // uploadedAt is stamped HERE — the instant this upload actually
                    // completes — not later when decorateItem() happens to render
                    // it (that could be a real "now", wrongly shown for restored/
                    // cached history items with no genuine upload time to report).
                    var entry = { url: url, des: (queued && queued.name) || name || '', dpiEntry: queued, uploadedAt: new Date() };
                    // originalStore.apply() above already did the real
                    // resource.upload.data.push(ojb) synchronously, so its length
                    // right now IS this item's real, final position in that array
                    // — which ng-repeat renders in the exact same order in the DOM.
                    // A plain sgUploadCapture.push() here would be WRONG: items
                    // restored from browser history (present at page load) never
                    // call storeUploadFile in this session at all, so they never
                    // get an entry — a compact array indexed purely by "the Nth
                    // capture this session" drifts out of sync with DOM position
                    // the moment there's ANY restored history ahead of a new
                    // upload (confirmed live: a fresh upload landed at DOM index
                    // 6, but sgUploadCapture — having only ever received this one
                    // push — held it at index 0). Storing by the item's actual
                    // absolute array/DOM index fixes that regardless of how many
                    // untracked restored items come before it.
                    var arr = scope.resource && scope.resource.upload && scope.resource.upload.data;
                    var absoluteIndex = arr ? arr.length - 1 : Object.keys(sgUploadCaptureByIndex).length;
                    sgUploadCaptureByIndex[absoluteIndex] = entry;

                    // Core plugin's own success handler (app-modern.min.js,
                    // right after this same storeUploadFile call) does
                    // jQuery('#tab-photo .tab-scroll').animate({scrollTop:
                    // scrollHeight}, 100) — auto-scrolls to the BOTTOM, because
                    // in its own unmodified design new uploads render there.
                    // We show newest-first (.mansory-wrap's flex-direction:
                    // column-reverse in override.css) instead, so the just-
                    // uploaded thumbnail is now at the TOP — scroll there
                    // instead, after a delay long enough to run after (and so
                    // win over) the core's own 100ms scroll animation.
                    setTimeout(function () {
                        var scrollEl = document.querySelector('#tab-photo .tab-scroll');
                        if (scrollEl) scrollEl.scrollTop = 0;
                    }, 200);

                    return result;
                };

                // Core behaviour: uploadFile() auto-places every uploaded photo
                // onto the canvas immediately (app-modern.min.js:2518,
                // `$scope.addImage(data, false, true)` — no maskId). signs365.com
                // instead just shows the uploaded thumbnail and only adds it when
                // the customer clicks it. addImage() itself can't tell which
                // caller invoked it — but every deliberate click-to-add entry
                // point in the core plugin's own templates (tab-photo.php: the
                // upload thumbnail, Photos search results, Facebook/Instagram/
                // Dropbox items, the Image URL insert button) already sets
                // `resource.addImageContext = 'manual'` immediately before
                // calling addImageFromUrl → addImage, synchronously, before
                // addImage's async fabric.Image.fromURL load even starts — so
                // that flag is still reliably readable the instant addImage is
                // entered. Skip the call only when it's neither that manual
                // context nor a mask-fill (maskId defined — e.g. uploading
                // directly into a selected template placeholder, which should
                // still auto-fill as before).
                if (typeof scope.addImage === 'function' && !scope.__sgManualAddOnlyWrapped) {
                    scope.__sgManualAddOnlyWrapped = true;
                    var originalAddImage = scope.addImage;
                    scope.addImage = function (data, showLoading, hideLoading, additionalTitle, additionalValue, maskId) {
                        if (typeof maskId === 'undefined' &&
                            (!scope.resource || scope.resource.addImageContext !== 'manual')) {
                            return; // automatic post-upload add — suppressed, thumbnail already shown by storeUploadFile
                        }
                        return originalAddImage.apply(this, arguments);
                    };
                }
            })(scope);
        }
        if (!wrappedAny && (attempt || 0) < 20) {
            setTimeout(function () { initUploadCaptureHook((attempt || 0) + 1); }, 500);
        }
    }

    // #nbd-upload-wrap's .mansory-item nodes are still the render target,
    // so we still need to poll for them — but the DATA now comes from our
    // own sgUploadCaptureByIndex map (keyed by absolute array/DOM index),
    // instead of from any Angular scope.
    function initUploadThumbnailMeta() {
        function reconcile() {
            var wrap = document.getElementById('nbd-upload-wrap');
            if (!wrap) return;
            var items = wrap.querySelectorAll('.mansory-item');
            for (var i = 0; i < items.length; i++) {
                // Newest-first via the flexbox `order` property instead of
                // a CSS row-reverse/wrap-reverse trick — that mirrored an
                // odd trailing item to the WRONG side of its (incomplete)
                // last row, leaving a visible gap next to it in the
                // 2-column grid (confirmed live). `order` repositions items
                // individually with no such edge case, for any column count
                // or item count, without touching the underlying
                // resource.upload.data / sgUploadCaptureByIndex order.
                items[i].style.order = -i;
                if (!items[i].querySelector('.sg-upload-meta')) {
                    decorateItem(items[i], sgUploadCaptureByIndex[i] || null);
                }
            }
        }

        setInterval(reconcile, 1000);
        reconcile();
    }

    function decorateItem(item, img) {
        var imgEl = item.querySelector('img');
        var domUrl = imgEl ? (imgEl.currentSrc || imgEl.getAttribute('src') || imgEl.src || '') : '';
        var url = (img && (img.url || img.src)) || domUrl;
        if (!url) return; // next reconcile() tick (≤1s later) will retry — item has no .sg-upload-meta yet

        var filename = (img && img.des) || filenameFromUrl(url);

        var meta = document.createElement('div');
        meta.className = 'sg-upload-meta';
        meta.innerHTML =
            '<div class="sg-upload-meta-name"></div>' +
            '<div class="sg-upload-meta-sizerow">' +
                '<span class="sg-upload-meta-size"></span>' +
                '<span class="sg-upload-meta-quality"></span>' +
            '</div>' +
            '<div class="sg-upload-meta-date"></div>';
        meta.querySelector('.sg-upload-meta-name').textContent = filename;
        meta.querySelector('.sg-upload-meta-name').title = filename;
        item.appendChild(meta);

        var dateEl = meta.querySelector('.sg-upload-meta-date');
        if (img && img.uploadedAt) {
            // Genuine capture from THIS session (stamped the instant
            // storeUploadFile actually ran) — no network round-trip needed.
            dateEl.textContent = formatUploadTimestamp(img.uploadedAt);
        } else {
            // No session-local capture — e.g. restored browser history, or
            // any OTHER customer/device viewing this design later. The
            // plugin's storage backend (files.printcart.com) sends
            // Access-Control-Allow-Origin: * (confirmed), so re-fetching the
            // file works identically for anyone: its HTTP Last-Modified
            // header is effectively the real upload time (the file is
            // created once at upload and never touched again).
            fetchRealUploadDate(url, dateEl);
        }

        var sizeEl = meta.querySelector('.sg-upload-meta-size');
        var knownDpi = img && img.dpiEntry && img.dpiEntry.dpi;
        var probe = new Image();
        probe.onload = function () {
            if (!probe.naturalWidth || !probe.naturalHeight) return;
            var w = probe.naturalWidth, h = probe.naturalHeight;

            if (knownDpi) {
                renderSize(sizeEl, w, h, knownDpi);
            } else {
                // Same reasoning as the date above: no client-side File
                // object to read (this wasn't uploaded in this session), so
                // fetch the actual bytes and parse embedded resolution
                // metadata directly — works for any viewer, not just
                // whoever originally uploaded it.
                fetch(url).then(function (res) { return res.arrayBuffer(); }).then(function (buf) {
                    renderSize(sizeEl, w, h, readEmbeddedDpi(buf) || getProductDefaultDpi());
                }).catch(function () {
                    renderSize(sizeEl, w, h, getProductDefaultDpi());
                });
            }
        };
        probe.src = url;
    }

    function renderSize(sizeEl, pixelW, pixelH, dpi) {
        var w = Math.round((pixelW / dpi) * 1000) / 1000;
        var h = Math.round((pixelH / dpi) * 1000) / 1000;
        sizeEl.textContent = w + ' x ' + h + ' in · ' + dpi + ' DPI';

        // Quality badge — only for the actionable case (resolution below
        // the product's own configured target DPI). A "GOOD" badge on
        // every normal upload was just noise, and the 2-column layout
        // leaves less room per card for it anyway.
        var badge = sizeEl.parentElement && sizeEl.parentElement.querySelector('.sg-upload-meta-quality');
        if (badge) {
            var target = getProductDefaultDpi();
            var isLow = dpi < target;
            badge.textContent = isLow ? ('LOW · ' + target + ' DPI target') : '';
            badge.classList.toggle('sg-quality-low', isLow);
        }
    }

    // settings.product_data.option.dpi = per-product DPI (admin: Edit
    // Product → Setting PCDesigner → "DPI (Dots Per Inch)"), already
    // resolved with the global default baked in server-side, available
    // from page load. (resource.config.dpi looks like the same value but
    // only gets copied there inside _saveData() — i.e. after Save/Add to
    // Cart — so it's empty before that. settings.nbdesigner_default_dpi
    // ignores any per-product override — don't read that either.)
    function getProductDefaultDpi() {
        var ngRoot = document.querySelector('[ng-controller]');
        if (ngRoot && window.angular) {
            try {
                var s = angular.element(ngRoot).scope();
                return parseFloat(s && s.settings && s.settings.product_data &&
                    s.settings.product_data.option && s.settings.product_data.option.dpi) || 150;
            } catch (e) { /* fall through */ }
        }
        return 150;
    }

    function fetchRealUploadDate(url, dateEl) {
        fetch(url, { method: 'HEAD' }).then(function (res) {
            var lastModified = res.headers.get('Last-Modified');
            if (lastModified) dateEl.textContent = formatUploadTimestamp(new Date(lastModified));
        }).catch(function () { /* leave blank — no real date available */ });
    }

    // Reads whatever resolution/DPI metadata is actually embedded in the
    // file — PNG's pHYs chunk, or a JPEG's JFIF APP0 segment / EXIF
    // XResolution tag. Returns a rounded DPI number, or null if the format
    // isn't recognized or carries no absolute (inches/cm) resolution tag
    // (common for e.g. plain screenshots) — callers fall back to the
    // product's configured default DPI in that case.
    function readEmbeddedDpi(arrayBuffer) {
        try {
            var view = new DataView(arrayBuffer);
            if (view.byteLength < 8) return null;

            // PNG: 89 50 4E 47 0D 0A 1A 0A, then a stream of length+type+data+crc chunks.
            if (view.getUint32(0) === 0x89504e47) {
                var offset = 8;
                while (offset + 8 <= view.byteLength) {
                    var length = view.getUint32(offset);
                    var type = String.fromCharCode(
                        view.getUint8(offset + 4), view.getUint8(offset + 5),
                        view.getUint8(offset + 6), view.getUint8(offset + 7)
                    );
                    if (type === 'pHYs' && offset + 8 + 9 <= view.byteLength) {
                        var ppuX = view.getUint32(offset + 8);   // pixels per unit, X axis
                        var unit = view.getUint8(offset + 8 + 8); // 0 = unknown ratio, 1 = metre
                        return (unit === 1 && ppuX > 0) ? Math.round(ppuX * 0.0254) : null;
                    }
                    if (type === 'IDAT' || type === 'IEND') return null; // pHYs always precedes image data
                    offset += 8 + length + 4; // length field + type + data + CRC
                }
                return null;
            }

            // JPEG: FFD8 then a sequence of FF-prefixed markers/segments.
            if (view.getUint16(0) === 0xffd8) {
                var pos = 2;
                while (pos + 4 <= view.byteLength) {
                    if (view.getUint8(pos) !== 0xff) break;
                    var marker = view.getUint8(pos + 1);
                    if (marker === 0xd8 || marker === 0x01 || (marker >= 0xd0 && marker <= 0xd9)) { pos += 2; continue; }
                    if (pos + 4 > view.byteLength) break;
                    var segLength = view.getUint16(pos + 2);

                    if (marker === 0xe0 && segLength >= 14 && pos + 4 + 9 <= view.byteLength) { // APP0 / JFIF
                        var id0 = String.fromCharCode(
                            view.getUint8(pos + 4), view.getUint8(pos + 5),
                            view.getUint8(pos + 6), view.getUint8(pos + 7)
                        );
                        if (id0 === 'JFIF') {
                            var units = view.getUint8(pos + 11);
                            var xDensity = view.getUint16(pos + 12);
                            if (units === 1 && xDensity > 0) return xDensity;
                            if (units === 2 && xDensity > 0) return Math.round(xDensity * 2.54);
                        }
                    }
                    if (marker === 0xe1 && pos + 4 <= view.byteLength) { // APP1 / EXIF
                        var exifDpi = readExifResolution(view, pos + 4, segLength - 2);
                        if (exifDpi) return exifDpi;
                    }
                    if (marker === 0xda) return null; // start of scan — no more metadata markers follow
                    pos += 2 + segLength;
                }
                return null;
            }

            return null;
        } catch (e) {
            return null;
        }
    }

    // Parses a TIFF-structured EXIF block for the XResolution (0x011A) /
    // ResolutionUnit (0x0128) tags. exifStart points at the "Exif\0\0"
    // marker; exifLength is the remaining bytes in that APP1 segment.
    function readExifResolution(view, exifStart, exifLength) {
        if (exifLength < 8 || exifStart + 8 > view.byteLength) return null;
        var sig = String.fromCharCode(
            view.getUint8(exifStart), view.getUint8(exifStart + 1),
            view.getUint8(exifStart + 2), view.getUint8(exifStart + 3)
        );
        if (sig !== 'Exif') return null;

        var tiffStart = exifStart + 6; // skip "Exif\0\0"
        if (tiffStart + 8 > view.byteLength) return null;

        var little = view.getUint16(tiffStart) === 0x4949; // "II" = little-endian, "MM" = big-endian
        function u16(o) { return view.getUint16(o, little); }
        function u32(o) { return view.getUint32(o, little); }

        var ifdOffset = tiffStart + u32(tiffStart + 4);
        if (ifdOffset + 2 > view.byteLength) return null;
        var numEntries = u16(ifdOffset);

        var xResolution = null, resUnit = 2; // ResolutionUnit default: 2 = inches
        for (var i = 0; i < numEntries; i++) {
            var entryOffset = ifdOffset + 2 + i * 12;
            if (entryOffset + 12 > view.byteLength) break;
            var tag = u16(entryOffset);
            if (tag === 0x011a) { // XResolution — type RATIONAL: 2 x uint32 (numerator, denominator)
                var valOffset = tiffStart + u32(entryOffset + 8);
                if (valOffset + 8 <= view.byteLength) {
                    var num = u32(valOffset), den = u32(valOffset + 4);
                    if (den > 0) xResolution = num / den;
                }
            } else if (tag === 0x0128) { // ResolutionUnit: 1 = none, 2 = inches, 3 = cm
                resUnit = u16(entryOffset + 8);
            }
        }
        if (xResolution == null) return null;
        return resUnit === 3 ? Math.round(xResolution * 2.54) : Math.round(xResolution);
    }

    function filenameFromUrl(url) {
        if (!url) return '';
        try {
            var clean = url.split('?')[0].split('#')[0];
            var parts = clean.split('/');
            return decodeURIComponent(parts[parts.length - 1] || '');
        } catch (e) { return ''; }
    }

    function formatUploadTimestamp(d) {
        var hours = d.getHours();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        if (hours === 0) hours = 12;
        var mins = ('0' + d.getMinutes()).slice(-2);
        return (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear() + ', ' + hours + ':' + mins + ' ' + ampm;
    }

    /* ----------------------------------------------------------------
       B2B TIER BADGE + DISCOUNT ROW
       Reads window.sg_b2b_* injected by PHP (printcart-b2bstore data).
       — Retail users: grey "Retail" pill → links to B2B apply page
       — B2B users: green "B2B · Tier B · 15% off" pill → links to portal
       — Discount row in price summary shows how much they save
    ---------------------------------------------------------------- */
    function initB2BBadge() {
        var badge = document.getElementById('sg-b2b-badge');
        if (!badge) return;

        var tier       = window.sg_b2b_tier       || 'b2c';
        var discount   = parseFloat(window.sg_b2b_discount) || 0;
        var portalUrl  = window.sg_b2b_portal_url || '#';

        if (tier && tier !== 'b2c' && discount > 0) {
            var tierShort;
            if (tier === 'vip') {
                tierShort = 'VIP';
            } else if (/^b2b_/.test(tier)) {
                tierShort = 'Tier ' + tier.replace('b2b_', '').toUpperCase();
            } else {
                tierShort = tier.toUpperCase();
            }
            badge.className = 'sg-b2b-badge sg-b2b-tier sg-b2b-static';
            badge.innerHTML =
                '<span class="sg-b2b-dot">&#9679;</span> B2B &middot; ' +
                tierShort + ' &middot; ' + discount + '% off';
            badge.title = window.sg_b2b_label || '';
            badge.removeAttribute('href');
        } else {
            badge.className = 'sg-b2b-badge sg-b2b-retail';
            badge.innerHTML = '<span class="sg-b2b-dot">&#9679;</span> Retail';
            badge.title = 'Apply for a B2B account to unlock discounts';
            if (window.sg_is_logged_in) {
                // Logged-in retail user → open apply modal
                badge.href = '#';
                badge.addEventListener('click', function (e) {
                    e.preventDefault();
                    openB2BModal();
                });
            } else {
                // Guest → static label, not clickable (Login button already in topbar)
                badge.removeAttribute('href');
                badge.classList.add('sg-b2b-static');
            }
        }
    }

    /* ----------------------------------------------------------------
       B2B APPLY MODAL
       3-step form: Company → Contact → Review + submit via AJAX
    ---------------------------------------------------------------- */
    var b2bModalStep = 1;

    function openB2BModal() {
        var modal = document.getElementById('sg-b2b-modal');
        if (!modal) {
            injectB2BModal();
            modal = document.getElementById('sg-b2b-modal');
        }
        if (!modal) return;
        b2bModalStep = 1;
        showB2BStep(1);
        resetB2BForm();
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeB2BModal() {
        var modal = document.getElementById('sg-b2b-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function injectB2BModal() {
        if (document.getElementById('sg-b2b-modal')) return;

        var INDUSTRIES = [
            '', 'Advertising & Marketing', 'Architecture & Design',
            'Education', 'Events & Entertainment', 'Fashion & Apparel',
            'Food & Beverage', 'Healthcare', 'Non-Profit', 'Real Estate',
            'Retail', 'Sports & Recreation', 'Technology', 'Other'
        ];
        var VOLUMES = [
            '', 'Under $500/month', '$500 – $2,000/month',
            '$2,000 – $5,000/month', '$5,000 – $15,000/month', 'Over $15,000/month'
        ];

        var industryOpts = INDUSTRIES.map(function(v) {
            return '<option value="' + escHtml(v) + '">' + (v || 'Select industry…') + '</option>';
        }).join('');
        var volumeOpts = VOLUMES.map(function(v) {
            return '<option value="' + escHtml(v) + '">' + (v || 'Select volume…') + '</option>';
        }).join('');

        var el = document.createElement('div');
        el.id = 'sg-b2b-modal';
        el.className = 'sg-b2b-modal-overlay';
        el.style.display = 'none';
        el.innerHTML =
            '<div class="sg-b2b-modal-card" role="dialog" aria-modal="true" aria-labelledby="sg-b2b-modal-title">' +
                '<button class="sg-b2b-modal-close" id="sg-b2b-modal-close" aria-label="Close">&times;</button>' +

                // Header
                '<div class="sg-b2b-modal-head">' +
                    '<div class="sg-b2b-modal-icon" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                            '<rect x="2" y="7" width="20" height="14" rx="2"/>' +
                            '<path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>' +
                        '</svg>' +
                    '</div>' +
                    '<h2 id="sg-b2b-modal-title">Open a B2B Account</h2>' +
                    '<p>Unlock wholesale pricing and exclusive business benefits</p>' +
                '</div>' +

                // Stepper
                '<div class="sg-b2b-stepper" id="sg-b2b-stepper">' +
                    '<div class="sg-b2b-step sg-b2b-step-active" data-step="1"><div class="sg-b2b-step-num">1</div><span>Company</span></div>' +
                    '<div class="sg-b2b-step-line"></div>' +
                    '<div class="sg-b2b-step" data-step="2"><div class="sg-b2b-step-num">2</div><span>Contact</span></div>' +
                    '<div class="sg-b2b-step-line"></div>' +
                    '<div class="sg-b2b-step" data-step="3"><div class="sg-b2b-step-num">3</div><span>Review</span></div>' +
                '</div>' +

                // Step 1: Company
                '<div class="sg-b2b-page" id="sg-b2b-page-1">' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-company">Company Name <span class="sg-b2b-req">*</span></label>' +
                        '<input type="text" id="sg-b2b-company" placeholder="Your company name" autocomplete="organization">' +
                    '</div>' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-industry">Industry</label>' +
                        '<select id="sg-b2b-industry">' + industryOpts + '</select>' +
                    '</div>' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-volume">Monthly Print Volume</label>' +
                        '<select id="sg-b2b-volume">' + volumeOpts + '</select>' +
                    '</div>' +
                '</div>' +

                // Step 2: Contact
                '<div class="sg-b2b-page" id="sg-b2b-page-2" style="display:none">' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-contact">Contact Name <span class="sg-b2b-req">*</span></label>' +
                        '<input type="text" id="sg-b2b-contact" placeholder="Full name" autocomplete="name">' +
                    '</div>' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-email">Business Email <span class="sg-b2b-req">*</span></label>' +
                        '<input type="email" id="sg-b2b-email" placeholder="you@company.com" autocomplete="email">' +
                    '</div>' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-phone">Phone</label>' +
                        '<input type="tel" id="sg-b2b-phone" placeholder="+1 (555) 000-0000" autocomplete="tel">' +
                    '</div>' +
                    '<div class="sg-b2b-field">' +
                        '<label for="sg-b2b-website">Website</label>' +
                        '<input type="url" id="sg-b2b-website" placeholder="https://yourcompany.com" autocomplete="url">' +
                    '</div>' +
                '</div>' +

                // Step 3: Review
                '<div class="sg-b2b-page" id="sg-b2b-page-3" style="display:none">' +
                    '<div class="sg-b2b-review" id="sg-b2b-review"></div>' +
                    '<div class="sg-b2b-benefits">' +
                        '<p class="sg-b2b-benefits-title">What you\'ll get</p>' +
                        '<ul>' +
                            '<li><span class="sg-b2b-check">&#10003;</span> Tiered discounts up to 24% off retail prices</li>' +
                            '<li><span class="sg-b2b-check">&#10003;</span> Net-30 / Net-60 payment terms after approval</li>' +
                            '<li><span class="sg-b2b-check">&#10003;</span> Private template library &amp; asset storage</li>' +
                            '<li><span class="sg-b2b-check">&#10003;</span> 1-click reorders &amp; smart reorder reminders</li>' +
                            '<li><span class="sg-b2b-check">&#10003;</span> Dedicated account manager for VIP tier</li>' +
                        '</ul>' +
                    '</div>' +
                '</div>' +

                // Success
                '<div class="sg-b2b-page" id="sg-b2b-page-success" style="display:none">' +
                    '<div class="sg-b2b-success">' +
                        '<div class="sg-b2b-success-icon">&#10003;</div>' +
                        '<h3 id="sg-b2b-success-msg">Application Submitted!</h3>' +
                        '<p id="sg-b2b-success-sub">We\'ll review your application and respond within 24 hours.</p>' +
                    '</div>' +
                '</div>' +

                // Error message
                '<div class="sg-b2b-error" id="sg-b2b-error" style="display:none"></div>' +

                // Actions
                '<div class="sg-b2b-actions" id="sg-b2b-actions">' +
                    '<button class="sg-b2b-btn-secondary" id="sg-b2b-btn-back" style="display:none">Back</button>' +
                    '<button class="sg-b2b-btn-primary" id="sg-b2b-btn-next">Next</button>' +
                    '<button class="sg-b2b-btn-primary sg-b2b-btn-submit" id="sg-b2b-btn-submit" style="display:none">Submit Application</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(el);

        // Close on overlay click
        el.addEventListener('click', function(e) {
            if (e.target === el) closeB2BModal();
        });

        // Close button
        document.getElementById('sg-b2b-modal-close').addEventListener('click', closeB2BModal);

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('sg-b2b-modal').style.display !== 'none') {
                closeB2BModal();
            }
        });

        // Next / Back buttons
        document.getElementById('sg-b2b-btn-next').addEventListener('click', function() {
            if (b2bModalStep === 1) {
                var company = document.getElementById('sg-b2b-company').value.trim();
                if (!company) { showB2BError('Company name is required.'); return; }
                hideB2BError();
                b2bModalStep = 2;
                showB2BStep(2);
            } else if (b2bModalStep === 2) {
                var contact = document.getElementById('sg-b2b-contact').value.trim();
                var email   = document.getElementById('sg-b2b-email').value.trim();
                if (!contact) { showB2BError('Contact name is required.'); return; }
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showB2BError('Please enter a valid email address.'); return;
                }
                hideB2BError();
                buildB2BReview();
                b2bModalStep = 3;
                showB2BStep(3);
            }
        });

        document.getElementById('sg-b2b-btn-back').addEventListener('click', function() {
            if (b2bModalStep > 1) {
                b2bModalStep--;
                showB2BStep(b2bModalStep);
                hideB2BError();
            }
        });

        document.getElementById('sg-b2b-btn-submit').addEventListener('click', submitB2BForm);
    }

    function showB2BStep(step) {
        [1, 2, 3].forEach(function(n) {
            var page  = document.getElementById('sg-b2b-page-' + n);
            var sStep = document.querySelector('.sg-b2b-stepper .sg-b2b-step[data-step="' + n + '"]');
            if (page)  page.style.display  = (n === step) ? '' : 'none';
            if (sStep) {
                sStep.classList.toggle('sg-b2b-step-active',    n === step);
                sStep.classList.toggle('sg-b2b-step-done',      n < step);
            }
        });
        document.getElementById('sg-b2b-page-success').style.display = 'none';

        var btnBack   = document.getElementById('sg-b2b-btn-back');
        var btnNext   = document.getElementById('sg-b2b-btn-next');
        var btnSubmit = document.getElementById('sg-b2b-btn-submit');
        var actions   = document.getElementById('sg-b2b-actions');
        if (btnBack)   btnBack.style.display   = step > 1  ? '' : 'none';
        if (btnNext)   btnNext.style.display   = step < 3  ? '' : 'none';
        if (btnSubmit) btnSubmit.style.display = step === 3 ? '' : 'none';
        if (actions)   actions.style.display   = '';
    }

    function buildB2BReview() {
        var fields = [
            { label: 'Company',       val: document.getElementById('sg-b2b-company').value },
            { label: 'Industry',      val: document.getElementById('sg-b2b-industry').value },
            { label: 'Monthly volume',val: document.getElementById('sg-b2b-volume').value },
            { label: 'Contact name',  val: document.getElementById('sg-b2b-contact').value },
            { label: 'Email',         val: document.getElementById('sg-b2b-email').value },
            { label: 'Phone',         val: document.getElementById('sg-b2b-phone').value },
            { label: 'Website',       val: document.getElementById('sg-b2b-website').value },
        ];
        var html = '<dl class="sg-b2b-review-list">';
        fields.forEach(function(f) {
            if (f.val) {
                html += '<dt>' + escHtml(f.label) + '</dt><dd>' + escHtml(f.val) + '</dd>';
            }
        });
        html += '</dl>';
        document.getElementById('sg-b2b-review').innerHTML = html;
    }

    function submitB2BForm() {
        var btn = document.getElementById('sg-b2b-btn-submit');
        if (btn) { btn.disabled = true; btn.textContent = 'Submitting…'; }
        hideB2BError();

        var body = new FormData();
        body.append('action',        'sg_b2b_apply');
        body.append('_nonce',        window.sg_b2b_nonce || '');
        body.append('company_name',  document.getElementById('sg-b2b-company').value.trim());
        body.append('industry',      document.getElementById('sg-b2b-industry').value);
        body.append('monthly_volume',document.getElementById('sg-b2b-volume').value);
        body.append('contact_name',  document.getElementById('sg-b2b-contact').value.trim());
        body.append('contact_email', document.getElementById('sg-b2b-email').value.trim());
        body.append('contact_phone', document.getElementById('sg-b2b-phone').value.trim());
        body.append('website',       document.getElementById('sg-b2b-website').value.trim());

        var ajaxUrl = window.sg_ajax_url || '/wp-admin/admin-ajax.php';
        fetch(ajaxUrl, { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    // Show success state
                    [1, 2, 3].forEach(function(n) {
                        var p = document.getElementById('sg-b2b-page-' + n);
                        if (p) p.style.display = 'none';
                    });
                    var successPage = document.getElementById('sg-b2b-page-success');
                    if (successPage) successPage.style.display = '';
                    var stepper = document.getElementById('sg-b2b-stepper');
                    if (stepper) stepper.style.display = 'none';
                    var actions = document.getElementById('sg-b2b-actions');
                    if (actions) actions.style.display = 'none';
                    var msgEl  = document.getElementById('sg-b2b-success-msg');
                    var subEl  = document.getElementById('sg-b2b-success-sub');
                    if (msgEl) msgEl.textContent = data.data.auto_approved ? 'Account Activated!' : 'Application Submitted!';
                    if (subEl) subEl.textContent = data.data.message || '';
                    hideB2BError();
                } else {
                    var msg = (data.data && data.data.message) ? data.data.message : 'Something went wrong.';
                    showB2BError(msg);
                    if (btn) { btn.disabled = false; btn.textContent = 'Submit Application'; }
                }
            })
            .catch(function() {
                showB2BError('Network error. Please try again.');
                if (btn) { btn.disabled = false; btn.textContent = 'Submit Application'; }
            });
    }

    function showB2BError(msg) {
        var el = document.getElementById('sg-b2b-error');
        if (el) { el.textContent = msg; el.style.display = ''; }
    }
    function hideB2BError() {
        var el = document.getElementById('sg-b2b-error');
        if (el) { el.textContent = ''; el.style.display = 'none'; }
    }
    function resetB2BForm() {
        ['sg-b2b-company','sg-b2b-contact','sg-b2b-email','sg-b2b-phone','sg-b2b-website'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['sg-b2b-industry','sg-b2b-volume'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.selectedIndex = 0;
        });
        hideB2BError();
        var stepper = document.getElementById('sg-b2b-stepper');
        if (stepper) stepper.style.display = '';
        var btn = document.getElementById('sg-b2b-btn-submit');
        if (btn) { btn.disabled = false; btn.textContent = 'Submit Application'; }
    }

    /* ----------------------------------------------------------------
       0. DISABLE ANGULAR $animate FOR SIDEBAR
       Angular's ngAnimate sets position:absolute + height on list
       items/containers during slideInDown animations. Disabling $animate
       for the sidebar prevents this across ALL tabs (Text/li, Photos/div,
       Elements/div, Cliparts, Layers).
    ---------------------------------------------------------------- */
    function disableSidebarAnimations() {
        var done = tryDisableSidebarAnimations();
        if (!done) {
            // Angular may not have bootstrapped yet — retry a few times
            var retries = 0;
            var t = setInterval(function () {
                retries++;
                if (tryDisableSidebarAnimations() || retries > 20) clearInterval(t);
            }, 300);
        }
    }

    function tryDisableSidebarAnimations() {
        try {
            if (!window.angular) return false;
            var ngRoot = document.querySelector('[ng-controller]');
            if (!ngRoot) return false;
            var injector = angular.element(ngRoot).injector();
            if (!injector) return false;
            var $animate = injector.get('$animate');
            if (!$animate) return false;
            var sidebar = document.querySelector('.nbd-sidebar');
            if (!sidebar) return false;
            $animate.enabled(sidebar, false);
            console.log('[SG-DUI] Angular $animate disabled for .nbd-sidebar');
            return true;
        } catch (e) {
            return false;
        }
    }

    /* ----------------------------------------------------------------
       0b. CANVAS CENTERING
       Strategy: JS-computed padding.
         1. Measure available space (stages.clientWidth/Height) and the
            canvas element (stage-main-wrap.offsetWidth/Height).
         2. Set equal top+bottom / left+right padding on .stage so the
            canvas sits exactly in the middle with ≥30 px breathing room.
         3. For canvases larger than the viewport, minimum 30 px padding
            is applied and we also scroll to the centre of the overflow.
       This is purely additive — no layout thrash from Angular reflows.
    ---------------------------------------------------------------- */
    function initCanvasCentering() {
        // Skip on mobile — the original plugin handles mobile layout
        if (window.innerWidth <= 767) return;

        var done = tryCenterCanvas();
        if (!done) {
            var retries = 0;
            var t = setInterval(function () {
                retries++;
                if (tryCenterCanvas() || retries > 30) clearInterval(t);
            }, 300);
        }
    }

    function tryCenterCanvas() {
        var stages    = document.querySelector('.nbd-stages');
        var stageMain = document.querySelector('.nbd-stages .stage-main');
        if (!stages || !stageMain) return false;

        centerCanvas(stages);

        if (window.MutationObserver) {
            var styleTimer  = null;
            var switchTimer = null;

            // After a stage switch, re-center repeatedly until Angular finishes rendering.
            // This MutationObserver-driven path fires purely on DOM change (a new
            // .stage-main being inserted), independent of which Angular scope instance
            // actually owns the real switchStage() — unlike the scope-function wrap in
            // tryHookSwitchStage below, which turned out NOT to be reliable (confirmed
            // live: wrapping switchStage there had no effect on side-switch zoom at all,
            // most likely because it's hooked on the wrong one of this page's two
            // [ng-controller] instances — see project memory on that issue). DOM
            // mutation doesn't care which scope triggered it, so autoZoomToFit() is
            // called from here too as the reliable path.
            function reCenterAfterSwitch() {
                clearInterval(switchTimer);
                var count = 0;
                switchTimer = setInterval(function () {
                    centerCanvas(stages);
                    autoZoomToFit();
                    if (++count >= 12) clearInterval(switchTimer);
                }, 250);
            }

            var mo = new MutationObserver(function (mutations) {
                // Detect when a new .stage-main is inserted (Angular stage switch)
                var stageAdded = mutations.some(function (m) {
                    if (m.type !== 'childList') return false;
                    for (var i = 0; i < m.addedNodes.length; i++) {
                        var n = m.addedNodes[i];
                        if (n.nodeType !== 1) continue;
                        if (n.classList && n.classList.contains('stage-main')) return true;
                        if (n.querySelector && n.querySelector('.stage-main'))  return true;
                    }
                    return false;
                });

                if (stageAdded) {
                    reCenterAfterSwitch();
                } else {
                    clearTimeout(styleTimer);
                    styleTimer = setTimeout(function () { centerCanvas(stages); }, 80);
                }
            });
            // childList catches new .stage-main elements; style catches ng-style updates
            mo.observe(stages, {
                subtree:         true,
                childList:       true,
                attributes:      true,
                attributeFilter: ['style']
            });
        }

        window.addEventListener('resize', function () { centerCanvas(stages); });

        // Also hook Angular's switchStage directly — most reliable trigger
        hookSwitchStage(stages);

        // Dense early polling (10 s) as ultimate fallback
        var pass = 0;
        var t = setInterval(function () {
            centerCanvas(stages);
            if (++pass >= 20) clearInterval(t);
        }, 500);

        return true;
    }

    // Same "which stage is actually visible" logic as centerCanvas()'s
    // activeWrap detection: skip stages hidden via ._nbd_hidden or mid-exit
    // via the fadeOut* animation classes, and confirm real rendered width.
    function findActiveStageMain() {
        var allMains = document.querySelectorAll('.nbd-stages .stage-main');
        for (var i = 0; i < allMains.length; i++) {
            var cur = allMains[i].parentElement;
            while (cur && !cur.classList.contains('stage')) cur = cur.parentElement;
            if (cur && (cur.classList.contains('_nbd_hidden') || /\bfadeOut/.test(cur.className))) continue;
            if (allMains[i].offsetWidth > 0) return allMains[i];
        }
        return null;
    }

    function hookSwitchStage(stages) {
        var done = tryHookSwitchStage(stages);
        if (!done) {
            var retries = 0;
            var t = setInterval(function () {
                retries++;
                if (tryHookSwitchStage(stages) || retries > 20) clearInterval(t);
            }, 500);
        }
    }

    function tryHookSwitchStage(stages) {
        try {
            if (!window.angular) return false;
            var ngRoot = document.querySelector('[ng-controller]');
            if (!ngRoot) return false;
            var scope = angular.element(ngRoot).scope();
            if (!scope || typeof scope.switchStage !== 'function') return false;
            if (scope._sgSwitchStageHooked) return true; // already hooked

            var orig = scope.switchStage;
            scope.switchStage = function () {
                var res = orig.apply(this, arguments);
                var count = 0;
                var t = setInterval(function () {
                    centerCanvas(stages);
                    if (++count >= 12) clearInterval(t);
                }, 250);
                // Each side/stage has its OWN independent zoom level
                // (stage.states.currentScaleIndex) — the auto-size-from-image
                // flow's zoom-fit only ever touched whichever stage it ran
                // on. Switching to a different side can land on one that was
                // never auto-fitted (still sitting at its default 100%),
                // overflowing again — confirmed live. Re-run the same
                // zoom-fit check after a switch, same delay pattern as
                // centerCanvas above so it measures the newly-active stage.
                setTimeout(autoZoomToFit, 300);
                return res;
            };
            scope._sgSwitchStageHooked = true;
            console.log('[SG-DUI] switchStage hooked');
            return true;
        } catch (e) { return false; }
    }

    function centerCanvas(stages) {
        // CSS flex (height:100% + margin:auto on .stage) handles VERTICAL centering.
        // JS handles HORIZONTAL centering via margin-left on .stage-main-wrap.
        var allWraps = stages.querySelectorAll('.stage-main-wrap');
        if (!allWraps.length) return;

        // Find the active wrap: skip _nbd_hidden and fadeOut* stages
        var activeWrap = null, canvasW = 0;
        for (var i = 0; i < allWraps.length; i++) {
            var cur = allWraps[i].parentElement;
            while (cur && !cur.classList.contains('stage')) cur = cur.parentElement;
            if (cur && (cur.classList.contains('_nbd_hidden') || /\bfadeOut/.test(cur.className))) continue;
            var sm = allWraps[i].querySelector('.stage-main');
            if (sm && sm.offsetWidth > 0) { activeWrap = allWraps[i]; canvasW = sm.offsetWidth; break; }
        }
        if (!activeWrap || !canvasW) return;

        var availW  = stages.clientWidth;
        var padL    = parseFloat(window.getComputedStyle(activeWrap.parentElement).paddingLeft) || 100;
        var marginL = Math.max(40, Math.floor((availW - canvasW) / 2 - padL));
        activeWrap.style.setProperty('margin-left', marginL + 'px', 'important');
        activeWrap.style.removeProperty('margin-right');

        // Angular's calcRulerPaddingLeft uses (viewportWidth - cwidth)/2 + 50, where viewportWidth =
        // nbd-stages.width - 100.  That formula doesn't know about our extra padL (100px stage padding),
        // so ruler "0" ends up 100px right of the canvas.  Fix: replace both ruler functions on the
        // Angular scope so they return the exact offsets that match our canvas position, then trigger
        // $apply() so ng-style picks up the new values immediately.
        try {
            var stageEl = activeWrap.parentElement;
            if (stageEl && window.angular) {
                var stageScope = angular.element(stageEl).scope();
                if (stageScope) {
                    // IMPORTANT: these two must measure the DOM live, at the exact
                    // moment Angular's digest calls them — NOT return a value
                    // captured earlier from this centerCanvas() run. centerCanvas()
                    // fires from several overlapping sources (resize, MutationObserver,
                    // interval polling, post-switch retries); an outer `marginL`
                    // variable captured by one of those calls can easily be stale by
                    // the time a *different* call's $apply() actually triggers
                    // Angular to re-read calcRulerPaddingLeft/Top — that mismatch
                    // (a margin-left already applied to .stage-main-wrap by one call,
                    // vs. a different margin value returned to Angular by another)
                    // is exactly what made the "Show dimensions" width/height labels
                    // render at the wrong horizontal position: confirmed live via
                    // devtools, .stage-main's real rect didn't match what the ruler
                    // functions were reporting. Measuring fresh on every single call
                    // removes the race entirely.
                    stageScope.calcRulerPaddingLeft = function () {
                        return measureRulerPaddingLeft(stageEl) + 'px';
                    };
                    stageScope.calcRulerPaddingTop = function () {
                        return measureCanvasTopWithinStage(stageEl) + 'px';
                    };
                    if (!stageScope.$$phase) {
                        stageScope.$apply();
                    }
                }
            }
        } catch (e) { /* ignore if Angular not ready */ }

        // .nbd-dimension.x-dimension's "top" (the width label bracket shown when
        // "Show dimensions" is on) is a hardcoded CSS top:10px, not Angular-bound
        // at all — it assumes the same fixed near-the-top canvas position broken
        // by our flex vertical centering above. Position it directly, a fixed
        // gap above the canvas's real (measured) top edge, every time we recenter.
        // .nbd-dimension.x-dimension / .y-dimension (the "Show dimensions" width
        // and height labels) get ALL FOUR relevant properties set directly here,
        // bypassing Angular's ng-style for them entirely — measured fresh from
        // the real, current .stage-main rect every time. Two earlier attempts
        // at this both had the same root problem: reusing the ruler's
        // calcRulerPaddingLeft/Top functions (or a value captured from this
        // same run's `marginL`) for these too. That's wrong on two counts —
        // (1) .x-dimension is position:absolute (its "left" is measured from
        // .stage's padding EDGE directly), while the ruler div is a normal-flow
        // child that's ALREADY shifted by .stage's own padding-left and only
        // needs the remaining gap as its own padding — same physical alignment,
        // different formulas, confirmed live via devtools (.x-dimension ended
        // up exactly .stage's padding-left (32px) short of the real canvas
        // edge); and (2) centerCanvas() fires from several overlapping
        // triggers, so any value computed earlier in one call can be stale by
        // the time a later call's $apply() reads it. Measuring directly here,
        // once, right before writing both elements' styles, sidesteps both.
        try {
            var xDim = stages.querySelector('.x-dimension');
            var yDim = stages.querySelector('.y-dimension');
            var stageMainForDim = activeWrap.querySelector('.stage-main');
            var stageElForDim = activeWrap.parentElement;
            if ((xDim || yDim) && stageMainForDim && stageElForDim) {
                // Gap between the bracket and the canvas edge — purely cosmetic
                // breathing room (bracket height/width is fixed at 25px by core
                // CSS), separate from the alignment math below.
                var dimGap = 45;

                // .stage's own top padding (16px by default) isn't enough room
                // for a 45px gap above the canvas — the width label was ending
                // up clamped flush against the canvas edge. Reserve enough
                // padding BEFORE measuring, so the canvas shifts down (flex
                // vertical centering re-settles around the new padding) and
                // the measurement below reflects genuine breathing room.
                if (xDim) {
                    var neededPadTop = dimGap + 15;
                    var curPadTop = parseFloat(window.getComputedStyle(stageElForDim).paddingTop) || 0;
                    if (curPadTop < neededPadTop) {
                        stageElForDim.style.setProperty('padding-top', neededPadTop + 'px', 'important');
                    }
                }

                var stageRectForDim  = stageElForDim.getBoundingClientRect();
                var canvasRectForDim = stageMainForDim.getBoundingClientRect();
                var leftWithinStage = Math.round(canvasRectForDim.left - stageRectForDim.left);
                var topWithinStage  = Math.round(canvasRectForDim.top - stageRectForDim.top);

                if (xDim) {
                    xDim.style.setProperty('left', leftWithinStage + 'px', 'important');
                    xDim.style.setProperty('width', Math.round(canvasRectForDim.width) + 'px', 'important');
                    xDim.style.setProperty('top', Math.max(0, topWithinStage - dimGap) + 'px', 'important');
                }
                if (yDim) {
                    yDim.style.setProperty('top', topWithinStage + 'px', 'important');
                    yDim.style.setProperty('height', Math.round(canvasRectForDim.height) + 'px', 'important');
                    yDim.style.setProperty('left', Math.max(0, leftWithinStage - dimGap) + 'px', 'important');
                }
            }
        } catch (e) { /* ignore */ }
    }

    // How far .stage-main's left edge sits from .stage's own content-box
    // (padding-edge) left edge, right now. Used as the ruler's padding-left
    // AND (via the same Angular function) .x-dimension's "left" — both are
    // direct children of .stage starting at that same baseline, so the one
    // live-measured number correctly repositions both.
    function measureRulerPaddingLeft(stageEl) {
        var stageMain = stageEl.querySelector('.stage-main');
        if (!stageMain) return 0;
        var stageRect = stageEl.getBoundingClientRect();
        var canvasRect = stageMain.getBoundingClientRect();
        var stagePadLeft = parseFloat(window.getComputedStyle(stageEl).paddingLeft) || 0;
        return Math.max(0, Math.round(canvasRect.left - stageRect.left - stagePadLeft));
    }

    // Returns how far .stage-main's top edge sits below .stage's own top edge,
    // in pixels — i.e. the real, currently-rendered vertical offset of the
    // canvas within its .stage container, accounting for flex centering.
    function measureCanvasTopWithinStage(stageEl) {
        var stageMain = stageEl && stageEl.querySelector('.stage-main');
        if (!stageEl || !stageMain) return 40; // fallback: core's own original hardcoded assumption
        var stageRect = stageEl.getBoundingClientRect();
        var canvasRect = stageMain.getBoundingClientRect();
        return Math.round(canvasRect.top - stageRect.top);
    }

    /* ----------------------------------------------------------------
       1. RIGHT SUMMARY PANEL
    ---------------------------------------------------------------- */
    function injectSummaryPanel(workspace) {
        if (document.querySelector('.sg-summary')) return; // already injected

        var productName = getProductName();

        var panel = document.createElement('div');
        panel.className = 'sg-summary';
        panel.innerHTML =
            '<div class="sg-summary-scroll">' +
                '<div class="sg-summary-header">' +
                    '<div class="sg-summary-header-top">' +
                        '<p class="sg-summary-product-name" id="sg-product-name">' + escHtml(productName) + '</p>' +
                        '<a class="sg-b2b-badge sg-b2b-retail" id="sg-b2b-badge" href="#" title="Apply for a B2B account">' +
                            '<span class="sg-b2b-dot">&#9679;</span> Retail' +
                        '</a>' +
                    '</div>' +
                '</div>' +

                '<p class="sg-summary-section-title">Printing Options</p>' +
                '<ul class="sg-summary-options-list" id="sg-options-mirror">' +
                    '<li class="sg-summary-option-item" id="sg-opts-placeholder">' +
                        '<span class="sg-summary-option-label">Select the Product tab to configure options</span>' +
                    '</li>' +
                '</ul>' +

                '<p class="sg-summary-section-title" style="margin-top:4px">Price Summary</p>' +
                '<div class="sg-summary-price-box">' +
                    '<div class="sg-price-row" id="sg-base-price-row">' +
                        '<span>Base price</span>' +
                        '<span id="sg-base-price">—</span>' +
                    '</div>' +
                    '<div class="sg-price-row" id="sg-options-price-row" style="display:none">' +
                        '<span>Options</span>' +
                        '<span id="sg-options-price">+0</span>' +
                    '</div>' +
                    '<div class="sg-price-row" id="sg-quantity-row">' +
                        '<span>Quantity</span>' +
                        '<span id="sg-quantity-val">1</span>' +
                    '</div>' +
                    '<div class="sg-price-row" id="sg-b2b-disc-row" style="display:none!important">' +
                        '<span id="sg-b2b-disc-label">B2B discount</span>' +
                        '<span class="sg-b2b-disc-val" id="sg-b2b-disc-val">−0.00</span>' +
                    '</div>' +
                    '<hr class="sg-price-divider">' +
                    '<div class="sg-price-total-row">' +
                        '<span class="sg-price-total-label">Your price</span>' +
                        '<div>' +
                            '<p class="sg-price-total-value" id="sg-total-price">—</p>' +
                            '<p class="sg-price-per-unit">per item</p>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="sg-summary-footer">' +
                '<button class="sg-btn-add-to-cart" id="sg-add-to-cart-btn">' +
                    '<span class="sg-btn-inner">' +
                        '<span class="sg-btn-label">Add to Cart</span>' +
                        '<span class="sg-btn-price-val" id="sg-btn-price">—</span>' +
                    '</span>' +
                    '<span class="sg-btn-arrow">&#8594;</span>' +
                '</button>' +
                '<p class="sg-summary-note">Finalise your design then click Add to Cart</p>' +
            '</div>';

        workspace.appendChild(panel);

        // Zoom-fit at initial load. See autoZoomToFit() below — reused after
        // later resizes too (e.g. the auto-size-from-image feature), not
        // just here at first render.
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
            setTimeout(autoZoomToFit, 600);
        }, 150);

        // Wire the CTA to saveData() — same function the Process button calls via ng-click="saveData()"
        document.getElementById('sg-add-to-cart-btn').addEventListener('click', function () {
            // Primary: call saveData() directly on the Angular scope
            try {
                var ngRoot = document.querySelector('[ng-controller]');
                if (ngRoot && window.angular) {
                    var scope = angular.element(ngRoot).scope();
                    if (scope && typeof scope.saveData === 'function') {
                        scope.$apply(function () { scope.saveData(); });
                        return;
                    }
                }
            } catch (e) { /* fall through */ }

            // Fallback: click the Process button in the top bar
            var processBtn = document.querySelector('.nbd-main-bar .item-process.save-data');
            if (processBtn) processBtn.click();
        });
    }

    // Zoom-fit strategy: click the DOM "−" zoom-out button (the same ng-click the user
    // presses manually) rather than calling scope methods directly.  This avoids $apply /
    // $evalAsync timing issues and is the most reliable path.
    // We only click if the canvas right edge visually overlaps the summary panel (via
    // getBoundingClientRect), so square / already-fitting canvases are left alone.
    //
    // Originally ran once at initial page load only. The auto-size-from-image
    // feature can resize the canvas to something much larger later on (e.g.
    // a wide/tall photo driving the custom-size dimension field), and at
    // 100% zoom that new, bigger canvas can overlap the left sidebar and/or
    // the right summary panel — confirmed live, looked broken until the
    // customer manually zoomed out to ~75%. Calling this same function again
    // after such a resize keeps the canvas comfortably fitted automatically.
    function autoZoomToFit() {
        try {
            // NOT document.querySelector('.stage-main') — that always grabs
            // the FIRST one in DOM order (side 1's), even while it's hidden
            // (._nbd_hidden collapses it to 0×0) and a different side is
            // actually visible. Confirmed live: switching to side 2 left it
            // stuck at 100% forever, because this was measuring side 1's
            // zero-size, hidden element (near-0 right edge → "no overflow"
            // → never zooms out) instead of side 2's real, overflowing one.
            var stageMEl = findActiveStageMain();
            var summaryEl = document.querySelector('.sg-summary');
            if (!stageMEl || !summaryEl) return;

            var canvasR = stageMEl.getBoundingClientRect().right;
            var panelL  = summaryEl.getBoundingClientRect().left;
            // Require ≥ 200 px breathing room between canvas right edge and panel.
            // A gap smaller than this means the canvas is too wide for the area
            // (it is likely also overflowing into the left sidebar).
            if (canvasR <= panelL - 200) return;

            // Try clicking the zoom-out "−" button first (most reliable path)
            var zoomOutBtn = document.querySelector('[ng-click*="currentScaleIndex - 1"]')
                          || document.querySelector('[ng-click*="currentScaleIndex-1"]');
            if (zoomOutBtn) {
                zoomOutBtn.click();
                // A single zoom-out step might not be enough for a canvas that
                // grew substantially — re-check shortly after and step down
                // again if it's still overflowing.
                setTimeout(autoZoomToFit, 400);
                return;
            }

            // Fallback: call Angular scope directly
            if (!window.angular) return;
            var ngRoot = document.querySelector('[ng-controller]');
            if (!ngRoot) return;
            var scope = angular.element(ngRoot).scope();
            if (!scope || !scope.stages || typeof scope.zoomStage !== 'function') return;
            var curStage = scope.currentStage || 0;
            var stObj    = scope.stages[curStage];
            if (!stObj || !stObj.states) return;
            var newIdx = stObj.states.currentScaleIndex - 1;
            if (newIdx < 0) return;
            if (scope.$$phase || scope.$root.$$phase) {
                scope.$evalAsync(function () { scope.zoomStage(newIdx, curStage); });
            } else {
                scope.$apply(function () { scope.zoomStage(newIdx, curStage); });
            }
            setTimeout(autoZoomToFit, 400);
        } catch (e) { /* ignore */ }
    }

    /* ----------------------------------------------------------------
       NBO SCOPE HELPERS
       The NBO (printing options) plugin runs its own Angular controller
       inside #nbo-options-wrap.  Its scope has:
         nbd_fields[key] = { title, value_name, price (HTML), enable, published }
         total_price     = HTML string of total options cost
    ---------------------------------------------------------------- */
    function getNboScope() {
        try {
            if (!window.angular) return null;
            var ctrlEl = null;
            if (window.nbOption && nbOption.crtlId) {
                ctrlEl = document.getElementById(nbOption.crtlId);
            }
            if (!ctrlEl) {
                ctrlEl = document.querySelector('#nbo-options-wrap [ng-controller]') ||
                         document.querySelector('#nbo-options-wrap');
            }
            if (!ctrlEl) return null;
            return angular.element(ctrlEl).scope() || null;
        } catch(e) { return null; }
    }

    // Returns { "value_name (lowercase)": "price string" } for all enabled/published fields.
    // Includes zero-price fields (price = "0,00 $" etc.) so callers can show "Free".
    function getNboFieldPrices() {
        var prices = {};
        var scope = getNboScope();
        if (!scope || !scope.nbd_fields) return prices;
        Object.keys(scope.nbd_fields).forEach(function(key) {
            var f = scope.nbd_fields[key];
            if (!f || !f.enable || !f.published) return;
            var valueName = (f.value_name || '').toLowerCase().trim();
            if (!valueName) return;
            var priceText = '';
            if (f.price) {
                var div = document.createElement('div');
                div.innerHTML = f.price;
                priceText = div.textContent.trim();
            }
            prices[valueName] = priceText;
        });
        return prices;
    }

    // Parse a formatted price string like "$6.63", "6,63 $", "1.234,56 $" → float
    // Handles both US (dot decimal) and European (comma decimal) formats.
    function parseFormattedPrice(str) {
        if (!str || str === '—') return null;
        // Keep only digits, dots, commas, and minus
        var s = String(str).replace(/[^0-9.,\-]/g, '');
        if (!s) return null;
        // Detect European format: comma followed by 1-2 digits at end (e.g. "6,63" "1.234,56")
        if (/,\d{1,2}$/.test(s)) {
            s = s.replace(/\./g, '').replace(',', '.');
        } else {
            // US / dot-decimal format: commas are thousands separators
            s = s.replace(/,/g, '');
        }
        var n = parseFloat(s);
        return isNaN(n) ? null : n;
    }

    /* ----------------------------------------------------------------
       3. OPTIONS MIRROR
       Watches #nbo-options-wrap for input changes and mirrors the
       currently selected values into the summary panel.
    ---------------------------------------------------------------- */
    function startOptionsMirror() {
        var wrap = document.getElementById('nbo-options-wrap');
        if (!wrap) {
            // Panel may not have loaded yet — retry briefly
            var retries = 0;
            var poller = setInterval(function () {
                retries++;
                var w = document.getElementById('nbo-options-wrap');
                if (w) { clearInterval(poller); attachOptionsMirror(w); }
                if (retries > 60) clearInterval(poller);
            }, 500);
            return;
        }
        attachOptionsMirror(wrap);
    }

    function attachOptionsMirror(wrap) {
        var debounceTimer = null;

        function scheduleUpdate() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(mirrorOptions, 150);
        }

        // MutationObserver catches Angular DOM rewrites
        var observer = new MutationObserver(scheduleUpdate);
        observer.observe(wrap, { childList: true, subtree: true, attributes: true, characterData: true });

        // Also listen for native change events
        wrap.addEventListener('change', scheduleUpdate);
        wrap.addEventListener('click', scheduleUpdate);

        // Initial render
        mirrorOptions();
    }

    function mirrorOptions() {
        var mirror = document.getElementById('sg-options-mirror');
        if (!mirror) return;

        var items = [];

        /* --- Radio / Checkbox inputs --- */
        var inputs = document.querySelectorAll(
            '#nbo-options-wrap input[type="radio"]:checked,' +
            '#nbo-options-wrap input[type="checkbox"]:checked'
        );
        inputs.forEach(function (input) {
            var labelEl  = document.querySelector('label[for="' + input.id + '"]') || input.closest('label');
            var groupLabel = getGroupLabel(input);
            var valueText  = labelEl ? cleanLabelText(labelEl.textContent) : input.value;
            if (valueText) {
                items.push({ label: groupLabel, value: valueText });
            }
        });

        /* --- Select dropdowns --- */
        var selects = document.querySelectorAll('#nbo-options-wrap select');
        selects.forEach(function (select) {
            var labelEl    = document.querySelector('label[for="' + select.id + '"]');
            var groupLabel = labelEl ? cleanLabelText(labelEl.textContent) : getGroupLabel(select);
            var selected   = select.options[select.selectedIndex];
            if (selected && selected.value !== '' && selected.value !== '0') {
                var valueText = cleanLabelText(selected.text);
                if (valueText) {
                    items.push({ label: groupLabel || 'Option', value: valueText });
                }
            }
        });

        /* --- Extract Quantity separately — it belongs in Price Summary, not options list --- */
        var quantityValue = null;
        items = items.filter(function(item) {
            if (/^quantity$/i.test(item.label.trim())) {
                quantityValue = item.value;
                return false;
            }
            return true;
        });
        var qRow = document.getElementById('sg-quantity-row');
        var qVal = document.getElementById('sg-quantity-val');
        if (qRow && qVal) {
            qVal.textContent = quantityValue || '1';
            qRow.style.removeProperty('display');
        }

        /* --- Attach per-option prices from NBO scope (match by value_name) --- */
        var fieldPrices = getNboFieldPrices();
        items.forEach(function(item) {
            var vKey = item.value.toLowerCase().trim();
            if (fieldPrices.hasOwnProperty(vKey)) {
                var p = fieldPrices[vKey];
                // Treat zero-price as "Free"
                item.price = (p && !/^[\$€£]?\s*0*([\.,]0+)?\s*[\$€£]?$/.test(p)) ? p : 'Free';
            } else {
                item.price = 'Free';
            }
        });

        /* --- Render --- */
        var placeholder = document.getElementById('sg-opts-placeholder');
        if (placeholder) placeholder.style.display = items.length ? 'none' : '';

        // Remove previously mirrored items (not the placeholder)
        var existing = mirror.querySelectorAll('.sg-mirrored-item');
        existing.forEach(function (el) { el.parentNode.removeChild(el); });

        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'sg-summary-option-item sg-mirrored-item';
            li.innerHTML =
                '<span class="sg-summary-option-label">' + escHtml(item.label) + '</span>' +
                '<span class="sg-summary-option-row">' +
                    '<span class="sg-summary-option-value">' + escHtml(item.value) + '</span>' +
                    (item.price ? '<span class="sg-summary-option-price' + (item.price === 'Free' ? ' sg-opt-free' : '') + '">' + escHtml(item.price) + '</span>' : '') +
                '</span>';
            mirror.appendChild(li);
        });
    }

    function getGroupLabel(input) {
        var fieldWrap = input.closest('.nbd-option-field, .nbo-group-wrap, .nbd-field-radio-wrap');
        if (!fieldWrap) return 'Option';
        var headerLabel = fieldWrap.querySelector('.nbd-field-header label, .nbo-group-header .group-title');
        if (headerLabel) {
            return cleanLabelText(headerLabel.textContent);
        }
        return 'Option';
    }

    function cleanLabelText(text) {
        return (text || '')
            .replace(/\s+/g, ' ')
            .replace(/\*$/, '')
            .replace(/[+\-]?\s*[\d]+[.,][\d]*\s*\S+/g, '') // strip prices like "+$1.00"
            .trim();
    }

    /* ----------------------------------------------------------------
       4. PRICE MIRROR
       Tries multiple strategies to read the computed price from
       the existing plugin (Angular scope, DOM elements, global vars).
    ---------------------------------------------------------------- */
    var pricePollCount = 0;
    var pricePollInterval = null;

    function startPriceMirror() {
        pricePollInterval = setInterval(function () {
            pricePollCount++;
            if (pricePollCount > 120) clearInterval(pricePollInterval); // stop after ~2 min
            updatePrice();
        }, 1000);

        // Also watch #nbo-options-wrap DOM for price element insertions
        var wrap = document.getElementById('nbo-options-wrap');
        if (wrap) {
            var obs = new MutationObserver(function () { setTimeout(updatePrice, 200); });
            obs.observe(wrap, { childList: true, subtree: true, characterData: true });
        }
    }

    /* ----------------------------------------------------------------
       OPTIONS LOADING STATE
       The product options arrive by AJAX (can take several seconds).
       Until the NBO scope has its fields and nbOption.initialed is set,
       show "Calculating..." instead of a bogus 0 price and keep the
       Add to Cart button disabled. Gives up after SG_OPTIONS_WAIT_MS.
    ---------------------------------------------------------------- */
    var SG_OPTIONS_WAIT_MS = 25000;
    var sgOptionsWaitStart = 0;
    var sgLoadingApplied   = false;
    var sgBtnLabelOrig     = '';

    function optionsReady() {
        try {
            var s = getNboScope();
            return !!(s && s.nbd_fields && Object.keys(s.nbd_fields).length > 0 &&
                      window.nbOption && window.nbOption.initialed);
        } catch (e) { return false; }
    }

    // Returns true while the loading state is active.
    function syncLoadingState() {
        if (!sgOptionsWaitStart) sgOptionsWaitStart = Date.now();
        var loading = !optionsReady() && (Date.now() - sgOptionsWaitStart) < SG_OPTIONS_WAIT_MS;

        var totalEl = document.getElementById('sg-total-price');
        var baseEl  = document.getElementById('sg-base-price');
        var btnEl   = document.getElementById('sg-btn-price');
        var addBtn  = document.getElementById('sg-add-to-cart-btn');
        var lblEl   = addBtn ? addBtn.querySelector('.sg-btn-label') : null;

        if (loading) {
            if (!sgLoadingApplied) {
                sgLoadingApplied = true;
                document.body.classList.add('sg-options-loading');
                if (lblEl) sgBtnLabelOrig = lblEl.textContent;
            }
            if (totalEl && totalEl.textContent !== 'Calculating...') totalEl.textContent = 'Calculating...';
            if (baseEl && baseEl.textContent !== 'Calculating...') baseEl.textContent = 'Calculating...';
            if (btnEl && btnEl.textContent !== 'Calculating...') btnEl.textContent = 'Calculating...';
            if (addBtn) {
                addBtn.disabled = true;
                addBtn.classList.add('sg-btn-loading');
            }
            if (lblEl && lblEl.textContent !== 'Loading options...') lblEl.textContent = 'Loading options...';
        } else if (sgLoadingApplied) {
            sgLoadingApplied = false;
            document.body.classList.remove('sg-options-loading');
            if (totalEl && totalEl.textContent === 'Calculating...') totalEl.textContent = '—';
            if (baseEl && baseEl.textContent === 'Calculating...') baseEl.textContent = '—';
            if (btnEl && btnEl.textContent === 'Calculating...') btnEl.textContent = '—';
            if (addBtn) {
                addBtn.disabled = false;
                addBtn.classList.remove('sg-btn-loading');
            }
            if (lblEl) lblEl.textContent = sgBtnLabelOrig || 'Add to Cart';
        }
        return loading;
    }

    function updatePrice() {
        var totalEl   = document.getElementById('sg-total-price');
        var baseEl    = document.getElementById('sg-base-price');
        if (!totalEl) return;

        if (syncLoadingState()) return;

        var totalPrice = null;
        var basePrice  = null;

        /* Strategy 1 — Angular scope on the main controller */
        try {
            var ngRoot = document.querySelector('[ng-controller]');
            if (ngRoot && window.angular) {
                var scope = angular.element(ngRoot).scope();
                if (scope) {
                    // NBO plugin exposes totalPrice / printOptionsPrice on scope
                    var raw = scope.totalPrice || scope.nboTotalPrice || scope.printOptionsPrice;
                    if (raw !== undefined && raw !== null) {
                        totalPrice = formatPrice(raw);
                    }
                    var rawBase = scope.productPrice || scope.basePrice;
                    if (rawBase !== undefined && rawBase !== null) {
                        basePrice = formatPrice(rawBase);
                    }
                }
            }
        } catch (e) { /* Angular may not be bootstrapped yet */ }

        /* Strategy 2 — DOM element inside #nbo-options-wrap */
        if (!totalPrice) {
            var priceEl = document.querySelector(
                '#nbo-options-wrap .nbo-total-price, ' +
                '#nbo-options-wrap .nbo-price-total, ' +
                '#nbo-options-wrap [class*="total-price"], ' +
                '#nbo-options-wrap .price bdi, ' +
                '#nbo-options-wrap .amount'
            );
            if (priceEl && priceEl.textContent.trim()) {
                totalPrice = priceEl.textContent.trim();
            }
        }

        /* Strategy 3 — nbds_frontend global + Angular scope for per-item price text */
        if (!totalPrice) {
            var tabProduct = document.querySelector('#tab-product');
            if (tabProduct) {
                var text = tabProduct.textContent || '';
                var m = text.match(/([\$€£¥₫]\s?[\d\s.,]+|[\d\s.,]+\s?[\$€£¥₫])/);
                if (m) totalPrice = m[0].replace(/\s/g, '').trim();
            }
        }

        /* Strategy 4 — sg_product_price injected by PHP (reliable, supports price = 0) */
        if (!totalPrice || !basePrice) {
            try {
                var rawP = null;
                // PHP-injected price is most reliable (null means product not found)
                if (window.sg_product_price !== undefined && window.sg_product_price !== null) {
                    rawP = window.sg_product_price;
                } else if (window.nbOption && nbOption.product_price !== undefined) {
                    rawP = nbOption.product_price;
                } else if (window.NBDESIGNCONFIG) {
                    var pd = NBDESIGNCONFIG.product_data || NBDESIGNCONFIG;
                    // Use explicit null check — price of 0 is valid
                    var candidates = [pd.price, pd.product_price, pd.regular_price];
                    if (pd.product && pd.product[0]) {
                        candidates.push(pd.product[0].price, pd.product[0].regular_price);
                    }
                    for (var ci = 0; ci < candidates.length; ci++) {
                        if (candidates[ci] !== undefined && candidates[ci] !== null && candidates[ci] !== '') {
                            rawP = candidates[ci]; break;
                        }
                    }
                }
                if (rawP !== null && rawP !== undefined) {
                    var formatted4 = formatPrice(rawP);
                    if (!basePrice) basePrice = formatted4;
                    if (!totalPrice) totalPrice = formatted4;
                }
            } catch (e) { /* ignore */ }
        }

        /* Apply to summary panel */
        if (totalPrice && totalEl.textContent !== totalPrice) {
            totalEl.textContent = totalPrice;
        }
        if (basePrice && baseEl && baseEl.textContent !== basePrice) {
            baseEl.textContent = basePrice;
        } else if (totalPrice && baseEl && baseEl.textContent === '—') {
            baseEl.textContent = totalPrice;
        }
        // Options total = total − base (avoids the NBO total_price which includes base/matrix)
        var optsRow = document.getElementById('sg-options-price-row');
        var optsEl  = document.getElementById('sg-options-price');
        if (optsRow && optsEl) {
            var tNum = parseFormattedPrice(totalEl.textContent);
            var bNum = parseFormattedPrice(baseEl ? baseEl.textContent : null);
            if (tNum !== null && bNum !== null) {
                var optsNum = tNum - bNum;
                if (optsNum > 0.005) {
                    var optsStr = formatPrice(optsNum);
                    if (optsEl.textContent !== optsStr) optsEl.textContent = optsStr;
                    optsRow.style.removeProperty('display');
                } else {
                    optsRow.style.display = 'none';
                }
            }
        }

        // B2B discount row — only show for actual B2B members with real discount
        var b2bRow  = document.getElementById('sg-b2b-disc-row');
        var b2bVal  = document.getElementById('sg-b2b-disc-val');
        var b2bLbl  = document.getElementById('sg-b2b-disc-label');
        var b2bDisc = parseFloat(window.sg_b2b_discount) || 0;
        var b2bTier = window.sg_b2b_tier || 'b2c';
        var displayTotal = totalPrice; // may be replaced by B2B-adjusted price below
        if (b2bRow && b2bTier !== 'b2c' && b2bDisc >= 1 && totalPrice) {
            var tNum = parseFormattedPrice(totalEl.textContent);
            if (tNum !== null && tNum > 0 && b2bDisc < 100) {
                var savings  = tNum * b2bDisc / 100;                  // e.g. 16.63 * 24/100 = 3.99
                var b2bTotal = tNum * (1 - b2bDisc / 100);            // e.g. 16.63 * 0.76  = 12.64
                if (b2bVal) b2bVal.textContent = '−' + formatPrice(savings);
                if (b2bLbl) {
                    var tier = window.sg_b2b_tier || '';
                    var ts = tier === 'vip' ? 'VIP'
                           : /^b2b_/.test(tier) ? 'Tier ' + tier.replace('b2b_', '').toUpperCase()
                           : tier.toUpperCase();
                    b2bLbl.textContent = 'B2B · ' + ts + ' · ' + b2bDisc + '% off';
                }
                // Override displayed total with the B2B price
                displayTotal = formatPrice(b2bTotal);
                totalEl.textContent = displayTotal;
                b2bRow.style.removeProperty('display');
            } else {
                b2bRow.style.setProperty('display', 'none', 'important');
            }
        } else if (b2bRow) {
            b2bRow.style.setProperty('display', 'none', 'important');
        }

        // Mirror price into the Add to Cart button (use B2B-adjusted price when applicable)
        var btnPrice = document.getElementById('sg-btn-price');
        if (btnPrice && displayTotal && btnPrice.textContent !== displayTotal) {
            btnPrice.textContent = displayTotal;
        }
    }

    function formatPrice(amount) {
        var sym      = '';
        var decimals = 2;
        var format   = '%s%v'; // default: symbol then value

        if (window.nbds_frontend) {
            sym      = window.nbds_frontend.currency_format_symbol      || '$';
            decimals = parseInt(window.nbds_frontend.currency_format_num_decimals, 10) || 2;
            format   = window.nbds_frontend.currency_format              || '%s%v';
        }

        var val = parseFloat(amount);
        if (isNaN(val)) return String(amount);

        var formatted = val.toFixed(decimals)
            .replace('.', window.nbds_frontend ? (window.nbds_frontend.currency_format_decimal_sep || '.') : '.');

        var result = format.replace('%s', sym).replace('%v', formatted);
        // Decode HTML entities (e.g. &nbsp; from currency_format) so textContent shows correctly
        var tmp = document.createElement('div');
        tmp.innerHTML = result;
        return tmp.textContent || tmp.innerText || result;
    }

    /* ----------------------------------------------------------------
       HELPERS
    ---------------------------------------------------------------- */
    function getProductName() {
        // NBDESIGNCONFIG.product_data has per-stage config; the WC product title
        // is most reliably obtained from the document title on the designer page.
        try {
            if (window.NBDESIGNCONFIG && NBDESIGNCONFIG.product_data) {
                // product_data.product is an array of stage configs; try name on first
                var pd = NBDESIGNCONFIG.product_data;
                if (pd.name) return pd.name;
                if (pd.product && pd.product[0] && pd.product[0].name) return pd.product[0].name;
            }
        } catch (e) {}

        // Fallback: document title (format: "Product Name | Site Name" or "… – …")
        try {
            var title = document.title || '';
            return title.split(/[|–—]/, 1)[0].trim() || 'Your Product';
        } catch (e) {}

        return 'Your Product';
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

})();
