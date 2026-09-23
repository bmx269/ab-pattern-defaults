# AB Pattern Defaults

Set a default block pattern for any post type's new-post editor. Database-stored patterns take priority over file-registered patterns.

**[Try it in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/bmx269/ab-pattern-defaults/main/blueprint.json)** to test the plugin in your browser, with nothing to install. The demo sets a sample pattern as the default for Posts, so *Posts → Add New* opens with it.

- **Requires:** WordPress 6.5+, PHP 8.0+
- **License:** GPLv2 or later
- **Author:** Trent Stromkins
- **Maintained by:** [Affinity Bridge](https://affinitybridge.com)

## Overview

AB Pattern Defaults adds a settings page where you can assign a block pattern to each registered post type. When a new post of that type is created, the editor opens with the pattern's blocks already in place.

## Features

- Settings page under **Appearance → AB Pattern Defaults**
- Lists every public post type on the site
- Accepts either a database pattern slug (Saved Pattern) or a file-registered pattern name
- Database patterns take priority over file-registered patterns
- Live status indicator confirms whether each configured slug resolves to a real pattern
- Slug fields suggest the patterns available on the site as you type
- Removes its option when the plugin is deleted, on every site in a multisite network

## Installation

1. Copy the `ab-pattern-defaults` folder into `wp-content/plugins/`, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin from the **Plugins** screen.
3. Visit **Appearance → AB Pattern Defaults** and enter the pattern slug for each post type you want to pre-populate.

## Usage

For a **database pattern** (Saved Pattern), use the post slug, for example `branch-default`.

For a **file-registered pattern**, use either:

- the full registered name, e.g. `myplugin/branch-default`, or
- the slug portion alone, e.g. `branch-default`

If no pattern matches the configured slug, the editor opens with its default blank state.

## How it works

The plugin hooks `default_content` and looks up the pattern content in this order:

1. Published database pattern (`wp_block` post type) matched by `post_name`
2. File-registered pattern matched by full registered name
3. File-registered pattern matched by the slug portion of the name

The pattern is only applied when the new post's content is empty, so content passed in another way (for example the `content` query arg) is kept. Core then hands the markup to the block editor as unsaved initial edits.

Because the new post isn't empty, core's "Choose a pattern" starter-pattern modal doesn't open for post types that have a default. Post types without one keep core's behaviour.

## Development

Main plugin file: [`ab-pattern-defaults.php`](ab-pattern-defaults.php)

Option key: `ab_pattern_defaults` (associative array, post_type => pattern slug)

Try it locally with WordPress Playground:

```bash
npx @wp-playground/cli@latest server --auto-mount
```

Build the distributable package (everything not listed in `.distignore`):

```bash
./build.sh   # build/ab-pattern-defaults/ and build/ab-pattern-defaults.zip
```

CI builds the package and runs [Plugin Check](https://wordpress.org/plugins/plugin-check/) against it on every push and pull request to `main`.

## Releasing

Publishing a GitHub release deploys to WordPress.org. The release tag (`1.2.0` or `v1.2.0`) must match the plugin header Version and the readme Stable tag, or the workflow stops. The built zip is attached to the release.

Changes to `readme.txt` or `.wordpress-org/` alone are pushed to WordPress.org on merge to `main`, once the `WPORG_SVN_READY` repository variable is `true`.

Deploys need the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets. `./deploy.sh <svn-checkout>` is a manual fallback.

## Support & Contribute

- **Support:** [WordPress.org support forum](https://wordpress.org/support/plugin/ab-pattern-defaults/)
- **Bugs and feature requests:** [GitHub issues](https://github.com/bmx269/ab-pattern-defaults/issues)
- **Contribute:** pull requests are welcome against `main`.

## Changelog

### 1.0.0
- Initial release.
