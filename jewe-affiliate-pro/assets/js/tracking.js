/**
 * JEWE Affiliate Pro - Tracking JavaScript
 */

(function() {
    'use strict';

    // Track page views with affiliate context
    document.addEventListener('DOMContentLoaded', function() {
        trackPageView();
    });

    function trackPageView() {
        // Check if we have affiliate cookie
        var affiliateCookie = getCookie('jewe_affiliate_ref');
        if (!affiliateCookie) {
            return;
        }

        // Send tracking data
        var data = {
            action: 'jewe_track_pageview',
            nonce: typeof jeweAffiliateTracking !== 'undefined' ? jeweAffiliateTracking.nonce : '',
            page_url: window.location.href,
            referrer: document.referrer,
            timestamp: Date.now()
        };

        // Use beacon API for non-blocking request
        if (navigator.sendBeacon && typeof jeweAffiliateTracking !== 'undefined') {
            var formData = new FormData();
            for (var key in data) {
                formData.append(key, data[key]);
            }
            navigator.sendBeacon(jeweAffiliateTracking.ajaxurl, formData);
        }
    }

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }

    // Track outbound clicks
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a');
        if (!link) return;

        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        // Check if external link
        try {
            var url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) {
                trackOutboundClick(href);
            }
        } catch (e) {
            // Invalid URL, ignore
        }
    });

    function trackOutboundClick(url) {
        var affiliateCookie = getCookie('jewe_affiliate_ref');
        if (!affiliateCookie) return;

        if (typeof jeweAffiliateTracking !== 'undefined') {
            var data = new FormData();
            data.append('action', 'jewe_track_outbound');
            data.append('nonce', jeweAffiliateTracking.nonce);
            data.append('url', url);

            navigator.sendBeacon(jeweAffiliateTracking.ajaxurl, data);
        }
    }

})();
