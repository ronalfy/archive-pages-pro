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

		$output      = get_option( 'post-type-archive-mapping', array() );
		$page_id_404 = get_option( 'post-type-archive-mapping-404', 0 );

		// Enqueue main script.
		$deps = require_once Functions::get_plugin_dir( 'build/app-settings-reading.asset.php' );
		wp_enqueue_script(
			'app-settings-reading',
			Functions::get_plugin_url( 'build/app-settings-reading.js' ),
			$deps['dependencies'],
			$deps['version'],
			true
		);

		wp_enqueue_style(
			'app-settings-reading-css',
			Functions::get_plugin_url( 'build/app-settings-reading.css' ),
			array(),
			$deps['version'],
			'all'
		);

		// Get post types.
		$post_types       = Functions::get_post_types();
		$post_type_return = array();
		foreach ( $post_types as $index => $post_type ) {
			$mapped = 'default';
			if ( isset( $output[ $post_type->name ] ) ) {
				$mapped = $output[ $post_type->name ];
			}
			$post_type_label = sanitize_text_field( $post_type->label );

			// If mapped, get post title for mapped.
			$post_title     = '';
			$edit_post_link = '';
			if ( 'default' !== $mapped ) {
				$post_title = sanitize_text_field( get_the_title( $mapped ) );
				if ( empty( $post_title ) ) {
					$post_title = __( 'No title', 'archive-pages-pro' );
				}
			}
			$post_type_return[] = array(
				'value'      => $post_type->name,
				'label'      => $post_type_label,
				'mapped'     => $mapped, /* can be page id or 'default' */
				'title'      => $post_title,
				'archiveUrl' => get_post_type_archive_link( $post_type->name ),
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

		$page_title_404 = get_the_title( $page_id_404 );

		wp_localize_script(
			'app-settings-reading',
			'appSettingsReading',
			array(
				'postTypes'    => $post_type_return,
				'pageId404'    => $page_id_404,
				'pageTitle404' => $page_title_404,
				'pageRestUrl'  => rest_url( 'dlxplugins/app/v1/search/pages' ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'editPostUrl'  => admin_url( 'post.php' ),
			)
		);
	}
}
