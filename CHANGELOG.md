# Changelog

All notable changes to Blog Lead Magnet will be documented in this file.

## [1.1.0] - 2026-04-09

### Changed
- Display conditions: "after H2" replaced with specific section targeting (after 1st, 2nd, 3rd, 4th, 5th H2 section)
- Floating bar completely rebuilt: author avatar + name + role + CTA button | expandable Table of Contents
- Server-side TOC with schema.org SiteNavigationElement for SEO/AI visibility
- Reading progress bar on top of page
- Media uploads via WP Library picker (no manual URLs)
- UTF-8 accurate percentage injection (mb_strlen based)
- Last H2 section no longer silently skipped

### Added
- `[blm_product]` shortcode — product card (image, name, price, description, button)
- `[blm_related_posts]` shortcode — related articles from same category or by IDs
- Floating bar TOC with active heading tracking (IntersectionObserver)
- Assets skip loading when plugin has nothing to display

### Fixed
- admin_init timing bug — form actions now fire correctly
- Nonce + page cache compatibility — soft check for tracking endpoint
- Frontend classes no longer load on REST API, AJAX, or Cron requests
- Product button visibility (was invisible due to currentColor CSS)
- Dedup query index added for analytics performance at scale

## [1.0.0] - 2026-04-08

### Added
- Flexible CTA builder with unlimited CTAs
- Display conditions: after H2, after 30/50/70% of article, end of article
- Exclusion rule — one CTA per position, priority wins
- Full CTA customization: heading, body, image, shortcode, button, colors, text size
- Analytics tracking with IntersectionObserver (impressions) and sendBeacon (clicks)
- Analytics dashboard with date range filters (7/30/90 days)
- GitHub auto-updater for seamless updates from releases
- Transient caching for active CTAs (1h TTL, invalidated on change)
