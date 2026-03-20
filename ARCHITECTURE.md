# Architecture: ps_googleanalytics

## Purpose

A PrestaShop module that integrates Google Analytics 4 (GA4) tracking into the front
office. Tracks page views, product impressions, add-to-cart, purchases, and other
e-commerce events using GA4's data layer.

## Directory Structure

```
ps_googleanalytics.php   # Main module class; injects GA4 snippets via hooks
views/templates/          # Smarty/Twig templates for data layer push snippets
translations/             # Translation files
upgrade/                  # Migration scripts (from Universal Analytics to GA4)
tests/                    # PHPStan and unit tests
```

## Key Design Decisions

All tracking is implemented via JavaScript data layer pushes (Google Tag Manager pattern).
The module generates JSON data layer events in PHP/Smarty templates and injects them
into the page via `displayBeforeBodyClosingTag` and other hooks. This keeps GA4 event
data accurate by generating it server-side from PrestaShop's order/cart objects.

## Extension Points

Configure the GA4 Measurement ID in the module back-office settings.
Hook into `actionGoogleAnalyticsPushEvent` (if implemented) for custom event tracking.
