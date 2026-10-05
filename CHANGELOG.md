# Changelog

All notable changes to Blog Lead Magnet will be documented in this file.

## [1.1.3] - 2026-10-05

### Added
- Structured data for the table of contents restored as a single JSON-LD `SiteNavigationElement` (name + url per entry, `hasPart`), linked to the page via `isPartOf` (`#webpage` node of the SEO plugin). It is generated from the final page HTML using the same configuration as the visible Bricks post TOC (`data-content-selector` / `data-heading-selectors` of `.brxe-post-toc`), so the schema always matches the TOC shown to users (including headings from Bricks modules such as FAQ). Skipped when the page has no visible TOC or fewer than 2 entries.

### Fixed
- Headings carrying `data-section-id` (leftover of pasting from ChatGPT) did not get an anchor id because the "has id" check matched `data-section-id` as `id`. They now get `h-N` like every other heading, so the TOC links and the schema URLs resolve in the static HTML.

## [1.1.2] - 2026-09-30

### Removed
- Hidden server-side TOC (`.blm-toc-seo`, schema.org `SiteNavigationElement`). It was invisible to users, included H3 unlike the visible TOC, and gave crawlers conflicting navigation data.

### Changed
- Floating bar TOC is now built from the same headings as the visible Bricks post TOC (`data-content-selector` / `data-heading-selectors` of `.brxe-post-toc`, fallback `.blog-content h2`). Empty headings are skipped.

## [1.1.1] - 2026-04-16

### Added
- Per-post CTA overrides — full accordion in post meta box (heading, body, shortcode, button, colors, position per CTA)
- Analytics per-post breakdown — expandable rows in analytics dashboard showing impressions/clicks by post
- Live CTA preview in admin form (updates on input change)
- Mode-conditional field visibility in floating bar settings (Autor/Przycisk hidden in toc_only mode)
- WP Cron daily cleanup for analytics table
- Rate limiting (30 req/IP/min) on tracking AJAX endpoint

### Changed
- "+ Dodaj CTA" button moved to page header row (opposite plugin title)
- Floating bar settings page redesigned to match admin design system
- Post meta box CTA cards wrapped in container with visual separation (gray background, gap between cards)
- `orderby=rand` replaced with deterministic shuffle + transient cache (fixes N random DB scans)

### Fixed
- Capability check order: `current_user_can()` now runs before nonce check in all form handlers
- `maybe_upgrade()` no longer runs `SHOW COLUMNS` on every request (gated behind version option)
- `blm_init()` early-returns for AJAX/Cron/REST before `is_admin()` check (admin classes were loading on every AJAX request)
- N+1 query in analytics AJAX: `_prime_post_caches()` before post loop
- N+1 query in CTA list: `get_terms()` batch instead of N× `get_term_by()`
- O(n²) → O(n) content processing in `find_percent_offset()`
- Enum whitelist validation for `type` and `display_condition` fields
- `wp_enqueue_media()` no longer loads on non-post CPTs
- PHP parse error: `foreach` value destructuring syntax incompatible with some PHP versions

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
