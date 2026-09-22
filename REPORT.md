# REPORT.md — EurasiaPulse WordPress theme

Deliverables: `eurasiapulse/` (theme source), `eurasiapulse.zip` (installable), `eurasiapulse/readme.txt` (installation guide), `DECISIONS.md` (34 recorded assumptions), `screenshots/` (36 template screenshots + admin screens + Lighthouse reports), `dev/` (local environment and QA scripts). Three local git commits (stages 1–3, 4–5, 6), no push, no deploy.

## 1. What was built

**Theme (classic, WP 6.5+ / PHP 8.1+, text domain `eurasiapulse`, no build step, no plugin dependency, no jQuery):**

| Area | Files |
|---|---|
| Bootstrap & modules | `functions.php`, `inc/setup.php` (supports, menus, image sizes, wp_head clean-up, comments policy, default terms on activation), `inc/enqueue.php`, `inc/taxonomies.php`, `inc/meta.php`, `inc/customizer.php`, `inc/template-tags.php`, `inc/schema.php` |
| Design system | `theme.json` (palette, font sizes, layout 680/1240, self-hosted `fontFace` rules), `assets/css/main.css` (tokens, mobile-first, breakpoints 600/900/1200), `assets/css/editor.css`, `assets/js/main.js`, `assets/fonts/` (Inter + Source Serif 4 variable WOFF2, 4 subsets × normal/italic, OFL licences) |
| Templates | `header.php`, `footer.php`, `front-page.php`, `index.php`, `single.php`, `page.php`, `archive.php` (category / region / format / tag / date), `author.php`, `search.php`, `404.php`, `searchform.php`, `comments.php` |
| Parts | `template-parts/card-lead`, `card-standard`, `card-compact` (+ archive row variant), `card-text-only`, `card-latest`, `article-meta`, `source-box`, `share`, `author-box`, `related`, `loop`, `home/{lead,latest,analysis,category,regions,opinion}` |
| i18n | `languages/eurasiapulse.pot` (158 strings, generated) |

**Content model (all `show_in_rest`):** taxonomy `region` (hierarchical, REST `regions`), taxonomy `format` (flat, REST `formats`, checkbox UI), post meta `ep_subtitle`, `ep_source_name`, `ep_source_url`, `ep_image_credit` (string, single, sanitized, `auth_callback` = `edit_posts`), an "Article details" meta box that works in the classic and block editor, and default terms created on activation (never overwritten).

**Homepage** (order as in the mockup): lead story (16:9 hero, headline over a scrim or below, subtitle, byline, reading time) + 2 cards + 8 headlines → Latest (time stamps) → Analysis (3 cards, kicker, caption, credit) → five section blocks (1 large + 4 small, category per slot chosen in the Customizer) → By Region strip → Opinion (author first, text only). No post appears twice; Opinion posts are reserved before the section blocks run.

**Article page:** format kicker + region · category, H1, subtitle, byline, published / updated (if > 1 h later) / reading time, featured image with caption + credit (eager, `fetchpriority=high`), 680 px reading column with styles for H2–H4, lists, blockquote, pull quote, tables (horizontal scroll), embeds (16:9), images, code; then Source box (`rel="nofollow noopener"`), topics, share (X, Telegram, LinkedIn, Copy link — plain URLs, no SDK), author box, Related (same region or category, 4).

**Header/footer:** top bar (date, About/Newsletter or a menu), text masthead (H1 on the front page only; custom logo replaces it), sticky section nav with full-screen mobile menu and no-JS fallback, search toggle; footer with Sections / Regions / Company columns (menus or automatic fallbacks), social text links, copyright and tagline.

**SEO / performance / a11y:** JSON-LD graph (NewsMediaOrganization, WebSite + SearchAction, NewsArticle / AnalysisNewsArticle / OpinionNewsArticle / BackgroundNewsArticle, BreadcrumbList), meta description, canonical on archives, Open Graph + Twitter cards — all switched off automatically when Yoast / Rank Math / AIOSEO / SEOPress / The SEO Framework / Slim SEO / Squirrly is active. Emoji script, generator, wlwmanifest, RSD, shortlink, extra feeds, oEmbed host JS, resource hints, `global-styles`, `classic-theme-styles` and `wp-block-library-theme` removed from the front end; per-block core CSS only for blocks on the page. Skip link, one H1 per page, landmarks, ARIA on toggles, visible focus, 24 px touch targets, `prefers-reduced-motion`, explicit `width`/`height` + `aspect-ratio` on every image (CLS 0).

