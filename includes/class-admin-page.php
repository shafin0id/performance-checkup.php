<?php
/**
 * Admin Page Class
 * 
 * Creates a simple admin page under Tools that explains what we're detecting
 * and why it matters. This is educational, not a dashboard.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Performance_Checkup_Admin_Page {
	
	/**
	 * Singleton instance
	 */
	private static $instance = null;
	
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}
	
	/**
	 * Add admin menu item as a top-level menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Performance Checkup',           // Page title
			'Performance Checkup',           // Menu title
			'manage_options',                // Capability
			'performance-checkup',           // Menu slug
			array( $this, 'render_page' ),  // Callback
			'dashicons-superhero',           // Icon
			80                               // Position (after Settings)
		);
	}
	
	/**
	 * Render the admin page
	 */
	public function render_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'performance-checkup' ) );
		}
		
		?>
		<div class="wrap">
			<h1>Performance Checkup</h1>
			
			<div class="card">
				<h2>What This Plugin Does</h2>
				<p>
					Performance Checkup is a simple diagnostic tool designed to help you spot common performance red flags 
					in WordPress admin pages. It's <strong>not</strong> a full profiling tool or performance suite.
				</p>
				<p>
					It checks three things on admin page loads:
				</p>
				<ul>
					<li><strong>Database query count</strong> - How many queries were made to load the page</li>
					<li><strong>Slow queries</strong> - Individual queries that took longer than 100ms (only when SAVEQUERIES is enabled)</li>
					<li><strong>Memory usage</strong> - How much memory the page consumed</li>
				</ul>
			</div>
			
			<div class="card">
				<h2>Understanding Query Counts</h2>
				<p>
					<strong>What's normal?</strong> A typical admin page might make 20-50 queries. Simple pages might do fewer. 
					Complex pages (like WooCommerce product editors) might legitimately make more.
				</p>
				<p>
					<strong>When to worry:</strong> If you're seeing 200+ queries on simple pages, it usually means a plugin 
					or theme is making inefficient database calls - often running queries inside loops instead of batching them.
				</p>
				<p>
					<strong>When it's fine:</strong> Some complex admin pages just need lots of data. A high query count isn't 
					automatically bad. It's a starting point for investigation, not a verdict.
				</p>
			</div>
			
			<div class="card">
				<h2>Understanding Slow Queries</h2>
				<p>
					<strong>What we're checking:</strong> Individual queries that take more than 100ms (0.1 seconds).
				</p>
				<p>
					<strong>Why it matters:</strong> One slow query can make an entire page feel sluggish. Slow queries are 
					often caused by:
				</p>
				<ul>
					<li>Missing database indexes</li>
					<li>Large tables without proper optimization</li>
					<li>Complex JOIN operations on big datasets</li>
					<li>Poorly written plugin or theme code</li>
				</ul>
				<p>
					<strong>Important:</strong> This only works if you have <code>define('SAVEQUERIES', true);</code> in your 
					wp-config.php file. We don't enable it automatically because it adds overhead you don't want in production.
				</p>
			</div>
			
			<div class="card">
				<h2>Understanding Memory Usage</h2>
				<p>
					<strong>What's normal?</strong> WordPress itself uses 20-40 MB typically. With plugins and themes, 60-80 MB 
					is common. Some complex operations might use more.
				</p>
				<p>
					<strong>When to worry:</strong> If you're getting "memory exhausted" errors, this reading helps you identify 
					which specific admin pages are the memory hogs.
				</p>
				<p>
					<strong>Context matters:</strong> High memory usage isn't inherently bad - WordPress needs memory to work. 
					It's only a problem if you're hitting your PHP memory limit.
				</p>
			</div>
			
			<div class="card">
				<h2>Current Status</h2>
				<?php $this->render_current_status(); ?>
			</div>
			
			<div class="card">
				<h2>What This Plugin Doesn't Do</h2>
				<ul>
					<li>It doesn't monitor your frontend - admin only</li>
					<li>It doesn't collect data over time or create logs</li>
					<li>It doesn't automatically fix anything</li>
					<li>It doesn't add overhead to your site (it only runs after admin pages load)</li>
					<li>It doesn't work like Query Monitor or other advanced profilers</li>
				</ul>
				<p>
					<strong>Think of it as:</strong> A simple set of blood pressure checks for your admin. It won't diagnose 
					every problem, but it might catch obvious issues before they become emergencies.
				</p>
			</div>
			
			<div class="card">
				<h2>When to Ignore the Warnings</h2>
				<p>Not every warning requires action. You can safely ignore notices if:</p>
				<ul>
					<li>The admin feels fast enough for your needs</li>
					<li>You're on a page that legitimately needs to load lots of data</li>
					<li>You're on a development site where performance isn't critical</li>
					<li>You've already investigated and know the cause is acceptable</li>
				</ul>
				<p>
					The goal isn't zero queries or zero memory usage. The goal is awareness. 
					If something changes dramatically, you'll notice.
				</p>
			</div>
			
			<div class="card">
				<h2>Troubleshooting Common Issues</h2>
				
				<h3>High Query Counts</h3>
				<p><strong>Common culprits:</strong></p>
				<ul>
					<li>Page builder plugins</li>
					<li>Analytics or statistics plugins checking data in admin</li>
					<li>Themes that make custom queries for every menu item or widget</li>
					<li>Plugins using <code>get_posts()</code> or <code>WP_Query</code> inside loops</li>
				</ul>
				<p><strong>How to investigate:</strong> Enable Query Monitor plugin temporarily - it shows exactly which plugins are responsible for queries.</p>
				
				<h3>Slow Queries</h3>
				<p><strong>Common causes:</strong></p>
				<ul>
					<li>Large postmeta or usermeta tables without indexes</li>
					<li>Custom queries on taxonomy terms with lots of relationships</li>
					<li>Search queries on large datasets</li>
				</ul>
				<p><strong>How to investigate:</strong> Check the query details shown in the notice. Look for table names to identify which plugin's data is involved.</p>
				
				<h3>High Memory</h3>
				<p><strong>Common causes:</strong></p>
				<ul>
					<li>Image processing operations</li>
					<li>Loading large datasets into memory at once</li>
					<li>Memory leaks in poorly coded plugins</li>
				</ul>
				<p><strong>How to investigate:</strong> Note which admin pages trigger high memory. Disable plugins one by one on those pages to isolate the cause.</p>
			</div>
			
			<div class="card">
				<h2>SAVEQUERIES Explained</h2>
				<p>
					To enable slow query detection, add this line to your <code>wp-config.php</code> file 
					(before the "That's all, stop editing" line):
				</p>
				<pre style="background: #f5f5f5; padding: 10px; border-left: 3px solid #0073aa;">define('SAVEQUERIES', true);</pre>
				<p>
					<strong>Important:</strong> Only do this on development or staging sites. SAVEQUERIES adds overhead 
					because WordPress has to save information about every query. Don't enable this on production unless 
					you're actively debugging and plan to disable it soon.
				</p>
				<p>
					<strong>Current status:</strong> 
					<?php if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) : ?>
						<span style="color: #46b450;">✓ SAVEQUERIES is enabled</span> - Slow query detection is active.
					<?php else : ?>
						<span style="color: #999;">SAVEQUERIES is not enabled</span> - Slow query detection is unavailable.
					<?php endif; ?>
				</p>
			</div>
			
			<style>
				.card {
					background: white;
					border: 1px solid #ccd0d4;
					border-left: 4px solid #0073aa;
					padding: 20px;
					margin: 20px 0;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
				}
				.card h2 {
					margin-top: 0;
				}
				.card h3 {
					margin-top: 20px;
					margin-bottom: 10px;
				}
				.card ul {
					margin-left: 20px;
				}
				.card code {
					background: #f5f5f5;
					padding: 2px 6px;
					border-radius: 3px;
				}
			</style>
		</div>
		<?php
	}
	
	/**
	 * Render current status section
	 */
	private function render_current_status() {
		global $wpdb;
		
		$current_memory_mb = memory_get_peak_usage( true ) / 1024 / 1024;
		$num_queries = $wpdb->num_queries;
		
		?>
		<p>Here's what we're seeing on this page right now:</p>
		<table class="widefat" style="max-width: 600px;">
			<tbody>
				<tr>
					<td><strong>Database Queries</strong></td>
					<td><?php echo esc_html( $num_queries ); ?> queries</td>
				</tr>
				<tr>
					<td><strong>Memory Usage</strong></td>
					<td><?php echo esc_html( number_format( $current_memory_mb, 1 ) ); ?> MB</td>
				</tr>
				<tr>
					<td><strong>SAVEQUERIES Status</strong></td>
					<td>
						<?php if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) : ?>
							<span style="color: #46b450;">Enabled</span>
						<?php else : ?>
							<span style="color: #999;">Disabled</span>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<p style="color: #666; font-size: 0.9em;">
			This is just for this specific page (Tools → Performance Checkup). The notices you see elsewhere 
			reflect the metrics for those pages.
		</p>
		<?php
	}
}
