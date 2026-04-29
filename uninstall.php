<?php
/**
 * Uninstall routine for AB Pattern Defaults.
 *
 * Runs when the user deletes the plugin from the Plugins screen.
 *
 * @package AffinityBridge\PatternDefaults
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

const AB_PD_UNINSTALL_OPTION = 'ab_pattern_defaults';

if ( is_multisite() ) {
	$site_ids = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		delete_option( AB_PD_UNINSTALL_OPTION );
		restore_current_blog();
	}

	delete_site_option( AB_PD_UNINSTALL_OPTION );
} else {
	delete_option( AB_PD_UNINSTALL_OPTION );
}
