<?php
/**
 * Plugin Name:       AB Pattern Defaults
 * Plugin URI:        https://github.com/bmx269/ab-pattern-defaults
 * Description:       Set a default block pattern for any post type's new-post editor. Database-stored patterns take priority over file-registered patterns.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Trent Stromkins
 * Author URI:        https://affinitybridge.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       ab-pattern-defaults
 * Domain Path:       /languages
 *
 * @package AffinityBridge\PatternDefaults
 */

declare(strict_types=1);

namespace AffinityBridge\PatternDefaults;

use WP_Block_Patterns_Registry;
use WP_Post;
use WP_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPTION_KEY = 'ab_pattern_defaults';

add_action( 'admin_menu', __NAMESPACE__ . '\\register_settings_page' );
add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
add_filter( 'default_content', __NAMESPACE__ . '\\filter_default_content', 10, 2 );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __NAMESPACE__ . '\\add_settings_link' );

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------

/**
 * Add the settings page under Appearance.
 */
function register_settings_page(): void {
	add_theme_page(
		__( 'AB Pattern Defaults', 'ab-pattern-defaults' ),
		__( 'AB Pattern Defaults', 'ab-pattern-defaults' ),
		'manage_options',
		'ab-pattern-defaults',
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * Register the option with the Settings API.
 */
function register_settings(): void {
	register_setting(
		'ab_pattern_defaults_group',
		OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}

/**
 * Sanitize the settings array: keys to post-type slugs, values to plain text.
 *
 * @param mixed $input Raw option value from the Settings API.
 * @return array<string, string>
 */
function sanitize_settings( $input ): array {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$allowed   = array_keys( get_manageable_post_types() );
	$sanitized = array();

	foreach ( $input as $post_type => $slug ) {
		$post_type = sanitize_key( (string) $post_type );
		if ( '' === $post_type || ! in_array( $post_type, $allowed, true ) ) {
			continue;
		}

		if ( ! is_string( $slug ) ) {
			continue;
		}

		$slug = sanitize_text_field( $slug );

		// An empty slug clears the setting for that post type.
		if ( '' !== $slug ) {
			$sanitized[ $post_type ] = $slug;
		}
	}

	return $sanitized;
}

// ---------------------------------------------------------------------------
// Settings page render
// ---------------------------------------------------------------------------

/**
 * Render the settings page.
 */
function render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$post_types = get_manageable_post_types();
	$saved      = get_saved_slugs();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'AB Pattern Defaults', 'ab-pattern-defaults' ); ?></h1>
		<?php
		// Pages outside the Settings menu don't print save notices automatically.
		settings_errors();
		?>
		<p>
			<?php esc_html_e( "Enter a pattern slug for each post type. When a new post is created the editor will be pre-populated with that pattern's content.", 'ab-pattern-defaults' ); ?>
			<strong><?php esc_html_e( 'Database patterns (saved patterns) take priority over file-registered patterns.', 'ab-pattern-defaults' ); ?></strong>
		</p>
		<p>
			<?php
			printf(
				/* translators: 1: example database pattern slug, 2: example file-registered pattern name. */
				esc_html__( 'Enter the post slug for a database pattern (e.g. %1$s), or the full registered name for a file pattern (e.g. %2$s).', 'ab-pattern-defaults' ),
				'<code>branch-default</code>',
				'<code>myplugin/branch-default</code>'
			);
			?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'ab_pattern_defaults_group' ); ?>
			<?php render_pattern_datalist(); ?>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr>
						<th style="width:220px"><?php esc_html_e( 'Post Type', 'ab-pattern-defaults' ); ?></th>
						<th><?php esc_html_e( 'Pattern Slug', 'ab-pattern-defaults' ); ?></th>
						<th style="width:240px"><?php esc_html_e( 'Status', 'ab-pattern-defaults' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $post_types as $type_slug => $obj ) :
					$saved_slug = $saved[ $type_slug ] ?? '';
					$field_id   = 'ab-pattern-defaults-' . $type_slug;
					$type_label = is_string( $obj->labels->singular_name ?? null ) ? $obj->labels->singular_name : $obj->label;
					?>
					<tr>
						<td>
							<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $type_label ); ?></strong></label><br>
							<code><?php echo esc_html( $type_slug ); ?></code>
						</td>
						<td>
							<input
								type="text"
								id="<?php echo esc_attr( $field_id ); ?>"
								name="<?php echo esc_attr( OPTION_KEY . '[' . $type_slug . ']' ); ?>"
								value="<?php echo esc_attr( $saved_slug ); ?>"
								placeholder="pattern-slug"
								class="regular-text"
								list="ab-pattern-defaults-patterns"
							>
						</td>
						<td><?php echo wp_kses_post( render_pattern_status( $saved_slug ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * All public post types the plugin can target.
 *
 * @return array<string, WP_Post_Type>
 */
function get_manageable_post_types(): array {
	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	unset( $post_types['attachment'], $post_types['wp_block'] );
	return $post_types;
}

/**
 * The saved post type => pattern slug map, ignoring malformed entries.
 *
 * @return array<string, string>
 */
function get_saved_slugs(): array {
	$saved = get_option( OPTION_KEY, array() );
	if ( ! is_array( $saved ) ) {
		return array();
	}

	return array_filter(
		$saved,
		static fn( $slug, $post_type ): bool => is_string( $post_type ) && is_string( $slug ) && '' !== $slug,
		ARRAY_FILTER_USE_BOTH
	);
}

/**
 * Read a string field from a registered pattern array.
 *
 * @param array<mixed> $pattern Registered pattern properties.
 * @param string       $key     Field name, e.g. 'name', 'title' or 'content'.
 * @return string The field value, or an empty string when missing or not a string.
 */
function pattern_field( array $pattern, string $key ): string {
	return isset( $pattern[ $key ] ) && is_string( $pattern[ $key ] ) ? $pattern[ $key ] : '';
}

/**
 * Locate the pattern a configured slug refers to.
 *
 * Resolution order:
 *   1. Published database pattern (wp_block post type), matched by post_name.
 *   2. File-registered pattern, matched by full registered name.
 *   3. File-registered pattern, matched by the slug portion of the name.
 *
 * @param string $slug Configured pattern slug or registered name.
 * @return array{source: string, content: string}|null Source is 'database' or 'file'.
 */
function locate_pattern( string $slug ): ?array {
	if ( '' === $slug ) {
		return null;
	}

	$db = get_page_by_path( $slug, OBJECT, 'wp_block' );
	if ( $db instanceof WP_Post && 'publish' === $db->post_status && '' !== $db->post_content ) {
		return array(
			'source'  => 'database',
			'content' => $db->post_content,
		);
	}

	$registry = WP_Block_Patterns_Registry::get_instance();

	$file = $registry->get_registered( $slug );
	if ( is_array( $file ) && '' !== pattern_field( $file, 'content' ) ) {
		return array(
			'source'  => 'file',
			'content' => pattern_field( $file, 'content' ),
		);
	}

	foreach ( $registry->get_all_registered() as $pattern ) {
		$parts = explode( '/', pattern_field( $pattern, 'name' ) );
		if ( end( $parts ) === $slug && '' !== pattern_field( $pattern, 'content' ) ) {
			return array(
				'source'  => 'file',
				'content' => pattern_field( $pattern, 'content' ),
			);
		}
	}

	return null;
}

/**
 * Build a status badge string for display in the settings table.
 *
 * Returns safe HTML; caller should still pass through wp_kses_post() when echoing.
 *
 * @param string $slug Configured pattern slug or registered name.
 * @return string
 */
function render_pattern_status( string $slug ): string {
	if ( '' === $slug ) {
		return '<span style="color:#646970">&mdash; ' . esc_html__( 'not set', 'ab-pattern-defaults' ) . '</span>';
	}

	$pattern = locate_pattern( $slug );

	if ( null === $pattern ) {
		return '<span style="color:#b32d2e">&#10007; ' . esc_html__( 'Pattern not found', 'ab-pattern-defaults' ) . '</span>';
	}

	$label = 'database' === $pattern['source']
		? __( 'Found in database', 'ab-pattern-defaults' )
		: __( 'Found in file registry', 'ab-pattern-defaults' );

	return '<span style="color:#008a20">&#10003; ' . esc_html( $label ) . '</span>';
}

/**
 * Output a datalist of available pattern slugs to suggest in the slug inputs.
 */
function render_pattern_datalist(): void {
	$options = array();

	$db_patterns = get_posts(
		array(
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $db_patterns as $db_pattern ) {
		$options[ $db_pattern->post_name ] = $db_pattern->post_title;
	}

	foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
		$name = pattern_field( $pattern, 'name' );
		if ( '' !== $name && ! isset( $options[ $name ] ) ) {
			$title            = pattern_field( $pattern, 'title' );
			$options[ $name ] = '' !== $title ? $title : $name;
		}
	}

	echo '<datalist id="ab-pattern-defaults-patterns">';
	foreach ( $options as $value => $label ) {
		printf( '<option value="%1$s" label="%2$s"></option>', esc_attr( (string) $value ), esc_attr( $label ) );
	}
	echo '</datalist>';
}

/**
 * Add a Settings link to the plugin's row on the Plugins screen.
 *
 * @param array<string|int, string> $links Existing action links.
 * @return array<string|int, string>
 */
function add_settings_link( array $links ): array {
	$settings = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( admin_url( 'themes.php?page=ab-pattern-defaults' ) ),
		esc_html__( 'Settings', 'ab-pattern-defaults' )
	);
	array_unshift( $links, $settings );
	return $links;
}

// ---------------------------------------------------------------------------
// Default content filter
// ---------------------------------------------------------------------------

/**
 * Pre-populate a new post with the pattern configured for its post type.
 *
 * Content already supplied (e.g. via the `content` query arg or an earlier
 * filter) is left untouched.
 *
 * @param mixed   $content Default post content.
 * @param WP_Post $post    The auto-draft being created.
 * @return mixed
 */
function filter_default_content( $content, $post ) {
	if ( ! $post instanceof WP_Post || ( is_string( $content ) && '' !== trim( $content ) ) ) {
		return $content;
	}

	$pattern = locate_pattern( get_saved_slugs()[ $post->post_type ] ?? '' );

	return null === $pattern ? $content : $pattern['content'];
}
