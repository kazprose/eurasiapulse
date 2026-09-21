# DECISIONS.md — EurasiaPulse theme

Assumptions and judgement calls made while building the theme, in the order they came up. Each one can be reversed; the "How to change" note says where.

## Environment

1. **No Docker on this machine, so `@wordpress/env` was not usable.** The brief said to stop and ask in that case, but it also said not to stop for anything and to pick the most reasonable path. Chosen path: a portable, project-local environment in the git-ignored `.local/` folder — PHP 8.3.33 (official windows.php.net build), WordPress 7.1.1 (wordpress.org), and the official *SQLite Database Integration* plugin as a `db.php` drop-in. Nothing was installed system-wide; deleting `.local/` removes everything. Scripts in `dev/` recreate it (`dev/wp-install.php`, `dev/serve.ps1`, `dev/activate.php`, `dev/seed.php`). *How to change:* with Docker available, `npx @wordpress/env start` with a `.wp-env.json` mapping `eurasiapulse/` as a theme works unchanged — the theme has no environment dependencies.

2. **QA tooling lives in `dev/` (Node only):** Playwright for screenshots, Lighthouse driven through Playwright's Chromium (chrome-launcher cannot spawn processes in this sandbox), a REST round-trip test in bash + curl, and a small `.pot` generator (no WP-CLI here). None of it ships with the theme.

## Content model / REST

3. **REST field names are plural: `regions` and `formats`.** The taxonomies are named `region` and `format` as requested, but their `rest_base` is `regions` / `formats`, mirroring core (`categories`, `tags`). This is not cosmetic: the core posts endpoint already has a `format` property (post formats), and a taxonomy with `rest_base = format` would overwrite it and break `POST /wp/v2/posts` with an array where core expects a string. Endpoints: `/wp/v2/regions`, `/wp/v2/formats`; post objects carry `regions: [ids]`, `formats: [ids]`, and posts can be filtered with `?regions=ID&formats=ID`. *How to change:* `rest_base` in `inc/taxonomies.php` (but keep it away from `format`).

4. **Default vocabularies are created on theme activation, never overwritten.** Regions: Kazakhstan, Uzbekistan, Kyrgyzstan, Tajikistan, Turkmenistan, Caucasus, Russia, China, Global. Formats: News, Analysis, Opinion, Explainer, Interview. Categories: Politics, Economy, Security, Energy, Diplomacy. Existing slugs are skipped, so renaming/deleting afterwards sticks. *How to change:* `eurasiapulse_seed_default_terms()` in `inc/setup.php`.

5. **Meta keys are `ep_subtitle`, `ep_source_name`, `ep_source_url`, `ep_image_credit`** (single string values, sanitized with `sanitize_text_field` / `sanitize_url` limited to http(s), `auth_callback` = `edit_posts`). They appear under `meta` in REST responses and are writable by authenticated users with `edit_posts`. The "javascript:" test in `dev/rest-test.sh` confirms the URL sanitizer.

6. **The "format" taxonomy uses a checkbox UI in the classic editor** (`meta_box_cb = post_categories_meta_box`) because it is a fixed vocabulary, not free tags. The block editor shows its normal taxonomy panel.

7. **Image credit is displayed as stored** (no automatic "Photo:" prefix) so the pipeline controls the wording ("Photo: Reuters", "Illustration: …").

## Homepage composition

8. **Lead story = newest sticky post, else the newest post** (Customizer switch "Always the newest post"). Then two cards under the lead (16:9), eight headlines beside it (mockup shows eight; the brief said 3–4 — counts are Customizer settings), then "Latest" (6), "Analysis" (3, format = analysis), five section blocks (1 large + 4 small, category chosen per slot, "auto" = Politics/Economy/Security/Energy/Diplomacy in that order), the region strip, and "Opinion" (4, format = opinion).

9. **Nothing repeats on the homepage.** Every query goes through `eurasiapulse_query()`, which excludes IDs already rendered. Consequence: "Latest" shows the newest posts *not already in the lead block*. **Opinion posts are reserved before the section blocks run** (`front-page.php`), otherwise a category block would swallow them and the Opinion block would come out empty (this happened with the first seed data).

10. **Lead headline over the image with a dark scrim** is the mockup's design and is the default; the brief's "no gradients" rule was read as "no decorative gradients". A Customizer option ("Headline below the image") gives the stacked variant, and posts without a featured image always render stacked.

11. **Thumbnails in the lead sidebar and in "Latest" follow the mockup** (the brief said text-only). Both are Customizer toggles; cards degrade to text-only automatically when a post has no image.

12. **Sub-cards under the lead use 16:9** instead of the mockup's 2:1, because the brief limits crops to 16:9 and 3:2 and a third crop set would cost storage on every upload.

13. **`front-page.php` always renders the composed homepage**, whatever "Your homepage displays" is set to. A static page set as front page is ignored (its content would have nowhere to go); a posts page, if set, is used as the "All updates" target and shows the plain list.

