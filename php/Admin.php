<?php
/**
 * Admin class.
 *
 * @package APP
 */

namespace DLXPlugins\APP;


/**
 * Admin class.
 */
class Admin {

	/**
	 * Class runner.
	 */
	public function run() {
		// Init the admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Enqueue scripts for the admin page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// For retrieving the options.
		add_action( 'wp_ajax_dlx_app_get_options', array( $this, 'ajax_get_options' ) );

		// For saving the options.
		add_action( 'wp_ajax_dlx_app_save_options', array( $this, 'ajax_save_options' ) );

		// For resetting the options.
		add_action( 'wp_ajax_dlx_app_reset_options', array( $this, 'ajax_reset_options' ) );

		// For getting license options.
		add_action( 'wp_ajax_dlx_app_license_get_options', array( $this, 'ajax_license_get_options' ) );

		// For revoking a license.
		add_action( 'wp_ajax_dlx_app_revoke_license', array( $this, 'ajax_revoke_license' ) );

		// For saving a license.
		add_action( 'wp_ajax_dlx_app_save_license', array( $this, 'ajax_save_license' ) );

		// For initializing EDD license.
		add_action( 'admin_init', array( $this, 'init_license_system' ) );

		// For initializing settings links on the plugins screen.
		add_action( 'admin_init', array( $this, 'init_settings_links' ) );
	}

	/**
	 * Initialize the setting links for the plugin page.
	 */
	public function init_settings_links() {
		$prefix = Functions::is_multisite() ? 'network_admin_' : '';
		add_action( $prefix . 'plugin_action_links_' . plugin_basename( ARCHIVE_PAGES_PRO_FILE ), array( $this, 'plugin_settings_link' ) );
	}

	/**
	 * Adds plugin settings page link to plugin links in WordPress Dashboard Plugins Page
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Uses $prefix . "plugin_action_links_$plugin_file" action.
	 * @return array Array of settings
	 */
	public function plugin_settings_link( $settings ) {
		$setting_links = array(
			'settings' => sprintf( '<a href="%s">%s</a>', esc_url( Functions::get_settings_url() ), esc_html__( 'Settings', 'archive-pages-pro' ) ),
			'docs'     => sprintf( '<a href="%s">%s</a>', esc_url( 'https://docs.dlxplugins.com/v/archive-pages-pro/' ), esc_html__( 'Docs', 'archive-pages-pro' ) ),
			'site'     => sprintf( '<a href="%s" style="color: #f60098;">%s</a>', esc_url( 'https://dlxplugins.com/plugins/archive-pages-pro/' ), esc_html__( 'Plugin Home', 'archive-pages-pro' ) ),
		);
		if ( ! is_array( $settings ) ) {
			return $setting_links;
		} else {
			return array_merge( $setting_links, $settings );
		}
	}

