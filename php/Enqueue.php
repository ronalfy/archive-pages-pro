<?php
/**
 * Enqueue scripts and styles.
 *
 * @package APP
 */

 namespace DLXPlugins\APP;

/**
 * Class Enqueue
 */
class Enqueue {

	/**
	 * Class Runner.
	 */
	public function run() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_reading_script' ) );
	}

	/**
	 * Enqueue the profile page script.
	 *
	 * @param string $hook The current page hook.
	 */
	public function enqueue_settings_reading_script( string $hook ) {
		if ( 'options-reading.php' !== $hook ) {
			return;
		}

		$output = get_option( 'post-type-archive-mapping', array() );
		$page_id_404 = get_option( 'post-type-archive-mapping-404', 0 );

		// Enqueue main script.
		wp_enqueue_script(
			'app-settings-reading',
			Functions::get_plugin_url( 'dist/app-settings-reading.js' ),
			array(),
			Functions::get_plugin_version(),
			true
		);

		wp_enqueue_style(
			'app-admin-css',
			Functions::get_plugin_url( 'dist/app-admin.css' ),
			array(),
			Functions::get_plugin_version(),
			'all'
		);

		// Get post types.
		$post_types       = get_post_types(
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		$post_type_return = array();
		foreach ( $post_types as $index => $post_type ) {
			$mapped = 'default';
			if ( isset( $output[ $post_type ] ) ) {
				$mapped = $output[ $post_type ];
			}
			$post_type_label = $post_type;
			$post_type_data  = get_post_type_object( $post_type );
			if ( isset( $post_type_data->label ) && ! empty( $post_type_data->label ) ) {
				$post_type_label = $post_type_data->label;
			}
			$post_type_return[] = array(
				'value'  => $post_type,
				'label'  => $post_type_label,
				'mapped' => $mapped, /* can be page id or 'default' */
			);
		}

		// // Set local vars.
		// $local_vars = array(
		// 'restUrl'       => rest_url( 'aplus/v1/' ),
		// 'getNonce'      => wp_create_nonce( 'avatars-plus-profile-page-get' ),
		// 'saveNonce'     => wp_create_nonce( 'avatars-plus-profile-page-save' ),
		// 'restNonce'     => wp_create_nonce( 'wp_rest' ),
		// 'networkAdmin'  => is_network_admin(),
		// 'siteId'        => get_current_blog_id(),
		// 'profileUserId' => Functions::get_user_id(),
		// 'defaultAvatar' => Functions::get_plugin_url( '/images/user.png' ),
		// );

		wp_localize_script(
			'app-settings-reading',
			'appSettingsReading',
			array(
				'postTypes' => $post_type_return,
				'pageId404' => $page_id_404,
				'pageRestUrl' => rest_url( 'dlxplugins/app/v1/search/pages' ),
				'restNonce'        => wp_create_nonce( 'wp_rest' ),
			)
		);

	}
}