## 2. Stages and commits

1. **Skeleton, theme.json, fonts, tokens, header/footer** — commit `088a047` (together with stages 2–3).
2. **Taxonomies, post meta, meta box** — same commit.
3. **Cards + front page** — same commit.
4. **single.php + article typography** — commit `f990285` (with stage 5).
5. **archive / author / search / 404 / page** — same commit.
6. **Schema, performance, a11y, docs, zip** — final commit (see `git log`).

Stage-end notes (what was decided / what was left) are consolidated in `DECISIONS.md`; the open items are in section 5 below.

## 3. Assumptions (full list in DECISIONS.md)

* No Docker → portable PHP 8.3.33 + WordPress 7.1.1 + SQLite Database Integration in `.local/` (git-ignored, nothing installed system-wide). The brief said to stop if Docker was missing; the "never stop" instruction was followed instead, and this is flagged here.
* REST field names are `regions` / `formats` (plural) because the core posts endpoint already owns `format`.
* Mockup wins on visuals: headline-over-image lead with a scrim (Customizer switch for the stacked variant), thumbnails in the lead sidebar and in Latest (both toggles), eight sidebar headlines, four small items per section block.
* Only the homepage mockup was present in `design/`; the article page was designed from the brief (single column, 680 px measure, 900 px image).
* `--text-xs` is 12 px on small screens (11 px from 900 px) for legibility and touch-target audits.
* Sub-cards under the lead use 16:9 (brief allows 16:9 and 3:2 only; mockup showed 2:1).
* Prefix `eurasiapulse_` for PHP, `ep_` for meta keys (as requested).

## 4. Verification results

**PHP lint:** `php -l` on all 37 PHP files → no syntax errors.

**WP_DEBUG (on, logging to file):** `.local/debug.log` stayed empty across every front-end template (home, single ×4 variants, category, region, format, tag, author, search, page, 404, paged home, feed, robots.txt, REST) and across wp-admin (block editor with the meta box, Regions and Formats screens, Customizer). The admin smoke test (`dev/admin-check.mjs`) also reported no JS/console errors and found 18 controls in the Homepage Customizer section.

**REST round-trip (`dev/rest-test.sh`, log in `dev/rest-test.log`) — 15/15 checks passed:**

* `GET /wp/v2/regions`, `GET /wp/v2/formats` return the terms.
* Authenticated `POST /wp/v2/posts` with `regions`, `formats`, `categories` and all four `meta` fields creates the post; an unauthenticated `GET` reads back `regions`, `formats`, `meta.ep_subtitle`, `meta.ep_source_name`, `meta.ep_source_url`, `meta.ep_image_credit`.
* Update clears `regions`/`formats`; `ep_source_url = "javascript:alert(1)"` is sanitized to `""`; HTML in `ep_subtitle` is stripped.
* Anonymous write → 401. The updated subtitle renders in the article's dek. Test post deleted afterwards.

**Screenshots (`screenshots/`, Playwright, 1440 / 768 / 375 px, full page):** home, single (with image), single-no-image, single-long-title, single-kazakh (Cyrillic-ext glyphs), category, region, format, author, search, page, 404 — 36 files — plus `home-375-menu-open.png`, `home-375-search-open.png`, `admin-block-editor.png`, `admin-formats.png`, `admin-customizer.png`. No horizontal overflow at any width (checked programmatically). `eurasiapulse/screenshot.png` is a 1200×900 capture of the homepage.

**Lighthouse 12 (mobile, simulated throttling, reports in `screenshots/lighthouse-*.html|json`):**

| Page | Performance | Accessibility | Best practices | SEO | LCP | CLS | TBT |
|---|---|---|---|---|---|---|---|
| Home | 95 | 100 | 100 | 100 | 2.6 s | 0 | 10 ms |
| Article | 98 | 100 | 100 | 100 | 2.3 s | 0 | 0 ms |
| Category | 99 | 100 | 100 | 100 | 2.1 s | 0 | 0 ms |

Targets (Performance ≥ 90, Accessibility ≥ 95, SEO 100) met. Remaining performance notes are outside the theme: no text compression and no cache headers on PHP's built-in server, its response time, unoptimised placeholder JPEGs, and the intentionally unminified stylesheet.

