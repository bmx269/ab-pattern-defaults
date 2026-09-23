# AB Pattern Defaults - Development Guide

## Project Overview
WordPress plugin that pre-populates the block editor with a configurable block pattern per post type. Pure PHP (no JS build step). Database-stored patterns take priority over file-registered patterns.

## Repository Structure
```
├── ab-pattern-defaults.php   # Main plugin file (settings UI + default_content filter)
├── uninstall.php             # Removes plugin option on delete
├── readme.txt                # WordPress.org plugin readme
├── README.md                 # GitHub-facing documentation
├── languages/                # Translation files (POT); regenerate with `wp i18n make-pot . languages/ab-pattern-defaults.pot --exclude=.github,.claude`
├── blueprint.json            # WordPress Playground blueprint (installs from WordPress.org)
├── build.sh                  # Builds build/ab-pattern-defaults/ + .zip from .distignore; checks Version == Stable tag
├── deploy.sh                 # Manual SVN deploy fallback (`./deploy.sh <svn-checkout>`), uses build.sh
├── .distignore               # Excluded from the WP.org package; must list /.git (10up deploy action)
├── .wordpress-org/           # WP.org assets (banners, icons, screenshots) - if/when added
└── .github/workflows/
    ├── plugin-check.yml      # CI: build, then Plugin Check the package, on push/PR to main
    ├── deploy.yml            # CD: GitHub release published -> version check -> SVN trunk + tag, zip attached to release
    └── wporg-assets.yml      # readme.txt / .wordpress-org changes -> SVN; idle until repo variable WPORG_SVN_READY=true
```

Release tooling mirrors `enable-navigation-icons` (already published on WP.org under the `bmx269` account).

## Development Standards
- Follow WordPress Coding Standards (WPCS) for PHP.
- PHP 8.0+ minimum, WordPress 6.5+ minimum.
- `declare(strict_types=1);` and namespace `AffinityBridge\PatternDefaults` for all PHP.
- All user-facing strings must use `__()` / `esc_html__()` / `esc_attr__()` with text domain `ab-pattern-defaults`.
- Sanitize all input (`sanitize_*`), escape all output (`esc_*`); pass HTML through `wp_kses_post()` where appropriate.
- Capability checks (`current_user_can( 'manage_options' )`) on all admin actions.
- Prefer root-cause fixes over surface workarounds.

## Key Architecture
- Single option `ab_pattern_defaults` stores `[ post_type => pattern_slug ]` map.
- `default_content` filter resolves the saved slug to block markup at new-post time, only when the incoming content is empty.
- Resolution order: (1) published `wp_block` post by `post_name`, (2) full registered pattern name, (3) registered pattern slug suffix.
- Settings page lists every public post type (minus `attachment` and `wp_block`) with a live status badge per row.

## Versioning & Releases
- Version must be synced in: `ab-pattern-defaults.php` (plugin header), `readme.txt` (`Stable tag`), and `LICENSE` (year).
- `Tested up to` in `readme.txt` should match the latest WP release verified against.
- Update `Changelog` sections in both `README.md` and `readme.txt` for every release.
- `build.sh` is the only place the package is assembled. Never hand-list files elsewhere; change `.distignore` instead.

### One-time setup (after WordPress.org approval)
1. WordPress.org → profile → Account & Security: set an SVN password for `bmx269`.
2. GitHub repo → Settings → Secrets and variables → Actions: add secrets `SVN_USERNAME` (`bmx269`) and `SVN_PASSWORD`, and variable `WPORG_SVN_READY` = `true`.
3. First deploy: publish GitHub release `1.0.0` (or `v1.0.0`). The approval email's SVN repo is empty until then.

### Each release
1. Bump the version in the plugin header and `readme.txt` Stable tag; update changelogs, Upgrade Notice and `Tested up to`.
2. Regenerate the POT, run `./build.sh`, and test the zip.
3. Merge to `main`, then publish a GitHub release whose tag matches the version. The deploy workflow refuses a mismatched tag.

Readme-only or asset-only changes (e.g. bumping `Tested up to`) go live on push to `main` via `wporg-assets.yml`, provided the Stable tag is already released.

## Attribution
Plugin authored by Trent Stromkins (WordPress.org: `bmx269`) and maintained by [Affinity Bridge](https://affinitybridge.com). Licensed GPL-2.0-or-later.
