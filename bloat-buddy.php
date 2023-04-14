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
	//Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules(); 
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bloat-buddy-deactivator.php
 */
function deactivate_bloat_buddy() {
	//Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules(); 
}

register_activation_hook( __FILE__, 'activate_bloat_buddy' );
register_deactivation_hook( __FILE__, 'deactivate_bloat_buddy' );

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Bloat_Buddy
 * @subpackage Bloat_Buddy/includes
 * @author     Matthew Szklarz <mszklarz@gmail.com>
 */
class Bloat_Buddy {
	private int $mb_limit_1 = 800000;
	private int $mb_limit_2 = 1800000;

	/**
	 * 
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', [ $this, 'on_wp_dashboard_setup' ] );
	}

	/**
	 * 
	 */
	public function on_wp_dashboard_setup () {
		wp_add_dashboard_widget( 
			'wp_options_bloat',//$widget_id: an identifying slug for your widget. This will be used as its CSS class and its key in the array of widgets.
			'Bloat Buddy',//$widget_name: this is the name your widget will display in its heading.
			 [ $this, 'buddy_output_widget' ],//$callback: The name of a function you will create that will display the actual contents of your widget.
			 [ $this, 'buddy_handle_widget' ],//$control_callback (Optional): The name of a function you create that will handle submission of widget options forms, and will also display the form elements.
			array(),//$callback_args (Optional): Set of arguments for the callback function.
		);
	}

	/**
	 * 
	 */
	public function buddy_output_widget() {
		global $wpdb;

		if( $results = $wpdb->get_results(
			"SELECT 'autoloaded data in KiB' as name, ROUND(SUM(LENGTH(option_value))) as value FROM wp_options WHERE autoload='yes' UNION SELECT 'autoloaded data count', count(*) FROM wp_options WHERE autoload='yes';"
		) ) {
			echo '<h3><b>Total Autoload Options Size</b></h3>';
			if( $results ) {
				$bytes = $results[0]->value;
				printf( 
					'<p style="color: %s">%s bytes (%s)</p>',
					intval( $bytes ) > $this->mb_limit_2 ? 'red' : 'inherit',
					$bytes,
					$this->formatBytes( $bytes ),				
				);
			}	
		}

		if( $results = $wpdb->get_results(
			"SELECT LENGTH(option_value),option_name FROM wp_options WHERE autoload='yes' ORDER BY length(option_value) DESC LIMIT 10;"
		) ) {
			if( is_array( $results ) )
			{
				echo '<hr style="margin-top: 2rem">';
				echo '<h3><b>Autoload Options Leaderboard</b></h3>';
				echo '<ul>';
				foreach( $results as  $r )
				{
					$r = (array) $r;
					$bytes = $r[ 'LENGTH(option_value)'];
					printf( 
						'<li><b style="color: %s">%s</b> %s bytes (%s)',
						intval( $bytes ) > $this->mb_limit_1 ? 'red' : 'inherit',
						$r[ 'option_name'], 
						$bytes,
						$this->formatBytes( $bytes ),				
					);
				}
				echo '</ul>';
			}
		}
		$user_meta = get_userdata( get_current_user_id() );
		if( in_array( 'administrator', $user_meta->roles ) )
		{
			//output buttons to perform admin actions here
		}
	}

	/**
	 * 
	 */
	public function buddy_handle_widget() {
		//handle admin actions here
	}

	/**
	 * 
	 */
	private function formatBytes($bytes, $precision = 2) { 
		$UNITS = array( 'B', 'KB', 'MB', 'GB', 'TB' ); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($UNITS) - 1); 
		$bytes /= (1 << (10 * $pow)); 

		return round($bytes, $precision) . ' ' . $UNITS[$pow];
	} 
}

new Bloat_Buddy();