**Budgets:** `main.css` 31.8 KB raw / 6.3 KB gzip (< 40 KB); `main.js` 2.6 KB raw / 0.9 KB gzip (< 10 KB). Front page HTML ≈ 95 KB (mostly `srcset` lists), inline CSS on the front page ≈ 4.5 KB (core block-library common styles + font faces).

## 5. Not done / open points

1. **Environment differs from the brief:** SQLite + PHP built-in server instead of `@wordpress/env` (Docker). Everything in the theme is environment-agnostic, but it has not been run on MySQL/MariaDB or a real web server (Apache/nginx) in this session.
2. **No article-page mockup was provided**, so the article design follows the brief and the homepage's visual language rather than a reference file.
3. **No PHPCS run** (no Composer/PHP tooling here). Code was written to WordPress Coding Standards by hand (tabs, Yoda conditions, escaping, sanitizing, docblocks, `phpcs:ignore` where output is pre-escaped), but an automated WPCS pass is still advisable.
4. **SEO-plugin detection is by constants/classes only**; it was not exercised with the plugins installed.
5. **Kazakh/Russian UI translations are not included** — only the `.pot` template. UI language is English as requested.
6. **Existing media needs thumbnail regeneration** after activation on a site with old uploads (eight new crops are registered).
7. **Placeholder JPEGs** in the local site are GD-generated grey images; Lighthouse's image-optimisation hint refers to them, not to theme code.
8. **`front-page.php` ignores a static front page's content** by design; if a static homepage is ever wanted, that decision should be revisited.

## 6. Version 1.1.0 — after the first live test on eurasiapulse.com

**Bug found on the live site (WordPress 6.9.8):** every link was black and underlined, including the lead headline over the image. Cause: on WP 6.x a classic theme with separate block assets gets the theme.json "global styles" printed in the *footer*, after `main.css`, and core's `a:where(...)` rules (ink colour, underline) won. WP 7.1.1, used for the first round of testing, prints the same block in the head, where the theme stylesheet overrides it — so the bug was invisible locally. Fix: global styles removed from the front end on both hooks, theme-defined preset variables, and link rules with higher specificity (`.site-main a` etc.). Reproduced and verified on a second local instance running WordPress 6.9.8 (`.local/wp69`, port 8081, screenshots in the run log) and re-verified on 7.1.1.

**New in 1.1.0 (requested after the live test):** language switcher (Polylang / WPML / manual list), social icons in the top bar, light/dark mode switch with system preference, footer page-link row, navigation fallbacks limited to categories that hold posts, and a GitHub-based self-updater with automatic installation plus a release workflow (`.github/workflows/release.yml`). Details in DECISIONS.md §35–42 and `eurasiapulse/readme.txt`.

**Verification, 1.1.0:** `php -l` clean (38 files); `node --check` clean; WP_DEBUG log empty on 7.1.1 and 6.9.8 for the front end and on 7.1.1 for wp-admin; REST test unchanged (15/15); 36 screenshots regenerated with no horizontal overflow; Lighthouse mobile home / article / category: performance 95 / 98 / 98, accessibility 100 / 100 / 100, best practices 100, SEO 100; `main.css` 35.2 KB raw / 6.9 KB gzip, `main.js` 3.9 KB raw / 1.4 KB gzip.

**Deployment note:** the copy installed on eurasiapulse.com is 1.0.0 and has no updater. Install 1.1.0 once by uploading `eurasiapulse.zip` (or the GitHub release asset) over it; from then on new releases (tag `vX.Y.Z` on GitHub) are picked up and installed automatically.

## 7. How to run locally (Windows, no Docker)

```
powershell -File dev/serve.ps1          # http://127.0.0.1:8080  (admin / admin-local-password)
.local/php/php.exe dev/seed.php --reset # placeholder content (optional)
bash dev/rest-test.sh                   # REST round-trip test
node dev/screenshots.mjs                # screenshots/ + eurasiapulse/screenshot.png
node dev/lighthouse.mjs                 # screenshots/lighthouse-*.html
node dev/admin-check.mjs                # wp-admin smoke test
node dev/make-pot.mjs                   # regenerate languages/eurasiapulse.pot
```

`.local/` was created by `dev/wp-install.php` after unpacking PHP, WordPress and the SQLite plugin (see DECISIONS.md §1). With Docker available, `@wordpress/env` can be used instead without any change to the theme.
