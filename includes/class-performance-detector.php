<?php
/**
 * Performance Detector Class
 * 
 * This class does the actual detection work. It hooks into admin_footer
 * to check performance metrics after the page has loaded.
 * 
 * We intentionally keep this simple - no fancy profiling, no continuous monitoring.
 * Just spot checks on admin page loads to help identify obvious red flags.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Performance_Checkup_Detector {
	
	/**
	 * Singleton instance
	 */
	private static $instance = null;
	
	/**
	 * Detection results
	 * Stored here so we can reference them in notices
	 */
	private $detection_results = array();
	
	/**
	 * Thresholds
	 * These are intentionally conservative. Better to miss some issues
	 * than to cry wolf and train admins to ignore notices.
	 * 
	 * TEMPORARILY LOWERED FOR TESTING - WILL RESTORE AFTER DEMO
	 */
	private $query_count_threshold = 10;  // Above this, we mention it (TESTING: normally 100)
	private $query_count_warning = 50;    // Above this, we're more concerned (TESTING: normally 200)
	private $slow_query_threshold = 0.1;  // 100ms - anything slower gets flagged
	private $memory_warning_mb = 5;       // If we're using more than this, worth noting (TESTING: normally 64)
	
	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		// Hook into admin_footer to run our checks
		// We do this late so all queries have been executed
		add_action( 'admin_footer', array( $this, 'run_checks' ), 999 );
		
		// Display admin notices based on what we found
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		
		// Handle notice dismissal
		add_action( 'admin_init', array( $this, 'handle_notice_dismissal' ) );
	}
	
	/**
	 * Run performance checks
	 * Called in admin_footer so we have the full picture
	 */
	public function run_checks() {
		// Only run on main admin pages, not on ajax requests
		if ( wp_doing_ajax() ) {
			return;
		}
		
		// Check if user has dismissed notices recently
		// We use a 24-hour transient to avoid nagging
		if ( get_transient( 'performance_checkup_notice_dismissed_' . get_current_user_id() ) ) {
			return;
		}
		
		// Reset results
		$this->detection_results = array();
		
		// Check query count
		$this->check_query_count();
		
		// Check for slow queries (only if SAVEQUERIES is enabled)
		$this->check_slow_queries();
		
		// Check memory usage
		$this->check_memory_usage();
	}
	
	/**
	 * Check database query count
	 */
	private function check_query_count() {
		global $wpdb;
		
		$num_queries = $wpdb->num_queries;
		
		// Only report if it's worth mentioning
		if ( $num_queries > $this->query_count_threshold ) {
			$severity = 'info';
			if ( $num_queries > $this->query_count_warning ) {
				$severity = 'warning';
			}
			
			$this->detection_results['query_count'] = array(
				'value'    => $num_queries,
				'severity' => $severity,
				'message'  => $this->get_query_count_message( $num_queries, $severity ),
			);
		}
	}
	
	/**
	 * Check for slow queries
	 * Only works if SAVEQUERIES is defined and true
	 */
	private function check_slow_queries() {
		global $wpdb;
		
		// SAVEQUERIES must be enabled for this to work
		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
			return;
		}
		
		if ( empty( $wpdb->queries ) ) {
			return;
		}
		
		$slow_queries = array();
		
		// Look through queries and find slow ones
		foreach ( $wpdb->queries as $query ) {
			// $query is an array: [0] => SQL, [1] => time, [2] => calling function
			$query_time = isset( $query[1] ) ? floatval( $query[1] ) : 0;
			
			if ( $query_time > $this->slow_query_threshold ) {
				$slow_queries[] = array(
					'time' => $query_time,
					'sql'  => isset( $query[0] ) ? $query[0] : '',
				);
			}
		}
		
		// Only report if we found slow queries
		if ( ! empty( $slow_queries ) ) {
			// Sort by time descending to show slowest first
			usort( $slow_queries, function( $a, $b ) {
				return $b['time'] <=> $a['time'];
			} );
			
			$this->detection_results['slow_queries'] = array(
				'value'    => count( $slow_queries ),
				'queries'  => array_slice( $slow_queries, 0, 3 ), // Only keep top 3
				'severity' => 'warning',
				'message'  => $this->get_slow_query_message( $slow_queries ),
			);
		}
	}
	
	/**
	 * Check memory usage
	 */
	private function check_memory_usage() {
		$current_memory_mb = memory_get_peak_usage( true ) / 1024 / 1024;
		
		// Only report if it's significant
		if ( $current_memory_mb > $this->memory_warning_mb ) {
			$this->detection_results['memory'] = array(
				'value'    => $current_memory_mb,
				'severity' => 'info',
				'message'  => $this->get_memory_message( $current_memory_mb ),
			);
		}
	}
	
	/**
	 * Generate message for query count
	 */
	private function get_query_count_message( $count, $severity ) {
		if ( 'warning' === $severity ) {
			return sprintf(
				'This admin page made <strong>%d database queries</strong>. That\'s quite high. Large numbers usually point to inefficient plugins or themes that make repeated queries in loops. It may not be urgent, but it\'s worth investigating.',
				$count
			);
		}
		
		return sprintf(
			'This admin page made <strong>%d database queries</strong>. This is noticeable but not necessarily a problem. If the admin feels slow, this might be why.',
			$count
		);
	}
	
	/**
	 * Generate message for slow queries
	 */
	private function get_slow_query_message( $slow_queries ) {
		$count = count( $slow_queries );
		$slowest_time = $slow_queries[0]['time'];
		
		return sprintf(
			'Found <strong>%d slow database %s</strong>. The slowest took <strong>%.3f seconds</strong>. Slow queries are often caused by missing indexes, large tables, or poorly written plugin code. Check the Performance Checkup page for details.',
			$count,
			$count === 1 ? 'query' : 'queries',
			$slowest_time
		);
	}
	
	/**
	 * Generate message for memory usage
	 */
	private function get_memory_message( $memory_mb ) {
		return sprintf(
			'This page used <strong>%.1f MB</strong> of memory. This isn\'t necessarily bad - WordPress needs memory to work. But if you\'re hitting memory limits, this reading can help identify which pages are the culprits.',
			$memory_mb
		);
	}
	
	/**
	 * Show admin notices based on detection results
	 */
	public function show_admin_notices() {
		// Don't show on ajax requests
		if ( wp_doing_ajax() ) {
			return;
		}
		
		// Only show to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// If we have no results, nothing to show
		if ( empty( $this->detection_results ) ) {
			return;
		}
		
		// We'll show one combined notice to avoid clutter
		$this->show_combined_notice();
	}
	
	/**
	 * Show a single combined notice
	 */
	private function show_combined_notice() {
		$messages = array();
		
		// Collect all messages
		foreach ( $this->detection_results as $key => $result ) {
			if ( isset( $result['message'] ) ) {
				$messages[] = $result['message'];
			}
		}
		
		if ( empty( $messages ) ) {
			return;
		}
		
		// Determine overall severity
		// If any are warnings, make the whole notice a warning
		$has_warning = false;
		foreach ( $this->detection_results as $result ) {
			if ( isset( $result['severity'] ) && 'warning' === $result['severity'] ) {
				$has_warning = true;
				break;
			}
		}
		
		$notice_class = $has_warning ? 'notice-warning' : 'notice-info';
		
		?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible performance-checkup-notice">
			<p><strong>Performance Checkup</strong></p>
			<?php foreach ( $messages as $message ) : ?>
				<p><?php echo wp_kses_post( $message ); ?></p>
			<?php endforeach; ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=performance-checkup' ) ); ?>">
					Learn more about these readings
				</a>
				| 
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'performance_checkup_dismiss', '1' ), 'performance_checkup_dismiss' ) ); ?>">
					Dismiss for 24 hours
				</a>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Handle notice dismissal
	 */
	public function handle_notice_dismissal() {
		if ( ! isset( $_GET['performance_checkup_dismiss'] ) ) {
			return;
		}
		
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'performance_checkup_dismiss' ) ) {
			return;
		}
		
		// Set a transient for 24 hours
		set_transient( 'performance_checkup_notice_dismissed_' . get_current_user_id(), true, DAY_IN_SECONDS );
		
		// Redirect to remove the query arg
		wp_safe_redirect( remove_query_arg( array( 'performance_checkup_dismiss', '_wpnonce' ) ) );
		exit;
	}
	
	/**
	 * Get detection results
	 * Used by the admin page to display detailed information
	 */
	public function get_results() {
		return $this->detection_results;
	}
}
