<?php
/**
 * @link 			https://github.com/jwcounts/hpm-mods
 * @since  			2019.2.6
 * @package  		HPM-Podcasts
 *
 * @wordpress-plugin
 * Plugin Name: 	HPM Mods
 * Plugin URI: 		https://github.com/jwcounts/hpm-mods
 * Description: 	Previously HPM Podcasts. Pulling together several must-use plugins into one place, HPM Mods contains 1.) podcast feed creation and caching, 2.) show page support, 3.) staff directory support, 4.) priority posts for the homepage, 5.) internal display ad support, 6.) news series support for pages, and 7.) other miscellaneous support functions.
 * Version: 		2021.10.1
 * Author: 			Jared Counts
 * Author URI: 		https://www.houstonpublicmedia.org/staff/jared-counts/
 * License: 		GPL-2.0+
 * License URI: 	http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: 	hpm-mods
 *
 * Works best with Wordpress 4.6.0+
 */

/**
 * Required plugins and setup
 */

define( 'HPM_MODS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HPM_MODS_URL', plugin_dir_url( __FILE__ ) );

require( HPM_MODS_DIR . 'extras/hpm_extras.php' );
require( HPM_MODS_DIR . 'podcasts/main.php' );
require( HPM_MODS_DIR . 'priority/hpm_priority.php' );
require( HPM_MODS_DIR . 'promos/hpm_promos.php' );
require( HPM_MODS_DIR . 'series/hpm_series.php' );
require( HPM_MODS_DIR . 'staff/hpm_staff.php' );
require( HPM_MODS_DIR . 'embeds/hpm_embeds.php' );

register_activation_hook( __FILE__, 'hpm_mods_activate' );
register_deactivation_hook( __FILE__, 'hpm_mods_deactivate' );

function hpm_mods_activate() {
	$pods = [
		'owner' => [
			'name' => '',
			'email' => ''
		],
		'recurrence' => 'hourly',
		'roles' => ['editor','administrator'],
		'upload-media' => 'sftp',
		'upload-flats' => 'database',
		'credentials' => [
			'sftp' => [
				'host' => '',
				'url' => '',
				'username' => '',
				'password' => '',
				'folder' => ''
			]
		],
		'https' => ''
	];
	$old = get_option( 'hpm_podcast_settings' );
	if ( empty( $old ) ) :
		update_option( 'hpm_podcast_settings', $pods, false );
	endif;
	update_option( 'hpm_podcast_last_update', 'none', false );
	HPM_Podcasts::create_type();
	flush_rewrite_rules();
	if ( ! wp_next_scheduled( 'hpm_podcast_update_refresh' ) ) :
		wp_schedule_event( time(), 'hourly', 'hpm_podcast_update_refresh' );
	endif;
}

function hpm_mods_deactivate() {
	wp_clear_scheduled_hook( 'hpm_podcast_update_refresh' );
	delete_option( 'hpm_podcast_settings' );
	delete_option( 'hpm_podcast_last_update' );
	flush_rewrite_rules();
}