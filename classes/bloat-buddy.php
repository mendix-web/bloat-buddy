<?php
/**
 * The core plugin class.
 *
 * @since      1.0.1
 * @package    Bloat_Buddy
 * @subpackage Bloat_Buddy/includes
 * @author     Matthew Szklarz <mszklarz@gmail.com>
 */
class Bloat_Buddy {

	const BYTE_LIMIT_SINGLE      = 800000; // Warning limit for single options.
	const BYTE_LIMIT_TOTAL       = 1800000; // Warning limit for total autoload wp_options.
	const FREQ_NOTICE_EMAIL_DAYS = 3; // Frequency at which notice emails should be sent.
	const SECONDS_PER_DAY        = 86400; // Amount of seconds in a day.

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
			'wp_options_bloat_buddy', // $widget_id: an identifying slug for your widget. This will be used as its CSS class and its key in the array of widgets.
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
				$bytes            = $results[0]->value;
				$bytes_over_limit = intval( $bytes ) > self::BYTE_LIMIT_TOTAL;
				$is_over_limit    = $bytes_over_limit > 0;

				printf(
					'<p style="color: %s">%s bytes (%s)</p>',
					$is_over_limit ? 'red' : 'inherit',
					$bytes,
					$this->format_bytes( $bytes ),
				);

				if ( $is_over_limit ) {
					$wpobb_email_dt = get_option( 'wpobb_email_dt' ) ?? 0;
					$diff_dt        = time() - $wpobb_email_dt;
					$diff_days      = $diff_dt / self::SECONDS_PER_DAY;

					if ( $diff_days > self::FREQ_NOTICE_EMAIL_DAYS ) {
						$this->send_email_notice( $bytes_over_limit );
					}
				}
			}
		}

		// Display 10 biggest autoload options.
		$results = $wpdb->get_results(
			"SELECT LENGTH(option_value),option_name FROM wp_options WHERE autoload='yes' ORDER BY length(option_value) DESC LIMIT 10;"
		);
		if ( is_array( $results ) && count( $results ) ) {
			echo '<hr style="margin-top: 2rem">';
			echo '<h3><b>Autoload Options Leaderboard</b></h3>';
			echo '<ul>';
			foreach ( $results as  $r ) {
				$r     = (array) $r;
				$bytes = $r['LENGTH(option_value)'];
				printf(
					'<li><b style="color: %s">%s</b> %s bytes (%s)',
					intval( $bytes ) > self::BYTE_LIMIT_SINGLE ? 'red' : 'inherit',
					esc_html( $r['option_name'] ),
					esc_html( $bytes ),
					esc_html( $this->format_bytes( $bytes ) ),
				);
			}
			echo '</ul>';
		}

		// TODO add email send button?
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
	 * Send an email to the specified user (or site admin) about the limit size.
	 *
	 * @param integer $bytes_over_limit Bytes over specified limit size
	 * @return void
	 */
	private function send_email_notice( int $bytes_over_limit ) {
		$to = get_option( 'admin_email' );
		if ( $to ) {
			update_option( 'wpobb_email_dt', time() );

			$site_name = get_bloginfo();
			$formatted = $this->format_bytes( $bytes_over_limit );

			$from    = 'bloat.buddy@mendix.com';
			$subject = "$site_name wp_options table is $formatted over the size limit";
			$body    = 'I’m Bloat Buddy and I’m here to say / your database needs cleanup to-day 🎤 🫳';

			$headers = implode(
				"\r\n",
				array(
					"From: $from",
					"Reply-To: $from",
					'X-Mailer: PHP/' . PHP_VERSION,
					'Content-Type: text/html; charset=UTF-8',
				)
			);
			wp_mail( $to, $subject, $body, $headers );
		}
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
