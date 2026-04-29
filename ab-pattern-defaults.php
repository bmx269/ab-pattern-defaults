<?php
/**
 * Plugin Name:       AB Pattern Defaults
 * Plugin URI:        https://affinitybridge.com/
 * Description:       Set a default block pattern for any post type's new-post editor. Database-stored patterns take priority over file-registered patterns.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Affinity Bridge
 * Author URI:        https://affinitybridge.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ab-pattern-defaults
 * Domain Path:       /languages
 * Update URI:        false
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

add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );
add_action( 'admin_menu', __NAMESPACE__ . '\\register_settings_page' );
add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
add_filter( 'default_content', __NAMESPACE__ . '\\filter_default_content', 10, 2 );

function load_textdomain(): void {
	load_plugin_textdomain( 'ab-pattern-defaults', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------

function register_settings_page(): void {
	add_options_page(
		__( 'AB Pattern Defaults', 'ab-pattern-defaults' ),
		__( 'AB Pattern Defaults', 'ab-pattern-defaults' ),
		'manage_options',
		'ab-pattern-defaults',
		__NAMESPACE__ . '\\render_settings_page'
	);
}

function register_settings(): void {
	register_setting(
		'ab_pattern_defaults_group',
		OPTION_KEY,
		[
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings',
			'default'           => [],
			'show_in_rest'      => false,
		]
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
		return [];
	}

	$allowed    = array_keys( get_manageable_post_types() );
	$sanitized  = [];

	foreach ( $input as $post_type => $slug ) {
		$post_type = sanitize_key( (string) $post_type );
		if ( '' === $post_type || ! in_array( $post_type, $allowed, true ) ) {
			continue;
		}

		// Allow empty slug (clears a prior setting).
		$sanitized[ $post_type ] = sanitize_text_field( trim( (string) $slug ) );
	}

	return $sanitized;
}

// ---------------------------------------------------------------------------
// Settings page render
// ---------------------------------------------------------------------------

function render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$post_types = get_manageable_post_types();
	$saved      = (array) get_option( OPTION_KEY, [] );
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'AB Pattern Defaults', 'ab-pattern-defaults' ); ?></h1>
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
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr>
						<th style="width:220px"><?php esc_html_e( 'Post Type', 'ab-pattern-defaults' ); ?></th>
						<th><?php esc_html_e( 'Pattern Slug', 'ab-pattern-defaults' ); ?></th>
						<th style="width:240px"><?php esc_html_e( 'Status', 'ab-pattern-defaults' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $post_types as $type_slug => $obj ) :
					$saved_slug = (string) ( $saved[ $type_slug ] ?? '' );
				?>
					<tr>
						<td>
							<strong><?php echo esc_html( $obj->labels->singular_name ); ?></strong><br>
							<code><?php echo esc_html( $type_slug ); ?></code>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( OPTION_KEY . '[' . $type_slug . ']' ); ?>"
								value="<?php echo esc_attr( $saved_slug ); ?>"
								placeholder="pattern-slug"
								class="regular-text"
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
	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'], $post_types['wp_block'] );
	return $post_types;
}

/**
 * Resolve a configured slug to its block-markup content.
 *
 * Resolution order:
 *   1. Database-stored pattern (wp_block post type), matched by post_name.
 *   2. File-registered pattern, matched by full registered name.
 *   3. File-registered pattern, matched by the slug portion of the name.
 */
function resolve_pattern( string $input ): ?string {
	if ( '' === $input ) {
		return null;
	}

	$db = get_page_by_path( $input, OBJECT, 'wp_block' );
	if ( $db instanceof WP_Post && '' !== $db->post_content ) {
		return $db->post_content;
	}

	$registry = WP_Block_Patterns_Registry::get_instance();

	$file = $registry->get_registered( $input );
	if ( is_array( $file ) && ! empty( $file['content'] ) ) {
		return (string) $file['content'];
	}

	foreach ( $registry->get_all_registered() as $pattern ) {
		$name  = (string) ( $pattern['name'] ?? '' );
		$parts = explode( '/', $name );
		if ( end( $parts ) === $input && ! empty( $pattern['content'] ) ) {
			return (string) $pattern['content'];
		}
	}

	return null;
}

/**
 * Build a status badge string for display in the settings table.
 *
 * Returns safe HTML; caller should still pass through wp_kses_post() when echoing.
 */
function render_pattern_status( string $slug ): string {
	if ( '' === $slug ) {
		return '<span style="color:#666">&mdash; ' . esc_html__( 'not set', 'ab-pattern-defaults' ) . '</span>';
	}

	$db = get_page_by_path( $slug, OBJECT, 'wp_block' );
	if ( $db instanceof WP_Post && '' !== $db->post_content ) {
		return '<span style="color:green">&#10003; ' . esc_html__( 'Found in database', 'ab-pattern-defaults' ) . '</span>';
	}

	$registry = WP_Block_Patterns_Registry::get_instance();
	if ( is_array( $registry->get_registered( $slug ) ) ) {
		return '<span style="color:green">&#10003; ' . esc_html__( 'Found in file registry', 'ab-pattern-defaults' ) . '</span>';
	}

	foreach ( $registry->get_all_registered() as $pattern ) {
		$parts = explode( '/', (string) ( $pattern['name'] ?? '' ) );
		if ( end( $parts ) === $slug ) {
			return '<span style="color:green">&#10003; ' . esc_html__( 'Found in file registry', 'ab-pattern-defaults' ) . '</span>';
		}
	}

	return '<span style="color:#b32d2e">&#10007; ' . esc_html__( 'Pattern not found', 'ab-pattern-defaults' ) . '</span>';
}

// ---------------------------------------------------------------------------
// Default content filter
// ---------------------------------------------------------------------------

function filter_default_content( string $content, WP_Post $post ): string {
	$saved = (array) get_option( OPTION_KEY, [] );
	$slug  = (string) ( $saved[ $post->post_type ] ?? '' );

	return resolve_pattern( $slug ) ?? $content;
}