	/**
	 * Ajax revoke license.
	 */
	public function ajax_revoke_license() {
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT ), 'dlx-app-license-revoke' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array() );
		}

		$form_data = filter_input( INPUT_POST, 'formData', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! $form_data ) {
			wp_send_json_error( array() );
		}
		$form_data = Functions::sanitize_array_recursive( $form_data );

		// Get license.
		$license_key    = $form_data['licenseKey'] ?? '';
		$license_helper = new Plugin_License( $license_key );
		$response       = $license_helper->perform_action( 'deactivate_license', $license_key, true );

		// Overrride options.
		$options = Options::get_options();

		// Clear options.
		$options['licenseValid']     = false;
		$options['licenseActivated'] = false;
		$options['licenseKey']       = '';
		$options['licenseData']      = false;

		Options::update_options( $options );
		if ( $response['license_errors'] ) {
			$license_helper->set_activated_status( false );
			wp_send_json_error( $response );
		}

		$license_helper->set_activated_status( false );
		$options['licenseValid']     = false;
		$options['licenseActivated'] = false;
		$options['licenseKey']       = '';
		$options['licenseData']      = false;

		// Update options (force).
		Options::update_options( $options );

		wp_send_json_success( $options );
	}

	/**
	 * Save/Check a license key.
	 */
	public function ajax_save_license() {
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT ), 'dlx-app-license-save' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array() );
		}

		$form_data = filter_input( INPUT_POST, 'formData', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! $form_data ) {
			wp_send_json_error( array() );
		}
		$form_data = Functions::sanitize_array_recursive( $form_data );

		// Get license.
		$license_key    = $form_data['licenseKey'] ?? '';
		$license_helper = new Plugin_License( $license_key );
		$response       = $license_helper->perform_action( 'activate_license', $license_key, true );

		if ( $response['license_errors'] ) {
			$license_helper->set_activated_status( false );
			wp_send_json_error( $response );
		}

		// Get latest options.
		$options                = Options::get_options( true );
		$options['licenseKey']  = $license_key;
		$options['licenseData'] = get_site_transient( 'app_core_license_check', array() );
		wp_send_json_success( $options );
	}

	/**
	 * Allow for automatic updates.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init_license_system() {
		$options = Options::get_options();

		$license_valid = $options['licenseValid'] ?? '';
		if ( isset( $options['licenseKey'] ) && 'valid' === $license_valid ) {
			// setup the updater.
			$edd_updater = new Plugin_Updater(
				'https://dlxplugins.com',
				ARCHIVE_PAGES_PRO_FILE,
				array(
					'version' => Functions::get_plugin_version(),
					'license' => $options['licenseKey'],
					'item_id' => ARCHIVE_PAGES_PRO_PRODUCT_ID,
					'author'  => 'Ronald Huereca',
					'beta'    => true,
					'url'     => home_url(),
				)
			);
		}
	}

	/**
	 * Get license options via Ajax.
	 */
	public function ajax_license_get_options() {
		// Get nonce.
		$nonce = sanitize_text_field( filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT ) );

		// Verify nonce.
		$nonce_action = 'dlx-app-license-get';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Nonce or permission verification failed.', 'archive-pages-pro' ),
					'type'        => 'error',
					'dismissable' => true,
					'title'       => __( 'Error', 'archive-pages-pro' ),
				)
			);
		}
		$options                = Options::get_options( true );
		$options['licenseData'] = get_site_transient( 'app_core_license_check', array() );
		wp_send_json_success( $options );
	}

	/**
	 * Save the options via Ajax.
	 */
	public function ajax_save_options() {
		// Get form data.
		$form_data = filter_input( INPUT_POST, 'formData', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$nonce = $form_data['saveNonce'] ?? false;
		if ( ! wp_verify_nonce( $nonce, 'dlx-app-settings-save-options' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Nonce or permission verification failed.', 'archive-pages-pro' ),
					'type'        => 'critical',
					'dismissable' => true,
					'title'       => __( 'Error', 'archive-pages-pro' ),
				)
			);
		}

		// If no font data, assume empty array.
		if ( ! isset( $form_data['allowedGoogleFonts'] ) ) {
			$form_data['allowedGoogleFonts'] = array();
		}

		$form_enabled_post_types = $form_data['enabledPostTypes'] ?? array();
		$enabled_post_types      = array();

		// Loop through enabled post types to save them in the right format.
		foreach ( $form_enabled_post_types as $post_type => $enabled ) {
			$post_type = trim( sanitize_text_field( $post_type ) );
			if ( is_numeric( $post_type ) ) {
				continue;
			}
			$enabled_post_types[ $post_type ] = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );
		}

		// Assign back.
		$form_data['enabledPostTypes'] = $enabled_post_types;

		// Get array values.
		$form_data = Functions::sanitize_array_recursive( $form_data );

		// Update options.
		Options::update_options( $form_data );

		// Send success message.
		wp_send_json_success(
			array(
				'message'     => __( 'Options saved.', 'archive-pages-pro' ),
				'type'        => 'success',
				'dismissable' => true,
			)
		);
	}

	/**
	 * Reset the options.
	 */
	public function ajax_reset_options() {
		// Get form data.
		$form_data = filter_input( INPUT_POST, 'formData', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$nonce = $form_data['resetNonce'] ?? false;
		if ( ! wp_verify_nonce( $nonce, 'dlx-app-settings-reset-options' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Nonce or permission verification failed.', 'archive-pages-pro' ),
					'type'        => 'error',
					'dismissable' => true,
					'title'       => __( 'Error', 'archive-pages-pro' ),
				)
			);
		}

		// Get existing options.
		$options = Options::get_options();

		// Get defaults and reset.
		$default_options = Options::get_defaults();

		// Don't reset license.
		$license_keys = array(
			'licenseKey',
			'licenseValid',
			'licenseActivated',
			'licenseData',
		);
		foreach ( $license_keys as $license_key ) {
			$default_options[ $license_key ] = $options[ $license_key ];
		}

		Options::update_options( $default_options );

		// Pull in nonces to default options before returning.
		$default_options['saveNonce']  = $options['saveNonce'];
		$default_options['resetNonce'] = $options['resetNonce'];

		// Format empty arrays into false. This is so they can be reset at the form level.
		$default_options['membershipLevelsToExclude'] = false;
		$default_options['checkoutLevelsToExclude']   = false;

		// Send success message.
		wp_send_json_success(
			array(
				'message'     => __( 'Options reset.', 'archive-pages-pro' ),
				'type'        => 'success',
				'dismissable' => true,
				'formData'    => $default_options,
			)
		);
	}

	/**
	 * Retrieve options via Ajax.
	 */
	public function ajax_get_options() {
		// Get nonce.
		$nonce = sanitize_text_field( filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT ) );

		// Verify nonce.
		$nonce_action = 'dlx-app-settings-get-options';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Nonce or permission verification failed.', 'archive-pages-pro' ),
					'type'        => 'error',
					'dismissable' => true,
					'title'       => __( 'Error', 'archive-pages-pro' ),
				)
			);
		}
		$options = Options::get_options();
		wp_send_json_success( $options );
	}

	/**
	 * Add the admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Archive Mapping', 'archive-pages-pro' ),
			__( 'Archive Mapping', 'archive-pages-pro' ),
			'manage_options',
			'archive-pages-pro',
			array( $this, 'admin_page' ),
			4
		);
	}

	/**
	 * Enqueue scripts for the admin page.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'settings_page_archive-pages-pro' !== $hook ) {
			return;
		}

		$options     = Options::get_options();
		$current_tab = Functions::get_admin_tab();
		if ( null === $current_tab || 'settings' === $current_tab ) {
			// Enqueue main scripts.
			$deps = require_once Functions::get_plugin_dir( 'build/app-admin-settings.asset.php' );
			wp_enqueue_script(
				'dlx-app-settings',
				Functions::get_plugin_url( 'build/app-admin-settings.js' ),
				$deps['dependencies'],
				$deps['version'],
				true
			);

			// Get all show in menu post types.
			$post_types = get_post_types(
				array(
					'show_ui' => true,
				),
				'objects'
			);
			$excluded   = array( 'attachment', 'revision', 'nav_menu_item', 'gblocks_templates', 'gblocks_global_style' );
			foreach ( $excluded as $exclude ) {
				if ( isset( $post_types[ $exclude ] ) ) {
					unset( $post_types[ $exclude ] );
				}
			}

			wp_localize_script(
				'dlx-app-settings',
				'dlxAppSettings',
				array(
					'getNonce'     => wp_create_nonce( 'dlx-app-settings-get-options' ),
					'saveNonce'    => wp_create_nonce( 'dlx-app-settings-save-options' ),
					'resetNonce'   => wp_create_nonce( 'dlx-app-settings-reset-options' ),
					'previewNonce' => wp_create_nonce( 'dlx-app-settings-preview' ),
					'ajaxurl'      => admin_url( 'admin-ajax.php' ),
					'postTypes'    => $post_types,
					'options'      => $options,
				)
			);
		} elseif ( 'license' === $current_tab ) {
			$deps = require_once Functions::get_plugin_dir( 'build/app-license.asset.php' );
			wp_enqueue_script(
				'dlx-app-license',
				Functions::get_plugin_url( 'build/app-license.js' ),
				$deps['dependencies'],
				$deps['version'],
				true
			);
			wp_localize_script(
				'dlx-app-license',
				'dlxAppLicense',
				array(
					'getNonce'    => wp_create_nonce( 'dlx-app-license-get' ),
					'saveNonce'   => wp_create_nonce( 'dlx-app-license-save' ),
					'revokeNonce' => wp_create_nonce( 'dlx-app-license-revoke' ),
				)
			);
		}

		// Enqueue admin styles.
		wp_enqueue_style(
			'dlx-app-settings-css',
			Functions::get_plugin_url( 'dist/app-admin-css.css' ),
			array(),
			Functions::get_plugin_version(),
			'all'
		);
	}

	/**
	 * Render the admin page.
	 */
	public function admin_page() {
		?>
		<div class="dlx-app-admin-wrap">
			<header class="dlx-app-admin-header">
				<div class="dlx-app-logo-wrapper">
					<div class="dlx-app-logo">
						<h2 id="dlx-app-admin-header">
							<img src="<?php echo esc_url( Functions::get_plugin_url( 'assets/logo.png' ) ); ?>" alt="Archive Pages Pro" />
						</h2>
					</div>
					<div class="header__btn-wrap">
						<a href="<?php echo esc_url( 'https://docs.dlxplugins.com/v/archive-pages-pro/' ); ?>" target="_blank" rel="noopener noreferrer" class="has__btn-primary"><?php esc_html_e( 'Docs', 'archive-pages-pro' ); ?></a>
						<a href="<?php echo esc_url( 'https://dlxplugins.com/support/' ); ?>" target="_blank" rel="noopener noreferrer" class="has__btn-primary"><?php esc_html_e( 'Support', 'archive-pages-pro' ); ?></a>
					</div>
				</div>
			</header>
			<?php
			$current_tab        = Functions::get_admin_tab();
			$settings_tab_class = array( 'nav-tab' );
			if ( null === $current_tab || 'settings' === $current_tab ) {
				$settings_tab_class[] = 'nav-tab-active';
			}
			$license_tab_class = array( 'nav-tab' );
			if ( 'license' === $current_tab ) {
				$license_tab_class[] = 'nav-tab-active';
			}
			$help_tab_class = array( 'nav-tab' );
			if ( 'help' === $current_tab ) {
				$help_tab_class[] = 'nav-tab-active';
			}
			?>
			<main class="dlx-app-admin-body-wrapper">
				<div class="has-admin-container-body">
					<nav class="nav-tab-wrapper">
						<a  class="<?php echo esc_attr( implode( ' ', $settings_tab_class ) ); ?>" href="<?php echo esc_url( Functions::get_settings_url() ); ?>"><?php esc_html_e( 'Settings', 'archive-pages-pro' ); ?></a>
						<a  class="<?php echo esc_attr( implode( ' ', $license_tab_class ) ); ?>" href="<?php echo esc_url( Functions::get_settings_url( 'license' ) ); ?>"><?php esc_html_e( 'License', 'archive-pages-pro' ); ?></a>
					</nav>
				</div>
				<div class="dlx-app-body__content">
					<?php
					if ( null === $current_tab || 'settings' === $current_tab ) {
						?>
							<div id="dlx-app-settings"></div>
						<?php
					} elseif ( 'license' === $current_tab ) {
						?>
							<div id="dlx-app-license"></div>
						<?php
					}
					?>
				</div>
			</main>
		</div>
		<?php
	}
}
