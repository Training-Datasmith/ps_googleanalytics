<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_googleanalytics PrestaShop module.
 *
 * ps_googleanalytics integrates Google Analytics 4 (GA4) tracking with your
 * PrestaShop store. It injects the GA4 script and sends e-commerce events
 * (view_item, add_to_cart, purchase, etc.) for sales analytics.
 *
 * This file documents common configuration and integration patterns.
 */

// --- Back Office configuration ---
// Modules > Google Analytics:
//   - Google Analytics 4 Measurement ID (format: G-XXXXXXXXXX)
//   - Enable/disable e-commerce tracking
//   - Enable/disable cross-domain tracking
//   - Cookie consent mode (wait for consent before sending hits)
//   - Enable Google Ads remarketing tags

// --- Hook: displayHeader ---
// The module injects the GA4 script tag and configuration in <head>:
//
// <!-- Google tag (gtag.js) -->
// <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
// <script>
//   window.dataLayer = window.dataLayer || [];
//   function gtag(){dataLayer.push(arguments);}
//   gtag('js', new Date());
//   gtag('config', 'G-XXXXXXXXXX');
// </script>

// --- E-commerce events tracked automatically ---
// view_item         — product detail page viewed
// add_to_cart       — product added to cart
// remove_from_cart  — product removed from cart
// view_cart         — cart page viewed
// begin_checkout    — checkout initiated
// add_payment_info  — payment step completed
// purchase          — order confirmation (revenue, tax, shipping)
// refund            — order refund processed

// --- Custom event tracking via dataLayer ---
// Push custom events from your own modules or themes:
//
// <script>
//   gtag('event', 'custom_event', {
//     'event_category': 'wishlist',
//     'event_label': 'add_product',
//     'product_id': 42,
//   });
// </script>

// --- Hook: displayOrderConfirmation ---
// The module fires the `purchase` GA4 event on the order confirmation page.
// Order revenue, items, tax, and shipping are sent automatically.

// --- Consent mode integration ---
// If using a cookie consent module (e.g., ps_dataprivacy or a CMP),
// configure consent mode in GA4 to delay sending hits until consent is given:
//
// gtag('consent', 'default', {
//   'analytics_storage': 'denied',
//   'ad_storage': 'denied',
// });
