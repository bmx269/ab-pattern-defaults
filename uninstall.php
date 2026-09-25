<?php
/**
 * Uninstall routine for Pattern Primer.
 *
 * Runs when the user deletes the plugin from the Plugins screen.
 *
 * @package PatternPrimer
 */

declare(strict_types=1);

namespace PatternPrimer;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete the plugin option from every site.
 */
function uninstall(): void {
	$option = 'pattern_primer';

	if ( ! is_multisite() ) {
		delete_option( $option );
		return;
	}

	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		delete_option( $option );
		restore_current_blog();
	}
}

uninstall();
