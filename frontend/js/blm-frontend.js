(function() {
    'use strict';

    // Session hash for deduplication
    var sessionHash = sessionStorage.getItem('blm_session');
    if (!sessionHash) {
        sessionHash = Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem('blm_session', sessionHash);
    }

    var trackedImpressions = {};

    // Track event via AJAX
    function trackEvent(ctaId, eventType) {
        if (!blm_data || !blm_data.ajax_url) return;

        var data = new FormData();
        data.append('action', 'blm_track_event');
        data.append('nonce', blm_data.nonce);
        data.append('cta_id', ctaId);
        data.append('post_id', blm_data.post_id);
        data.append('event_type', eventType);
        data.append('session_hash', sessionHash);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(blm_data.ajax_url, data);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', blm_data.ajax_url, true);
            xhr.send(data);
        }
    }

    // Impression tracking with IntersectionObserver
    function initImpressionTracking() {
        var ctaElements = document.querySelectorAll('.blm-cta[data-cta-id]');
        if (!ctaElements.length) return;

        if (!('IntersectionObserver' in window)) {
            // Fallback: track all as impressions immediately
            ctaElements.forEach(function(el) {
                var id = el.getAttribute('data-cta-id');
                if (id && !trackedImpressions[id]) {
                    trackedImpressions[id] = true;
                    trackEvent(id, 'impression');
                }
            });
            return;
        }

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var id = entry.target.getAttribute('data-cta-id');
                    if (id && !trackedImpressions[id]) {
                        trackedImpressions[id] = true;
                        trackEvent(id, 'impression');
                    }
                }
            });
        }, { threshold: 0.5 });

        ctaElements.forEach(function(el) {
            observer.observe(el);
        });
    }

    // Click tracking
    function initClickTracking() {
        document.addEventListener('click', function(e) {
            var button = e.target.closest('.blm-cta__button');
            if (!button) return;

            var ctaEl = button.closest('.blm-cta[data-cta-id]');
            if (!ctaEl) return;

            var id = ctaEl.getAttribute('data-cta-id');
            if (id) {
                trackEvent(id, 'click');
            }
            // Don't preventDefault — let the link navigate
        });
    }

    // Reading progress bar
    function initProgressBar() {
        var progressBar = document.getElementById('blm-progress-bar');
        if (!progressBar) return;

        function updateProgress() {
            var scrollTop = window.scrollY;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            var pct = docHeight > 0 ? Math.min(100, Math.round((scrollTop / docHeight) * 100)) : 0;
            progressBar.style.width = pct + '%';
        }
        window.addEventListener('scroll', updateProgress, { passive: true });
        updateProgress();
    }

    // Floating bar with TOC
    function initFloatingBar() {
        var bar = document.getElementById('blm-float');
        if (!bar) return;

        var tocList   = document.getElementById('blm-toc-list');
        var tocActive = document.getElementById('blm-toc-active');

        // Read TOC from server-side nav
        var seoNav = document.querySelector('.blm-toc-seo');
        var seoItems = seoNav ? seoNav.querySelectorAll('li') : [];

        if (tocList) {
            if (seoItems.length < 2) {
                var tocArea = bar.querySelector('.blm-float__toc-area');
                var sep = bar.querySelector('.blm-float__sep');
                if (tocArea) tocArea.style.display = 'none';
                if (sep) sep.style.display = 'none';
            } else {
                seoItems.forEach(function (item) {
                    var srcLink = item.querySelector('a');
                    if (!srcLink) return;

                    var li = document.createElement('li');
                    if (item.className) li.className = item.className;

                    var a = document.createElement('a');
                    a.href = srcLink.getAttribute('href');
                    a.textContent = srcLink.textContent;
                    li.appendChild(a);
                    tocList.appendChild(li);
                });

                // Active heading via IntersectionObserver
                var links = tocList.querySelectorAll('a');

                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        var id = entry.target.id;

                        links.forEach(function (a) {
                            a.classList.remove('active');
                            if (a.getAttribute('href') === '#' + id) a.classList.add('active');
                        });

                        if (tocActive) {
                            tocActive.textContent = entry.target.textContent.trim();
                        }
                    });
                }, { rootMargin: '-80px 0px -60% 0px', threshold: 0 });

                links.forEach(function (a) {
                    var id = a.getAttribute('href').replace('#', '');
                    var heading = document.getElementById(id);
                    if (heading) observer.observe(heading);
                });

                // Close panel after TOC click
                links.forEach(function (link) {
                    link.addEventListener('click', function () {
                        bar.setAttribute('aria-expanded', 'false');
                    });
                });
            }
        }

        // Track floating bar CTA clicks
        var fbButton = bar.querySelector('.blm-float__btn');
        if (fbButton) {
            fbButton.addEventListener('click', function() {
                trackEvent('floating-bar', 'click');
            });
        }
    }

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initImpressionTracking();
        initClickTracking();
        initProgressBar();
        initFloatingBar();
    }

})();

// Toggle function (global, called from onclick in floating bar)
function toggleBlmFloat() {
    var bar = document.getElementById('blm-float');
    if (!bar) return;
    var isOpen = bar.getAttribute('aria-expanded') === 'true';
    bar.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
}
