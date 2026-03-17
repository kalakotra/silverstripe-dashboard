/**
 * dashboard.js
 * SilverStripe 6 CMS Dashboard Module – full production build
 *
 * Features
 * ────────
 *  - AJAX widget refresh (individual + refresh-all)
 *  - Skeleton loader swap during refresh
 *  - Per-widget error banner with dismiss
 *  - Animated progress-bar entrance via IntersectionObserver
 *  - Keyboard accessibility for refresh buttons
 *  - Chart.js script re-execution after AJAX injection
 *  - Public API: window.SSDashboard
 */

(function (window, document) {
    'use strict';

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    var BASE_REFRESH_URL = '/admin/dashboard/widgetRefresh/';

    var CLS = {
        WIDGET:          'dashboard-widget',
        WIDGET_INNER:    'dashboard-widget__inner',
        WIDGET_BODY:     'dashboard-widget__body',
        LOADING:         'is-loading',
        ERROR:           'has-error',
        REFRESH_BTN:     '[data-refresh-widget]',
        REFRESH_ALL:     '.dashboard__refresh-all',
        ERROR_BANNER:    'dashboard-widget__error-banner',
        ERROR_DISMISS:   'dashboard-widget__error-dismiss',
    };

    // Skeleton HTML keyed by widget type.
    // Injected while real data loads; built to match the CSS skeleton classes.
    var SKELETONS = {
        stats: function () {
            var tiles = '';
            for (var i = 0; i < 4; i++) {
                tiles += '<div class="skeleton-stat-tile">'
                    + '<div class="skeleton skeleton-line skeleton-line--50"></div>'
                    + '<div class="skeleton skeleton-line skeleton-line--xl skeleton-line--75"></div>'
                    + '<div class="skeleton skeleton-line skeleton-line--sm skeleton-line--33"></div>'
                    + '</div>';
            }
            return '<div class="skeleton-stat-grid">' + tiles + '</div>';
        },
        table: function () {
            var cells = '<div class="skeleton skeleton-line skeleton-line--full" style="flex:1"></div>'
                .repeat(3);
            var rows = '';
            for (var i = 0; i < 5; i++) {
                rows += '<div class="skeleton-table-row">' + cells + '</div>';
            }
            return '<div class="skeleton-table">'
                + '<div class="skeleton-table-header">' + cells + '</div>'
                + rows + '</div>';
        },
        list: function () {
            var items = '';
            var widths = ['full', '75', '50'];
            for (var i = 0; i < 6; i++) {
                items += '<div class="skeleton-list-item">'
                    + '<div class="skeleton skeleton-circle" style="width:30px;height:30px;flex-shrink:0"></div>'
                    + '<div style="flex:1;display:flex;flex-direction:column;gap:5px">'
                    + '<div class="skeleton skeleton-line skeleton-line--' + widths[i % 3] + '"></div>'
                    + '<div class="skeleton skeleton-line skeleton-line--sm skeleton-line--50"></div>'
                    + '</div></div>';
            }
            return '<div class="skeleton-list">' + items + '</div>';
        },
        progress: function () {
            var items = '';
            for (var i = 0; i < 3; i++) {
                items += '<div class="skeleton-progress-item">'
                    + '<div style="display:flex;justify-content:space-between;gap:8px">'
                    + '<div class="skeleton skeleton-line skeleton-line--50" style="margin:0"></div>'
                    + '<div class="skeleton skeleton-line skeleton-line--33" style="margin:0"></div>'
                    + '</div>'
                    + '<div class="skeleton skeleton-progress-track"></div>'
                    + '</div>';
            }
            return '<div class="skeleton-progress-list">' + items + '</div>';
        },
        notification: function () {
            var items = '';
            var widths = ['full', '75', '50'];
            for (var i = 0; i < 5; i++) {
                items += '<div class="skeleton-list-item">'
                    + '<div class="skeleton" style="width:4px;height:36px;border-radius:4px;flex-shrink:0"></div>'
                    + '<div style="flex:1;display:flex;flex-direction:column;gap:5px">'
                    + '<div class="skeleton skeleton-line skeleton-line--' + widths[i % 3] + '"></div>'
                    + '<div class="skeleton skeleton-line skeleton-line--sm skeleton-line--33"></div>'
                    + '</div></div>';
            }
            return '<div class="skeleton-list">' + items + '</div>';
        },
        chart: function () {
            var bars = '';
            [40, 70, 55, 85, 60, 90, 45, 75].forEach(function (h) {
                bars += '<div class="skeleton" style="flex:1;height:' + h + '%;border-radius:4px 4px 0 0"></div>';
            });
            return '<div style="display:flex;align-items:flex-end;gap:10px;height:240px;padding-bottom:4px">'
                + bars + '</div>'
                + '<div class="skeleton skeleton-line skeleton-line--50" style="margin-top:10px"></div>';
        },
        action: function () {
            var items = '';
            for (var i = 0; i < 4; i++) {
                items += '<div class="skeleton skeleton-block" style="height:40px;margin-bottom:8px"></div>';
            }
            return '<div>' + items + '</div>';
        },
        _default: function () {
            return '<div>'
                + '<div class="skeleton skeleton-line skeleton-line--full"></div>'
                + '<div class="skeleton skeleton-line skeleton-line--75"></div>'
                + '<div class="skeleton skeleton-line skeleton-line--full"></div>'
                + '<div class="skeleton skeleton-line skeleton-line--50"></div>'
                + '</div>';
        },
    };

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;');
    }

    /** Infers skeleton type from data-widget class basename. */
    function inferSkeletonType(identifier) {
        var id = (identifier || '').toLowerCase();
        if (/stat/.test(id))         return 'stats';
        if (/table/.test(id))        return 'table';
        if (/list/.test(id))         return 'list';
        if (/progress/.test(id))     return 'progress';
        if (/notif/.test(id))        return 'notification';
        if (/chart/.test(id))        return 'chart';
        if (/action/.test(id))       return 'action';
        return '_default';
    }

    function buildSkeleton(identifier) {
        var type = inferSkeletonType(identifier);
        var fn   = SKELETONS[type] || SKELETONS['_default'];
        return fn();
    }

    // -------------------------------------------------------------------------
    // Loading state
    // -------------------------------------------------------------------------

    /**
     * Sets the loading state on a widget card.
     * On START  : swap widget body for a skeleton.
     * On END    : skeleton is replaced by server HTML (done in refreshWidget).
     */
    function setLoading(widgetEl, loading, identifier) {
        widgetEl.classList.toggle(CLS.LOADING, loading);

        var btn = widgetEl.querySelector(CLS.REFRESH_BTN);
        if (btn) {
            btn.disabled = loading;
            btn.classList.toggle(CLS.LOADING, loading);
        }

        if (loading) {
            // Stash current body HTML so we can restore on error
            var body = widgetEl.querySelector('.' + CLS.WIDGET_BODY);
            if (body) {
                widgetEl.dataset.bodySnapshot = body.innerHTML;
                body.innerHTML = buildSkeleton(identifier || widgetEl.dataset.widget || '');
            }
        }
    }

    // -------------------------------------------------------------------------
    // Error banner
    // -------------------------------------------------------------------------

    function showError(widgetEl, message) {
        widgetEl.classList.add(CLS.ERROR);

        // Remove existing banner
        var old = widgetEl.querySelector('.' + CLS.ERROR_BANNER);
        if (old) { old.remove(); }

        // Restore original body content from snapshot
        var body = widgetEl.querySelector('.' + CLS.WIDGET_BODY);
        if (body && widgetEl.dataset.bodySnapshot) {
            body.innerHTML = widgetEl.dataset.bodySnapshot;
            delete widgetEl.dataset.bodySnapshot;
        }

        // Build banner
        var banner = document.createElement('div');
        banner.className = CLS.ERROR_BANNER;
        banner.setAttribute('role', 'alert');
        banner.innerHTML =
            '<span class="font-icon-cancel-circled" aria-hidden="true"></span>'
            + '<span>' + escapeHtml(message) + '</span>'
            + '<button type="button" class="' + CLS.ERROR_DISMISS + '" aria-label="Dismiss error">&times;</button>';

        banner.querySelector('.' + CLS.ERROR_DISMISS)
              .addEventListener('click', function () {
                  banner.remove();
                  widgetEl.classList.remove(CLS.ERROR);
              });

        if (body) {
            body.prepend(banner);
        } else {
            widgetEl.appendChild(banner);
        }
    }

    // -------------------------------------------------------------------------
    // Core refresh
    // -------------------------------------------------------------------------

    /**
     * Performs an AJAX refresh for a single widget.
     * 1. Show skeleton loader
     * 2. Fetch fresh HTML from server
     * 3. Swap in new inner HTML
     * 4. Re-execute any inline Chart.js scripts
     * 5. Re-animate progress bars
     *
     * @param {Element} widgetEl
     * @returns {Promise<void>}
     */
    function refreshWidget(widgetEl) {
        var identifier = widgetEl.getAttribute('data-widget');

        if (!identifier) {
            return Promise.reject(new Error('Widget element has no data-widget attribute.'));
        }

        // Clear previous error state
        widgetEl.classList.remove(CLS.ERROR);
        var oldBanner = widgetEl.querySelector('.' + CLS.ERROR_BANNER);
        if (oldBanner) { oldBanner.remove(); }

        setLoading(widgetEl, true, identifier);

        var url = BASE_REFRESH_URL + encodeURIComponent(identifier);

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SecurityID':     getCsrfToken(),
                'Accept':           'application/json',
            },
        })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (data) {
                    throw new Error(data.error || 'HTTP ' + response.status);
                }).catch(function () {
                    throw new Error('HTTP ' + response.status);
                });
            }

            return response.json();
        })
        .then(function (data) {
            if (!data.html) {
                throw new Error('Server returned empty widget HTML.');
            }

            // Replace the inner wrapper
            var inner = widgetEl.querySelector('.' + CLS.WIDGET_INNER);
            if (inner) {
                inner.outerHTML = data.html;
            } else {
                widgetEl.innerHTML = data.html;
            }

            // Delete snapshot – successful refresh
            delete widgetEl.dataset.bodySnapshot;

            // Re-run any inline scripts (Chart.js init blocks)
            reinitScripts(widgetEl);

            // Animate progress bars in the refreshed content
            animateProgressBars(widgetEl);
        })
        .catch(function (err) {
            showError(widgetEl, 'Refresh failed: ' + (err.message || 'Unknown error'));
        })
        .finally(function () {
            setLoading(widgetEl, false, identifier);
        });
    }

    /**
     * Refreshes all widgets that opt-in via data-supports-refresh="1".
     * Runs all refreshes in parallel (Promise.allSettled).
     *
     * @returns {Promise<void>}
     */
    function refreshAllWidgets() {
        var widgets = document.querySelectorAll(
            '.' + CLS.WIDGET + '[data-supports-refresh="1"]'
        );

        var allBtn = document.querySelector(CLS.REFRESH_ALL);
        if (allBtn) {
            allBtn.disabled = true;
            allBtn.classList.add(CLS.LOADING);
        }

        var promises = Array.prototype.map.call(widgets, refreshWidget);

        return Promise.allSettled(promises).then(function () {
            if (allBtn) {
                allBtn.disabled = false;
                allBtn.classList.remove(CLS.LOADING);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Chart.js re-initialisation
    // -------------------------------------------------------------------------

    /**
     * Browser does NOT execute <script> tags injected via innerHTML.
     * We manually recreate each script element so Chart.js instances
     * are initialised in refreshed widget HTML.
     */
    function reinitScripts(container) {
        var scripts = container.querySelectorAll('script');

        Array.prototype.forEach.call(scripts, function (oldScript) {
            var newScript = document.createElement('script');

            Array.prototype.forEach.call(oldScript.attributes, function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });

            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    // -------------------------------------------------------------------------
    // Progress bar animated entrance
    // -------------------------------------------------------------------------

    /**
     * Animates .progress-widget__fill elements from 0% → target width.
     * Uses IntersectionObserver so off-screen bars animate on scroll.
     *
     * @param {Element|Document} root  Scope – defaults to the full document
     */
    function animateProgressBars(root) {
        root = root || document;

        var fills = root.querySelectorAll('.progress-widget__fill');

        if (!fills.length) { return; }

        // Capture targets then reset to 0 for animation
        Array.prototype.forEach.call(fills, function (fill) {
            if (!fill.dataset.targetWidth) {
                fill.dataset.targetWidth = fill.style.width || '0%';
            }
            fill.style.width = '0%';
        });

        if (!('IntersectionObserver' in window)) {
            // Graceful fallback – animate immediately
            Array.prototype.forEach.call(fills, function (fill) {
                fill.style.width = fill.dataset.targetWidth;
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }

                var fill = entry.target;
                setTimeout(function () {
                    fill.style.width = fill.dataset.targetWidth || '0%';
                }, 80);

                observer.unobserve(fill);
            });
        }, { threshold: 0.1 });

        Array.prototype.forEach.call(fills, function (fill) {
            observer.observe(fill);
        });
    }

    // -------------------------------------------------------------------------
    // Event delegation
    // -------------------------------------------------------------------------

    function bindEvents() {
        // Click delegation – handles both individual and refresh-all buttons
        document.addEventListener('click', function (e) {
            var target = e.target;

            // Individual widget refresh button
            var refreshBtn = target.closest(CLS.REFRESH_BTN);
            if (refreshBtn) {
                e.preventDefault();
                var widgetEl = refreshBtn.closest('.' + CLS.WIDGET);
                if (widgetEl) { refreshWidget(widgetEl); }
                return;
            }

            // Refresh-all button
            var allBtn = target.closest(CLS.REFRESH_ALL);
            if (allBtn) {
                e.preventDefault();
                refreshAllWidgets();
            }
        });

        // Keyboard: Enter / Space on focusable refresh buttons
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') { return; }

            var refreshBtn = e.target.closest(CLS.REFRESH_BTN);
            if (refreshBtn) {
                e.preventDefault();
                var widgetEl = refreshBtn.closest('.' + CLS.WIDGET);
                if (widgetEl) { refreshWidget(widgetEl); }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    function init() {
        bindEvents();
        animateProgressBars();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    window.SSDashboard = {
        /**
         * Refresh a single widget element.
         * @param {Element} widgetEl  .dashboard-widget wrapper
         */
        refreshWidget: refreshWidget,

        /**
         * Refresh all widgets with data-supports-refresh="1".
         */
        refreshAllWidgets: refreshAllWidgets,

        /**
         * Build and return skeleton HTML for a given type.
         * @param {string} type  stats|table|list|progress|chart|action|notification
         */
        buildSkeleton: buildSkeleton,
    };

}(window, document));
