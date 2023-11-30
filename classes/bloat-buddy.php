<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Bloat_Buddy
 * @subpackage Bloat_Buddy/includes
 * @author     Matthew Szklarz <mszklarz@gmail.com>
 */
class Bloat_Buddy {
	/**
	 * Undocumented variable
	 *
	 * @var integer
	 */
	private int $mb_limit_autoload_single = 800000;

	/**
	 * The warning size for the wp_options table
	 *
	 * @var integer
	 */
	private int $mb_limit_autoload_total = 1800000;

	/**
	 * Undocumented function
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'on_wp_dashboard_setup' ) );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function on_wp_dashboard_setup() {
		wp_add_dashboard_widget(
			'wp_options_bloat', // $widget_id: an identifying slug for your widget. This will be used as its CSS class and its key in the array of widgets.
			'Bloat Buddy', // $widget_name: this is the name your widget will display in its heading.
			array( $this, 'buddy_output_widget' ), // $callback: The name of a function you will create that will display the actual contents of your widget.
			array( $this, 'buddy_handle_widget' ), // $control_callback (Optional): The name of a function you create that will handle submission of widget options forms, and will also display the form elements.
			array(), // $callback_args (Optional): Set of arguments for the callback function.
		);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function buddy_output_widget() {
		global $wpdb;

		// Display total autoload table results.
		$results = $wpdb->get_results(
			"SELECT 'autoloaded data in KiB' as name, ROUND(SUM(LENGTH(option_value))) as value FROM wp_options WHERE autoload='yes' UNION SELECT 'autoloaded data count', count(*) FROM wp_options WHERE autoload='yes';"
		);
		if ( $results ) {
			echo '<h3><b>Total Autoload Options Size</b></h3>';
			if ( $results ) {
				$bytes = $results[0]->value;
				printf(
					'<p style="color: %s">%s bytes (%s)</p>',
					intval( $bytes ) > $this->mb_limit_autoload_total ? 'red' : 'inherit',
					$bytes,
					$this->format_bytes( $bytes ),
				);
			}
		}

		// Display  10 biggest autoload options.
		$results = $wpdb->get_results(
			"SELECT LENGTH(option_value),option_name FROM wp_options WHERE autoload='yes' ORDER BY length(option_value) DESC LIMIT 10;"
		);
		if ( $results ) {
			if ( is_array( $results ) ) {
				echo '<hr style="margin-top: 2rem">';
				echo '<h3><b>Autoload Options Leaderboard</b></h3>';
				echo '<ul>';
				foreach ( $results as  $r ) {
					$r     = (array) $r;
					$bytes = $r['LENGTH(option_value)'];
					printf(
						'<li><b style="color: %s">%s</b> %s bytes (%s)',
						intval( $bytes ) > $this->mb_limit_autoload_single ? 'red' : 'inherit',
						esc_html( $r['option_name'] ),
						esc_html( $bytes ),
						esc_html( $this->format_bytes( $bytes ) ),
					);
				}
				echo '</ul>';
			}
		}

		$user_meta = get_userdata( get_current_user_id() );
		if ( in_array( 'administrator', $user_meta->roles, true ) ) {
			// Output buttons to perform admin actions here.
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function buddy_handle_widget() {
		// Handle admin actions here.
	}

	/**
	 * Format bytes value into readable b, kb, mb, etc.
	 *
	 * @param integer $bytes Total amount of bytes to convert.
	 * @param integer $precision Rounding precision.
	 * @return string
	 */
	private function format_bytes( int $bytes, int $precision = 2 ): string {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes  = max( $bytes, 0 );
		$pow    = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow    = min( $pow, count( $units ) - 1 );
		$bytes /= ( 1 << ( 10 * $pow ) );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}
}
