<?php
/**
 * Adds a dashboard widget to monitor large wp_option values
 *
 * @link              https://www.mendix.com
 * @since             1.0.0
 * @package           Bloat_Buddy
 *
 * @wordpress-plugin
 * Plugin Name:       Bloat Buddy
 *
 * Plugin URI:        https://https://github.com/mendix-web/bloat-buddy
 * Description:       Adds a dashboard widget to monitor large wp_option values
 * Version:           1.0.0
 * Author:            Matthew Szklarz
 * Author URI:        https://www.mendix.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bloat-buddy
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BLOAT_BUDDY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bloat-buddy-activator.php
 */
function activate_bloat_buddy() {
	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bloat-buddy-deactivator.php
 */
function deactivate_bloat_buddy() {
	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'activate_bloat_buddy' );
register_deactivation_hook( __FILE__, 'deactivate_bloat_buddy' );

require_once plugin_dir_path( __FILE__ ) . 'classes/bloat-buddy.php';
new Bloat_Buddy();
