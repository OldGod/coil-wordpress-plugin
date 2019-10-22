<?php
/**
 * Coil - Display notices in the WordPress admin.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  Coil/Admin/Notices
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Coil_Admin_Notices' ) ) {

	class Coil_Admin_Notices {

		/**
		 * Activation date.
		 *
		 * @access public
		 * @static
		 * @var    string
		 */
		public static $install_date;

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			self::$install_date = get_site_option( 'coil_install_date', time() );

			// Check WordPress environment.
			add_action( 'admin_init', array( $this, 'check_wp' ), 12 );

			// Don't bug the user if they don't want to see any notices.
			add_action( 'admin_init', array( $this, 'dont_bug_me' ), 15 );

			// Display other admin notices when required. All are dismissible.
			add_action( 'admin_print_styles', array( $this, 'add_notices' ), 0 );
		} // END __construct()

		/**
		 * Checks that the WordPress version meets the plugin requirement.
		 *
		 * @access public
		 * @global string $wp_version
		 * @return bool
		 */
		public function check_wp() {
			global $wp_version;

			// If the current user can not install plugins then return nothing!
			if ( ! current_user_can( 'install_plugins' ) ) {
				return false;
			}

			if ( ! version_compare( $wp_version, COIL_WP_VERSION_REQUIRE, '>=' ) ) {
				add_action( 'admin_notices', array( $this, 'requirement_wp_notice' ) );
				return false;
			}

			return true;
		} // END check_wp()

		/**
		 * Don't bug the user if they don't want to see any notices.
		 *
		 * @access public
		 * @global $current_user
		 */
		public function dont_bug_me() {
			global $current_user;

			$user_hidden_notice = false;

			// If the user is allowed to install plugins and requested to hide the review notice then hide it for that user.
			if ( ! empty( $_GET['hide_coil_review_notice'] ) && current_user_can( 'install_plugins' ) ) {
				add_user_meta( $current_user->ID, 'coil_hide_review_notice', '1', true );
				$user_hidden_notice = true;
			}

			// Did user hide a notice?
			if ( $user_hidden_notice ) {
				// Redirect to the plugins page.
				wp_safe_redirect( admin_url( 'plugins.php' ) );
				exit;
			}
		} // END dont_bug_me()

		/**
		 * Displays admin notice to review the plugin after 7 days or 
		 * more from the time the plugin was installed.
		 * 
		 * @access public
		 * @global $current_user
		 * @return void|bool
		 */
		public function add_notices() {
			global $current_user;

			// If the current user can not install plugins then return nothing!
			if ( ! current_user_can( 'install_plugins' ) ) {
				return false;
			}

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			// Notices should only show on the main dashboard and on the plugins screen.
			if ( ! in_array( $screen_id, Coil_Admin::coil_get_admin_screens() ) ) {
				return false;
			}

			// Is admin review notice hidden?
			$hide_review_notice = get_user_meta( $current_user->ID, 'coil_hide_review_notice', true );

			// Check if we need to display the review plugin notice.
			if ( empty( $hide_review_notice ) ) {
				// If it has been a week or more since activating the plugin then display the review notice.
				if ( ( intval( time() - self::$install_date ) ) > WEEK_IN_SECONDS ) {
					add_action( 'admin_notices', array( $this, 'plugin_review_notice' ) );
				}
			}
		} // END add_notices()

		/**
		 * Show the WordPress requirement notice.
		 *
		 * @access public
		 */
		public function requirement_wp_notice() {
			include( COIL_FILE_PATH . '/includes/admin/views/html-notice-requirement-wp.php' );
		} // END requirement_wp_notice()

		/**
		 * Show the plugin review notice.
		 *
		 * @access public
		 */
		public function plugin_review_notice() {
			$install_date = self::$install_date;

			//include( COIL_FILE_PATH . '/includes/admin/views/html-notice-please-review.php' );
		} // END plugin_review_notice()

	} // END class.

} // END if class exists.

return new Coil_Admin_Notices();
