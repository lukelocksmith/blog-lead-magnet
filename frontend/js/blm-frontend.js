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

    // Floating bar
    function initFloatingBar() {
        var bar = document.querySelector('.blm-floating-bar');
        if (!bar) return;

        var delay = parseInt(bar.getAttribute('data-delay') || '3', 10) * 1000;
        var dismissKey = 'blm_fb_dismissed_' + new Date().toISOString().slice(0, 10);

        // Check if dismissed today
        if (sessionStorage.getItem(dismissKey)) {
            bar.remove();
            return;
        }

        // Show after delay
        setTimeout(function() {
            bar.classList.add('blm-floating-bar--visible');
        }, delay);

        // Dismiss button
        var dismissBtn = bar.querySelector('.blm-floating-bar__dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                bar.classList.remove('blm-floating-bar--visible');
                sessionStorage.setItem(dismissKey, '1');
                setTimeout(function() { bar.remove(); }, 500);
            });
        }

        // Track floating bar clicks
        var fbButton = bar.querySelector('.blm-floating-bar__button');
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
        initFloatingBar();
    }

})();
