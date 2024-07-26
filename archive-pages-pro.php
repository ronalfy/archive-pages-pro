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
define( 'ARCHIVE_PAGES_PRO_PRODUCT_ID', 0 );

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
	 * The current page number.
	 *
	 * @var int $paged The current page number.
	 */
	protected static $paged = null;

	/**
	 * Holds the paged reset argument.
	 *
	 * @var bool $paged_reset
	 */
	protected static $paged_reset = false;

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

		// Init Yoast.
		$yoast = new Yoast();
		$yoast->run();

		// Init admin.
		$admin = new Admin();
		$admin->run();

		add_action( 'admin_init', array( $this, 'init_settings_api' ) );
		add_action( 'pre_get_posts', array( $this, 'maybe_override_archive' ) );

		// 404 page detection.
		add_filter( 'template_include', array( $this, 'maybe_force_404_template' ), 1 );

		// Author profile pages in the admin do not support the settings API, so let's add the fields manually.
		add_action( 'show_user_profile', array( $this, 'add_profile_interface' ), 1 );
		add_action( 'edit_user_profile', array( $this, 'add_profile_interface' ), 1 );

		add_action( 'personal_options_update', array( $this, 'save_user_profile_options' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_options' ) );

		// Display an error message for the top of the user profile page (if applicable).
		add_action( 'admin_notices', array( $this, 'display_user_profile_error_message' ) );

		// Change the author base if set.
		add_filter( 'init', array( $this, 'change_author_base' ) );
		add_filter( 'author_rewrite_rules', array( $this, 'change_author_rewrites' ) );

		// Set up page templates if available for each enabled post type.
		add_action( 'admin_init', array( $this, 'init_page_templates_meta_box' ), 100 );
	}

	/**
	 * Initialize post type arguments.
	 */
	public function init_page_templates_meta_box() {
		$options = Options::get_options();
		$post_types = $options['postTypes'];

		if ( ! is_array( $post_types ) ) {
			return;
		}

		// Check if it's a block theme.
		if ( wp_is_block_theme() ) {
			return;
		}

		// Get current theme.
		$theme = wp_get_theme();

		// Get page templates in theme.
		$page_templates = $theme->get_page_templates( null, 'page' );
		if ( empty( $page_templates ) ) {
			return;
		}

		// Go through each post type and set up the arguments.
		foreach ( $post_types as $post_type ) {
			$enable_page_templates = (bool) $post_type['enable_page_templates'];
			if ( ! $enable_page_templates ) {
				continue;
			}
			add_meta_box(
				'app_page_template',
				__( 'Page Template', 'archive-pages-pro' ),
				array( $this, 'page_template_meta_box' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Display the page template meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function page_template_meta_box( $post ) {
		$options = Options::get_options();
		
		// Get current theme.
		$theme = wp_get_theme();

		// Get page templates in theme.
		$page_templates = $theme->get_page_templates( null, 'page' );
		$current_template = get_post_meta( $post->ID, '_app_page_template', true );
		if ( empty( $page_templates ) ) {
			return;
		}
		?>
		<label for="app_page_template"><?php esc_html_e( 'Page Template', 'archive-pages-pro' ); ?></label>
		<select name="app_page_template" id="app_page_template" class="widefat">
			<option value="default"><?php esc_html_e( 'Default Template', 'archive-pages-pro' ); ?></option>
			<?php
			foreach ( $page_templates as $template_slug => $template_label ) {
				$selected = selected( $current_template, $template_slug, false );
				echo '<option value="' . esc_attr( $template_slug ) . '" ' . $selected . '>' . esc_html( $template_label ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 * Change the author base if set.
	 */
	public function change_author_base() {
		global $wp_rewrite;
		$options     = Options::get_options();
		$author_base = sanitize_title( $options['authorBase'] );

		// Convert author_base to underlines.
		$author_base = str_replace( '-', '_', $author_base );

		// Make sure it isn't empty.
		if ( '' === $author_base ) {
			return;
		}

		if ( $author_base ) {
			$wp_rewrite->author_base = $author_base;
		}
	}

	/**
	 * Change the author rewrites if set.
	 *
	 * @param array $author_rewrite The author rewrite rules.
	 *
	 * @return array $author_rewrite The modified author rewrite rules.
	 */
	public function change_author_rewrites( $author_rewrite ) {
		$options     = Options::get_options(); // Assuming Options::get_options() retrieves your plugin options
		$author_base = sanitize_title( $options['authorBase'] );

		// Convert author_base to underscores (if needed).
		$author_base = str_replace( '-', '_', $author_base );

		// Ensure it's not empty.
		if ( '' === $author_base ) {
			return $author_rewrite;
		}

		// Create new rewrite rules array.
		$new_author_rewrite_rules                              = array();
		$new_author_rewrite_rules[ "$author_base/([^/]+)/?$" ] = 'index.php?author_name=$matches[1]';
		$new_author_rewrite_rules[ "$author_base/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$" ] = 'index.php?author_name=$matches[1]&feed=$matches[2]';
		$new_author_rewrite_rules[ "$author_base/([^/]+)/(feed|rdf|rss|rss2|atom)/?$" ]      = 'index.php?author_name=$matches[1]&feed=$matches[2]';
		$new_author_rewrite_rules[ "$author_base/([^/]+)/page/?([0-9]{1,})/?$" ]             = 'index.php?author_name=$matches[1]&paged=$matches[2]';

		return $new_author_rewrite_rules;
	}

	/**
	 * Display an error message on the user profile page.
	 */
	public function display_user_profile_error_message() {
		$error_message = get_option( 'app_error_message' );
		if ( $error_message ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
			<?php
			delete_option( 'app_error_message' );
		}
	}

	/**
	 * Save user profile options.
	 *
	 * @param int $user_id The user ID.
	 */
	public function save_user_profile_options( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( ! \wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'update-user_' . $user_id ) ) {
			return;
		}

		$options = Options::get_options();
		if ( ! (bool) $options['enableAuthorMapping'] ) {
			return;
		}

		// todo - check if nicename is the same as the passed user_id
		// todo - check if nicename is a reserved term.
		// todo - check if sanitized_title is run and return an error if they don't match.
		// todo - check if nicename already exists.

		$app_user_options = filter_input( INPUT_POST, 'app-user-profile', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
		if ( ! $app_user_options ) {
			return;
		}

		// Let's get the page ID that is currently mapped. Note if it's `default`, the page should be stripped.
		$maybe_mapped_page_id = sanitize_text_field( $app_user_options['page_id'] );
		if ( '0' === $maybe_mapped_page_id ) {
			delete_user_meta( $user_id, 'app_archive_page_id' );
		} else {
			$maybe_mapped_page_id = absint( $maybe_mapped_page_id );
			$maybe_mapped_page_id = apply_filters( 'wpml_object_id', $maybe_mapped_page_id, 'page', true );
			update_user_meta( $user_id, 'app_archive_page_id', $maybe_mapped_page_id );
		}

		$maybe_new_author_slug = sanitize_text_field( trim( $app_user_options['slug'] ) );
		$maybe_user            = get_user_by( 'slug', $maybe_new_author_slug );

		// Let's check if the user exists and is not the current user.
		if ( $maybe_user && $maybe_user->ID !== $user_id ) {
			update_option( 'app_error_message', esc_html__( 'The author slug you entered is already in use. Please enter a different slug.', 'archive-pages-pro' ) );
			return;
		} elseif ( $maybe_user ) {
			// If slugs are the same, exit.
			if ( $maybe_user && $maybe_user->ID === $user_id ) {
				return;
			}
		}

		// Get the sanitized author slug.
		$sanitized_author_slug = sanitize_title( trim( $maybe_new_author_slug ) );

		// If they don't match, return a malformed slug error.
		if ( $sanitized_author_slug !== $maybe_new_author_slug ) {
			update_option( 'app_error_message', esc_html__( 'The author slug you entered is malformed. Please enter a valid slug.', 'archive-pages-pro' ) );
			return;
		}

		/**
		 * Filter the sanitized author slug.
		 *
		 * This filter is run before the sanity checks are run.
		 *
		 * @param string $sanitized_author_slug The sanitized author slug.
		 * @param int    $user_id               The user ID.
		 * @param string $maybe_new_author_slug The original slug passed via POST.
		 *
		 * @since 1.0.0
		 */
		$sanitized_author_slug = apply_filters(
			'archive_pages_pro_pre_save_author_slug',
			$sanitized_author_slug,
			$user_id,
			$maybe_new_author_slug
		);

		// If the slug is empty, return early.
		if ( empty( $sanitized_author_slug ) ) {
			update_option( 'app_error_message', esc_html__( 'The author slug you entered is empty. Please enter a valid slug.', 'archive-pages-pro' ) );
			return;
		}

		$reserved_slugs = array( 'admin', 'administrator', 'login', 'user', 'profile', 'edit', 'author', 'author-slug' );

		/**
		 * Filter the reserved slugs.
		 *
		 * @param array $reserved_slugs The reserved slugs.
		 *
		 * @since 1.0.0
		 */
		$reserved_slugs = apply_filters( 'archive_pages_pro_reserved_slugs', $reserved_slugs );

		// If the slug is reserved, return a reserved slug error.
		if ( in_array( $sanitized_author_slug, $reserved_slugs, true ) ) {
			update_option( 'app_error_message', esc_html__( 'The author slug you entered is reserved. Please enter a different slug.', 'archive-pages-pro' ) );
			return;
		}

		// Update the user nicename.
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			$user->user_nicename = $sanitized_author_slug;

			/**
			 * Perform an action prior to saving the user.
			 *
			 * @since 1.0.0
			 */
			do_action( 'archive_pages_pro_pre_save_user', $user );
			wp_update_user( $user );
		}
	}

	/**
	 * This is a catch-all. Any 404 error not caught will be directed here.
	 * If a 404 error is caught, will load the page template instead.
	 *
	 * @param string $template The regular template.
	 *
	 * @return string $template The updated template.
	 */
	public function maybe_force_404_template( $template ) {
		$options = Options::get_options();
		if ( ! (bool) $options['enable404Mapping'] ) {
			return $template;
		}

		if ( is_404() ) {
			$page_id_404 = absint( get_option( 'post-type-archive-mapping-404', 0 ) );
			if ( $page_id_404 > 0 ) {
				$args = array(
					'post_type'      => 'page',
					'page_id'        => $page_id_404,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
				);
				/* I wise woman once told me to never use query_posts. Like NEVER. I had no choice here. */
				query_posts( // phpcs:ignore
					$args
				);
				return get_page_template();
			}
		}
		return $template;
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
		$options = Options::get_options();

		// Get taxonomies.
		$taxonomies = get_taxonomies(
			array(
				'public' => true,
			),
			'objects'
		);
		if ( (bool) $options['enableTermMapping'] ) {
			foreach ( $taxonomies as $taxonomy ) {
				add_action( "{$taxonomy->name}_edit_form", array( $this, 'map_term_interface' ) );
			}
			add_action( 'edit_term', array( $this, 'save_mapped_term' ) );
		}

		add_settings_section(
			'archive-pages-pro',
			false,
			array( $this, 'settings_section' ),
			'reading'
		);

		if ( (bool) $options['enablePostTypeArchiveMapping'] || (bool) $options['enable404Mapping'] ) {
			add_settings_field(
				'archive-pages-pro',
				__( 'Map Archives', 'archive-pages-pro' ),
				array( $this, 'add_settings_reading' ),
				'reading',
				'archive-pages-pro'
			);
		}

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
	 * Map Term Archives to Posts Options.
	 *
	 * @param object $tag The term object.
	 * @param string $taxonomy The taxonomy.
	 */
	public function map_term_interface( $tag, $taxonomy = '' ) {
		$post_id = get_term_meta( $tag->term_id, '_term_archive_mapping', true );
		if ( ! $post_id ) {
			$post_id = -1;
		}
		$term_permalink = get_term_link( $tag->term_id, $taxonomy );
		?>
		<h2><?php esc_html_e( 'Map Term Archive', 'archive-pages-pro' ); ?> (<a href="<?php echo esc_url( $term_permalink ); ?>" target="_blank" rel="noreferer noopener"><?php echo esc_html( $tag->name ); ?></a>)</h2>
		<p class="description"><?php esc_html_e( 'Map a term archive to a page.', 'archive-pages-pro' ); ?></p>
		<div id="app-term-mapping"><?php esc_html_e( 'Loading...', 'archive-pages-pro' ); ?></div>
		<?php
	}

	/**
	 * Map a saved term to a term ID.
	 *
	 * @param int $term_id The term ID to map.
	 */
	public function save_mapped_term( $term_id ) {
		if ( current_user_can( 'edit_term', $term_id ) ) {
			$maybe_post_id = filter_input( INPUT_POST, 'term_post_type', FILTER_VALIDATE_INT );
			if ( ! $maybe_post_id ) {
				delete_post_meta( $maybe_post_id, '_term_mapped' );
				delete_term_meta( $term_id, '_term_archive_mapping' );
			} elseif ( $maybe_post_id ) {
				update_post_meta( $maybe_post_id, '_term_mapped', $term_id );
				update_term_meta( $term_id, '_term_archive_mapping', $maybe_post_id );
			}
		}
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
		?>
		<div id="app-reading"><?php esc_html_e( 'Loading...', 'archive-pages-pro' ); ?></div>
		<?php
	}

	/**
	 * Map Term Archives to Posts Options.
	 *
	 * @param mixed $user_id_or_object The user ID or user object.
	 */
	public function add_profile_interface( $user_id_or_object ) {
		$options = Options::get_options();
		if ( ! (bool) $options['enableAuthorMapping'] ) {
			return;
		}
		if ( is_object( $user_id_or_object ) ) {
			$user_id = $user_id_or_object->ID;
		} else {
			$user_id = $user_id_or_object;
		}
		$author_permalink = get_author_posts_url( $user_id );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Author Archive', 'archive-pages-pro' ); ?></th>
					<td>
						<div id="app-author-mapping"><?php esc_html_e( 'Loading...', 'archive-pages-pro' ); ?></div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Override an archive page based on passed query arguments.
	 *
	 * @param WP_Query $query The query to check.
	 */
	public function maybe_override_archive( $query ) {
		if ( is_admin() ) {
			return $query;
		}

		$options                   = Options::get_options();
		$post_type_mapping_enabled = (bool) $options['enablePostTypeArchiveMapping'];
		$term_mapping_enabled      = (bool) $options['enableTermMapping'];
		$author_mapping_enabled    = (bool) $options['enableAuthorMapping'];

		// Maybe Redirect.
		if ( is_page() && $post_type_mapping_enabled ) {
			$object_id = get_queried_object_id();
			$post_meta = get_post_meta( $object_id, '_post_type_mapped', true );
			if ( $post_meta ) {
				if ( $post_meta && ! get_query_var( 'redirected' ) ) {
					wp_safe_redirect( get_post_type_archive_link( $post_meta ) );
					exit;
				}
			} else {
				if ( get_query_var( 'paged' ) ) {
					$query->set( 'paged', get_query_var( 'paged' ) );
				}
				return;
			}
		}

		// trigger this once after running the main query.
		if ( true === self::$paged_reset ) {
			$query->set( 'paged', self::$paged );
			set_query_var( 'paged', self::$paged );

			self::$paged_reset = false;
		}

		$post_types = get_option( 'post-type-archive-mapping', array() ); // old option name for compatibility with Custom Query Blocks (PTAM).
		if ( empty( $post_types ) && is_admin() && ! is_tax() && ! is_author() ) {
			return;
		}

		// trigger this the first time to get the current page.
		if ( is_null( self::$paged ) ) {
			self::$paged = get_query_var( 'paged' );
		}
		if ( is_array( $post_types ) && ! empty( $post_types ) && $post_type_mapping_enabled ) {
			foreach ( $post_types as $post_type => $post_id ) {
				if ( is_post_type_archive( $post_type ) && 'default' !== $post_id && $query->is_main_query() ) {
					$post_id = absint( $post_id );
					$post_id = apply_filters( 'wpml_object_id', $post_id, 'page', true );
					$query->set( 'post_type', 'page' );
					$query->set( 'page_id', $post_id );
					$query->set( 'redirected', true );
					$query->set( 'original_archive_type', 'page' );
					$query->set( 'original_archive_id', $post_type );
					$query->set( 'term_tax', '' );
					$query->set( 'paged', self::$paged );
					$query->is_archive           = false;
					$query->is_single            = true;
					$query->is_singular          = true;
					$query->is_post_type_archive = false;
					self::$paged_reset           = true;
				}
			}
		}
		if ( ( is_tax() || $query->is_category || $query->is_tag ) && $term_mapping_enabled ) {
			$post_id = get_term_meta( get_queried_object_id(), '_term_archive_mapping', true );
			$term    = get_queried_object();
			if ( $post_id && 'default' !== $post_id ) {
				$post_id = absint( $post_id );
				$query->set( 'post_type', 'page' );
				$query->set( 'page_id', $post_id );
				$query->set( 'redirected', true );
				$query->set( 'paged', self::$paged );
				$query->set( 'original_archive_type', 'term' );
				$query->set( 'original_archive_id', absint( $term->term_id ) );
				$query->set( 'term_tax', sanitize_text_field( $term->taxonomy ) );
				$query->is_page              = true;
				$query->is_archive           = false;
				$query->is_category          = false;
				$query->is_tag               = false;
				$query->is_tax               = false;
				$query->is_single            = true;
				$query->is_singular          = true;
				$query->is_post_type_archive = false;
				$query->queried_object_id    = $post_id;

				$query->queried_object = get_post( $post_id, OBJECT );
				self::$paged_reset     = true;
			}
		}

		// Map author archive to page.
		if ( ( is_author() || $query->is_author || $query->is_author_archive ) && $author_mapping_enabled ) {
			$author_name = get_query_var( 'author_name' );
			$author      = get_user_by( 'slug', $author_name );
			$author_id   = $author->ID;
			$post_id     = get_user_meta( $author_id, 'app_archive_page_id', true );
			if ( $post_id && ( 'default' !== $post_id && '0' !== $post_id ) ) {
				$post_id = absint( $post_id );
				$query->set( 'post_type', 'page' );
				$query->set( 'page_id', $post_id );
				$query->set( 'redirected', true );
				$query->set( 'paged', self::$paged );
				$query->set( 'original_archive_type', 'author' );
				$query->set( 'original_archive_id', absint( $author_id ) );
				$query->is_page              = true;
				$query->is_archive           = false;
				$query->is_author            = false;
				$query->is_author_archive    = false;
				$query->is_single            = true;
				$query->is_singular          = true;
				$query->is_post_type_archive = false;
				$query->queried_object_id    = $post_id;

				$query->queried_object = get_post( $post_id, OBJECT );
				self::$paged_reset     = true;
			}
		}
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
	add_filter( 'ptam_archive_mapping_disabled', '__return_true' );

	// Set up our plugin.
	$app_instance = Archive_Pages_Pro::get_instance();
	$app_instance->plugins_loaded();
}
