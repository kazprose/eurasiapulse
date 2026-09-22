# EurasiaPulse — WordPress theme

Lightweight classic WordPress theme for [eurasiapulse.com](https://eurasiapulse.com): politics and analysis across Central Asia and Eurasia. Typography-led editorial design (Source Serif 4 + Inter, self-hosted), hairline rules, one accent colour, light/dark mode, no build step, no plugin dependencies.

The installable theme is the `eurasiapulse/` folder. Everything else in this repository is documentation and local QA tooling.

| Path | What it is |
|---|---|
| `eurasiapulse/` | The theme (upload this folder, or the release zip) |
| `eurasiapulse/readme.txt` | Installation and configuration guide |
| `DECISIONS.md` | Assumptions and judgement calls, numbered |
| `REPORT.md` | What was built, how it was verified |
| `dev/` | Local environment and QA scripts (screenshots, Lighthouse, REST test) |
| `screenshots/` | Template screenshots and Lighthouse reports |
| `design/` | Original homepage mockup |

## Install

Download `eurasiapulse.zip` from the [latest release](https://github.com/kazprose/eurasiapulse/releases/latest) and upload it under **Appearance → Themes → Add New → Upload Theme**, then activate. Details in `eurasiapulse/readme.txt`.

## Updates from GitHub (no plugin)

The theme checks this repository's latest release and offers it under **Dashboard → Updates** (and installs it automatically when *Customize → EurasiaPulse → Updates (GitHub) → Install new releases automatically* is on, which is the default).

Release flow:

1. Bump `Version:` in `eurasiapulse/style.css` (and `EURASIAPULSE_VERSION` in `eurasiapulse/functions.php`), add a line to the changelog in `readme.txt`, commit.
2. Tag and push:

   ```bash
   git tag v1.2.0
   git push origin main --tags
   ```

3. GitHub Actions (`.github/workflows/release.yml`) checks that the tag matches the version header, lints PHP, builds `eurasiapulse.zip` and publishes a Release with that asset.
4. Sites running the theme pick it up on the next update check (twice a day; **Dashboard → Updates → Check again** forces it).

The repository is configurable: Customizer field *GitHub repository (owner/name)*, or `define( 'EURASIAPULSE_GITHUB_REPO', 'owner/name' );` in `wp-config.php`. For a private repository also define `EURASIAPULSE_GITHUB_TOKEN`.

## Local development

No Docker needed. `dev/` contains scripts for a portable PHP + SQLite WordPress under `.local/` (git-ignored); see `REPORT.md` §6.

## Licence

GPL-2.0-or-later. Bundled fonts (Inter, Source Serif 4) are under the SIL Open Font License 1.1 (`eurasiapulse/assets/fonts/`).
