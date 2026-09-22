=== EurasiaPulse ===
Contributors: eurasiapulse
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: news, one-column, custom-logo, custom-menu, featured-images, threaded-comments, translation-ready, editor-style, block-styles

A lightweight, classic editorial theme for EurasiaPulse: politics and analysis across Central Asia and Eurasia.

== Description ==

Typography-led news design (Source Serif 4 + Inter, self-hosted), hairline rules, one accent colour, light and dark mode, no build step, no plugin dependencies, no jQuery. Built for REST-driven publishing: the `region` and `format` taxonomies and the article meta fields are all exposed to the WordPress REST API.

Templates: composed front page (lead, latest, analysis, section blocks, region strip, opinion), single article (single reading column), shared archive template for categories / regions / formats / tags, author, search, 404, page.

Top bar: date, secondary links, language switcher (Polylang / WPML detected automatically, otherwise a "Label|URL" list in the Customizer), social icons (X, Telegram, Instagram, Facebook, YouTube, LinkedIn, RSS) and a light/dark switch that remembers the visitor's choice and can follow the system setting. Footer: sections, regions, company pages, follow links and a bottom row of page links.

Updates: the theme can update itself from GitHub Releases (see below), no plugin required.

== Installation ==

1. Upload the `eurasiapulse` folder to `/wp-content/themes/` (or upload `eurasiapulse.zip` under Appearance > Themes > Add New > Upload Theme).
2. Activate the theme. On activation it creates the default vocabularies if they do not exist yet: categories (Politics, Economy, Security, Energy, Diplomacy), regions (Kazakhstan, Uzbekistan, Kyrgyzstan, Tajikistan, Turkmenistan, Caucasus, Russia, China, Global) and formats (News, Analysis, Opinion, Explainer, Interview). Existing terms are never changed.
3. Settings > Permalinks: choose "Post name" (the theme flushes rewrite rules on activation).
4. Settings > Reading: "Your latest posts" is fine — `front-page.php` always renders the composed news homepage. Optionally set a "Posts page" to get an "All updates" listing.
5. Appearance > Customize > EurasiaPulse: homepage blocks (which category each block shows, counts, thumbnails, lead style), header (newsletter link, date), article options (reading time, share links, author box, related count, comments on/off), footer & social links, structured data (organization logo, sameAs).
6. Appearance > Customize > Site Identity: upload a logo to replace the text masthead (max height 60 px is used).
7. Appearance > Menus (optional): assign "Primary navigation", "Top bar links" and the three footer columns. Without menus the theme falls back to the default categories, the About page, non-empty regions and the company pages (slugs: about, methodology, editorial-standards, contact, privacy).

== Publishing through the REST API ==

* Terms: `GET /wp-json/wp/v2/regions`, `GET /wp-json/wp/v2/formats`, `GET /wp-json/wp/v2/categories`.
* Create a post: `POST /wp-json/wp/v2/posts` with `regions: [id]`, `formats: [id]`, `categories: [id]`, `featured_media: id` and `meta: { ep_subtitle, ep_source_name, ep_source_url, ep_image_credit }`.
* Filter: `GET /wp-json/wp/v2/posts?regions=7&formats=17`.
* Authentication: an Application Password (Users > Profile) with Basic auth, or any other method that grants `edit_posts`.
* The REST field names are plural (`regions`, `formats`) to mirror core and to avoid the core `format` (post format) property.

== Meta fields ==

* `ep_subtitle` — dek / standfirst under the headline (also used for the meta description).
* `ep_source_name`, `ep_source_url` — shown in the "Source" box with `rel="nofollow noopener"`.
* `ep_image_credit` — shown after the featured-image caption (stored as written, e.g. "Photo: Agency").

All four are editable in the "Article details" meta box (classic and block editor).

== Structured data and SEO ==

Without an SEO plugin the theme prints a meta description, canonical links, Open Graph / Twitter tags and Schema.org JSON-LD (NewsMediaOrganization, WebSite, NewsArticle / AnalysisNewsArticle / OpinionNewsArticle / BackgroundNewsArticle, BreadcrumbList). When Yoast SEO, Rank Math, All in One SEO, SEOPress, The SEO Framework, Slim SEO or Squirrly is active, all of that is switched off automatically. Filter: `eurasiapulse_has_seo_plugin`.

== Image sizes ==

16:9 crops: ep-169-s (480×270), ep-169-m (800×450), ep-169-l (1200×675), ep-169-xl (1600×900). 3:2 crops: ep-32-s (240×160), ep-32-m (480×320), ep-32-l (720×480), ep-32-xl (1080×720). Upload featured images at least 1600 px wide. Run a thumbnail regeneration tool after switching to the theme on an existing site.

== Fonts ==

Inter and Source Serif 4 are bundled as variable WOFF2 subsets (latin, latin-ext, cyrillic, cyrillic-ext) from the Fontsource packages, licensed under the SIL Open Font License 1.1 (see assets/fonts/LICENSE-*.txt). No fonts are loaded from third-party servers.

== Updates from GitHub ==

Customize > EurasiaPulse > Updates (GitHub): enter the repository as "owner/name" (default: kazprose/eurasiapulse) and keep "Install new releases automatically" on. The theme reads the repository's latest release (cached 6 hours), compares its tag (v1.2.0) with the Version header and hands a newer release to WordPress' normal theme updater; with auto-updates on, WordPress installs it on its twice-daily check. Dashboard > Updates > "Check again" forces a check. The release must carry an "eurasiapulse.zip" asset (built by the repository's GitHub Actions workflow on every "v*" tag). Optional constants for wp-config.php: EURASIAPULSE_GITHUB_REPO (overrides the Customizer value) and EURASIAPULSE_GITHUB_TOKEN (private repositories).

== Changelog ==

= 1.1.1 =
* Updater: private repositories are supported with EURASIAPULSE_GITHUB_TOKEN (authenticated API calls, asset download through the API with redirect handling); the API base is filterable (eurasiapulse_github_api_base) for testing.

= 1.1.0 =
* Fix: on WordPress 6.x the theme.json "global styles" block was printed in the footer and recoloured / underlined every link (black headline over the lead image). Global styles are now kept off the front end entirely and link styles carry higher specificity.
* New: language switcher in the top bar (Polylang / WPML automatic, manual fallback list).
* New: social icons in the top bar (X, Telegram, Instagram, Facebook, YouTube, LinkedIn, RSS).
* New: light / dark mode switch with system preference support.
* New: footer bottom row with page links (menu location "Footer: bottom row" or top-level pages).
* New: self-updates from GitHub Releases, with optional automatic installation.
* Change: navigation and section fallbacks only use categories that contain posts.

= 1.0.0 =
* Initial release.