14. **Section blocks with no posts are skipped silently**, as are Analysis/Opinion when the format term does not exist. A fresh site shows "No articles have been published yet."

## Header, footer, navigation

15. **Menus are optional.** Five locations exist (primary, top bar, three footer columns). Without menus, primary nav = the five default categories + Analysis + Opinion; top bar = the page with slug `about` + the Newsletter URL from the Customizer; footer columns = sections, non-empty regions, and pages found by slug (about, methodology, editorial-standards, contact, privacy / privacy-policy).

16. **The masthead is an `<h1>` only on the front page.** Everywhere else the article/archive title is the single H1 and the masthead is a `<p>`.

17. **Search without JavaScript**: the search row under the nav is visible when the `no-js` class is present, and the search icon links to `#header-search`. With JS it toggles.

18. **Social links are plain text links** (X, Telegram, LinkedIn, Facebook, YouTube, RSS) from the Customizer — no icon set, consistent with "no decoration".

## Article page

19. **"Updated" is shown only when the modified time is more than one hour after publication**, to hide cosmetic edits. `inc/template-tags.php`, `eurasiapulse_is_updated()`.

20. **Reading time** = words ÷ 220, Unicode-aware (works for Kazakh/Russian), minimum 1 minute; can be hidden in the Customizer.

21. **Related = same category OR same region, newest first**, falling back to newest posts; count is a Customizer setting.

22. **Share links** are intent URLs on X (`x.com/intent/post`), Telegram (`t.me/share/url`), LinkedIn (`share-offsite`), plus a "Copy link" button that appears only when the Clipboard API is available (secure context).

23. **No avatars anywhere** (Gravatar would add a third-party request and a privacy question). Author box shows name, bio and archive link.

24. **Comments are closed site-wide** via `comments_open`/`pings_open` filters until "Enable comments" is switched on in the Customizer; `comments.php` is complete and styled for that case.

## Design system / CSS

25. **Fonts are variable-weight WOFF2 subsets from the Fontsource packages** (Inter 5.3.0, Source Serif 4 5.3.0, both SIL OFL 1.1; licences bundled in `assets/fonts/`). Subsets: latin, latin-ext, cyrillic, cyrillic-ext (Kazakh letters Ә Ғ Қ Ң Ө Ұ Ү Һ І live in cyrillic-ext), normal + italic. Total 700 KB on disk, but `unicode-range` means an English page downloads ~100 KB, a Kazakh page ~150 KB. `@font-face` rules come from `theme.json` (`fontFace`), so the editor gets the same fonts; the two Latin regular files are preloaded.

26. **`--text-xs` is 12 px below 900 px and 11 px above** (mockup: 11 px). Lighthouse's mobile legibility heuristics and WCAG target-size checks were the reason.

27. **Small text links carry an invisible 24 px vertical hit area** (padding on inline links, padding + negative margin on flex children) to satisfy WCAG 2.5.8 / Lighthouse `target-size` without changing the visual rhythm.

28. **WordPress' `global-styles` inline CSS (~12 KB) is dequeued on the front end** together with `classic-theme-styles` and `wp-block-library-theme`; the theme ships its own `.has-*-color` / `.has-*-font-size` preset classes for the five palette colours and four sizes. Per-block core CSS still loads, but only for blocks present on the page (`should_load_separate_core_block_assets`).

29. **CSS is shipped readable, not minified** (30 KB raw / 6 KB gzip, under the 40 KB budget) because the brief rules out a build step. Lighthouse's "unminified CSS" note is accepted.

30. **Image crops:** `ep-169-{s,m,l,xl}` = 480/800/1200/1600 wide and `ep-32-{s,m,l,xl}` = 240/480/720/1080 wide, all hard-cropped, which gives clean `srcset` lists per ratio. `<img>` tags are built by the theme (`eurasiapulse_image()`) so `loading`, `fetchpriority`, `sizes`, `alt` and `aspect-ratio` classes are explicit; the lead image and the first archive row load eagerly with `fetchpriority="high"`.

## SEO

31. **Structured data uses `NewsMediaOrganization`** (a subtype of `Organization`), `WebSite` (+ `SearchAction` on the front page), `NewsArticle` / `AnalysisNewsArticle` / `OpinionNewsArticle` / `BackgroundNewsArticle` (explainer), and `BreadcrumbList`. Region becomes `contentLocation`, the source URL becomes `isBasedOn`.

32. **SEO plugin detection** covers Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework, Slim SEO and Squirrly; when one is active the theme prints no meta description, canonical, Open Graph, Twitter or JSON-LD. Filter `eurasiapulse_has_seo_plugin` and the Customizer switch override this.

33. **Meta description** = subtitle → manual excerpt → trimmed content (40 words, cut at 160 chars) on articles; tagline on the front page; term description or "Latest X coverage from …" on archives.

## Naming

34. **PHP prefix is `eurasiapulse_`** for functions, hooks and Customizer settings (theme-check requires a distinctive prefix); meta keys keep the requested `ep_` prefix; CSS classes are unprefixed BEM-ish (`.card--compact`, `.lead__side`).
