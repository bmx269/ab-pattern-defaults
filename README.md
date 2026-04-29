# AB Pattern Defaults

Set a default block pattern for any post type's new-post editor. Database-stored patterns take priority over file-registered patterns.

- **Requires:** WordPress 6.5+, PHP 8.0+
- **License:** GPL-2.0-or-later
- **Author:** [Affinity Bridge](https://affinitybridge.com)

## Overview

AB Pattern Defaults adds a settings page where you can assign a block pattern to each registered post type. When a new post of that type is created, the editor is pre-populated with the pattern's content — no more blank canvas.

## Features

- Settings page under **Settings → AB Pattern Defaults**
- Lists every public post type on the site
- Accepts either a database pattern slug (Saved Pattern) or a file-registered pattern name
- Database patterns take priority over file-registered patterns
- Live status indicator confirms whether each configured slug resolves to a real pattern
- Clean uninstall — removes its option when the plugin is deleted

## Installation

1. Copy the `ab-pattern-defaults` folder into `wp-content/plugins/`, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin from the **Plugins** screen.
3. Visit **Settings → AB Pattern Defaults** and enter the pattern slug for each post type you want to pre-populate.

## Usage

For a **database pattern** (Saved Pattern), use the post slug — for example `branch-default`.

For a **file-registered pattern**, use either:

- the full registered name, e.g. `myplugin/branch-default`, or
- the slug portion alone, e.g. `branch-default`

If no pattern matches the configured slug, the editor opens with its default blank state.

## How it works

The plugin hooks `default_content` and looks up the pattern content in this order:

1. Database-stored pattern (`wp_block` post type) matched by `post_name`
2. File-registered pattern matched by full registered name
3. File-registered pattern matched by the slug portion of the name

## Development

Main plugin file: [`ab-pattern-defaults.php`](ab-pattern-defaults.php)

Option key: `ab_pattern_defaults` (associative array, post_type => pattern slug)

## Changelog

### 1.0.0
- Initial release.
