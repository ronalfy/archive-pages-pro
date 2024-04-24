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

		// Enqueue scripts.
		$enqueue = new Enqueue();
		$enqueue->run();

		// Init REST.
		$rest = new REST();
		$rest->run();

		add_action( 'admin_init', array( $this, 'init_settings_api' ) );
		//add_action( 'pre_get_posts', array( $this, 'maybe_override_archive' ) );

		// Output admin notices once when saving archive mapping.
		//add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// 404 page detection.
		//add_filter( 'template_include', array( $this, 'maybe_force_404_template' ), 1 );
	}

	/**
	 * Initialize options
	 *
	 * Initialize page settings, fields, and sections and their callbacks
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init
	 */
	public function init_settings_api() {

		// Get taxonomies.
		$taxonomies = get_taxonomies(
			array(
				'public' => true,
			),
			'objects'
		);
		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy->name}_edit_form", array( $this, 'map_term_interface' ) );
		}
		add_action( 'edit_term', array( $this, 'save_mapped_term' ) );

		add_settings_section(
			'archive-pages-pro',
			false,
			array( $this, 'settings_section' ),
			'reading'
		);

		add_settings_field(
			'archive-pages-pro',
			__( 'Map Archives', 'archive-pages-pro' ),
			array( $this, 'add_settings_reading' ),
			'reading',
			'archive-pages-pro'
		);
		// Register post type mapping settings.
		register_setting(
			'reading',
			'post-type-archive-mapping',
			array(
				'sanitize_callback' => array( $this, 'post_type_save' ),
			)
		);
		register_setting(
			'reading',
			'post-type-archive-mapping-404',
			array(
				'sanitize_callback' => 'absint',
			)
		);
	}

	/**
	 * Save post meta if selected on the reading screen.
	 *
	 * @param array $args Post Type arguments.
	 */
	public function post_type_save( $args ) {
		if ( ! is_array( $args ) ) {
			return $args;
		}
		global $wpdb;
		$query = "delete from {$wpdb->postmeta} where meta_key = '_post_type_mapped'";
		$wpdb->query( $query ); // phpcs:ignore
		foreach ( $args as $post_type => $page_id ) {
			$maybe_mapped = get_post_meta( $page_id, '_term_mapped', true );
			if ( $maybe_mapped ) {
				update_option(
					'ptam_error_message',
					sprintf(
						/* Translators: %s is the page title */
						__( 'The page %s to map to a post type archive is already mapped to a term.', 'archive-pages-pro' ),
						esc_html( get_the_title( $page_id ) )
					)
				);
				unset( $args[ $post_type ] );
			} else {
				update_post_meta( $page_id, '_post_type_mapped', $post_type );
			}
		}
		return $args;
	}

	/**
	 * Output settings HTML
	 *
	 * Output any HTML required to go into a settings section
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init_admin_settings
	 */
	public function settings_section() {
	}

	/**
	 * Add post type options to Settings->Reading screen.
	 *
	 * @param array $args Post Type arguments.
	 */
	public function add_settings_reading( $args ) {
		$post_types = Functions::get_post_types();
		if ( ! $post_types ) {
			?>
			<p><?php esc_html_e( 'No public post types found.', 'archive-pages-pro' ); ?></p>
			<?php
			return;
		}

		// Parse the post types into data attributes.
		$post_types_data = array();
		foreach ( $post_types as $post_type ) {
			$post_types_data[] = array(
				'value' => $post_type->name,
				'label' => $post_type->label,
			);
		}

		// Map to a data attribute.
		$post_types_data = wp_json_encode( $post_types_data );

		?>
		<div id="app-reading" data-post-types="<?php echo esc_attr( $post_types_data ); ?>"><?php esc_html_e( 'Loading...', 'archive-pages-pro' ); ?></div>
		<?php
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

add_action( 'plugins_loaded', __NAMESPACE__ . '\archive_pages_pro_instantiate', 9 );
/**
 * Instantiate the APP class.
 */
function archive_pages_pro_instantiate() {

	// Disable post type mapping in custom query blocks.
	//add_filter( 'ptam_archive_mapping_disabled', '__return_true' );

	// Set up our plugin.
	$app_instance = Archive_Pages_Pro::get_instance();
	$app_instance->plugins_loaded();
}
