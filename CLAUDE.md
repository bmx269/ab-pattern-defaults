# AB Pattern Defaults - Development Guide

## Project Overview
WordPress plugin that pre-populates the block editor with a configurable block pattern per post type. Pure PHP (no JS build step). Database-stored patterns take priority over file-registered patterns.

## Repository Structure
```
├── ab-pattern-defaults.php   # Main plugin file (settings UI + default_content filter)
├── uninstall.php             # Removes plugin option on delete
├── readme.txt                # WordPress.org plugin readme
├── README.md                 # GitHub-facing documentation
├── languages/                # Translation files (POT + .po/.mo)
├── .wordpress-org/           # WP.org assets (banners, icons, screenshots) - if/when added
└── .github/workflows/        # CI / deployment automation - if/when added
```

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
- `default_content` filter resolves the saved slug to block markup at new-post time.
- Resolution order: (1) `wp_block` post by `post_name`, (2) full registered pattern name, (3) registered pattern slug suffix.
- Settings page lists every public post type (minus `attachment` and `wp_block`) with a live status badge per row.

## Versioning & Releases
- Version must be synced in: `ab-pattern-defaults.php` (plugin header), `readme.txt` (`Stable tag`), and `LICENSE` (year).
- `Tested up to` in `readme.txt` should match the latest WP release verified against.
- Update `Changelog` sections in both `README.md` and `readme.txt` for every release.

## Attribution
Plugin authored and maintained by [Affinity Bridge](https://affinitybridge.com). Licensed GPL-2.0-or-later.
