<?php // phpcs:ignore

/*
 * Plugin Name: Archive Pages Pro
 * Plugin URI: https://dlxplugins.com/plugins/archive-pages-pro/
 * Description: Map archives to pages with a few clicks.
 * Author: DLX Plugins
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Author URI: https://dlxplugins.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: archive-pages-pro
 */

namespace DLXPlugins\APP;

define( 'ARCHIVE_PAGES_PRO_VERSION', '1.0.0' );
define( 'ARCHIVE_PAGES_PRO_FILE', __FILE__ );

// Support for site-level autoloading.
if ( file_exists( __DIR__ . '/lib/autoload.php' ) ) {
	require_once __DIR__ . '/lib/autoload.php';
}

/**
 * Archive Pages Pro Main Class
 */
class Archive_Pages_Pro {
	/**
	 * Highlight and Share instance.
	 *
	 * @var Archive_Pages_Pro $instance Instance of Highlight and Share class.
	 */
	private static $instance = null;

	/**
	 * Return an instance of the class
	 *
	 * Return an instance of the Highlight and Share Class.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return Archive_Pages_Pro class instance.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) { // phpcs:ignore
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Run actions on plugin load.
	 */
	public function plugins_loaded() {

	}

	/**
	 * Class constructor.
	 *
	 * Initialize plugin and load text domain for internationalization
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct() {
		// i18n initialization.
		load_plugin_textdomain( 'archive-pages-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\archive_pages_pro_instantiate' );
/**
 * Instantiate the APP class.
 */
function archive_pages_pro_instantiate() {
	$app_instance = Archive_Pages_Pro::get_instance();
	$app_instance->plugins_loaded();
}
