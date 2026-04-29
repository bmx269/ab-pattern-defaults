=== AB Pattern Defaults ===
Contributors:      affinitybridge
Tags:              block patterns, editor, default content, post types
Requires at least: 6.5
Tested up to:      6.9
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Set a default block pattern for any post type's new-post editor via a simple settings UI.

== Description ==

AB Pattern Defaults lets you assign a block pattern to any registered post type. When a new post of that type is created, the editor is pre-populated with the pattern's content — no more starting from a blank canvas.

**Key features:**

* Settings page under *Settings → AB Pattern Defaults* listing every public post type
* Enter a pattern slug per post type — no code changes required
* Searches both database-stored patterns (Saved Patterns / wp_block) and file-registered patterns
* Database patterns take priority over file-registered patterns
* Status indicator on the settings page confirms whether each slug resolves to a real pattern
* File patterns can be referenced by full registered name (e.g. `myplugin/my-pattern`) or by slug alone (e.g. `my-pattern`)

== Installation ==

1. Upload the `ab-pattern-defaults` folder to the `/wp-content/plugins/` directory, or install via *Plugins → Add New Plugin → Upload Plugin*.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Navigate to *Settings → AB Pattern Defaults*.
4. Enter the pattern slug for each post type you want to pre-populate and save.

== Frequently Asked Questions ==

= Where do I find the pattern slug? =

For a **database pattern** (Saved Pattern), open *Appearance → Editor → Patterns*, click the pattern, and note the URL slug — it is the value shown in the permalink field.

For a **file-registered pattern**, the slug is the second segment of the registered name (e.g. for `myplugin/my-pattern` the slug is `my-pattern`). You can also enter the full registered name.

= Which takes priority — database or file? =

Database-stored patterns (Saved Patterns) are checked first. File-registered patterns are used as a fallback.

= Does this work with custom post types? =

Yes. Every public post type registered on your site (excluding Media and Patterns themselves) appears in the settings table.

= What happens if the slug doesn't match any pattern? =

The editor opens with its default blank state, exactly as it would without the plugin.

== Changelog ==

= 1.0.0 =
* Initial release.
