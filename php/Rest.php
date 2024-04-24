<?php
/**
 * Rest actions for the plugin.
 *
 * @package APP
 */

namespace DLXPlugins\APP;

/**
 * Class Rest
 */
class Rest {

	/**
	 * Register Rest actions.
	 */
	public function run() {
		// Rest API.
		add_action( 'rest_api_init', array( $this, 'rest_api_register' ) );
	}

	/**
	 * Gets permissions for the get users rest api endpoint.
	 *
	 * @return bool true if the user has permission, false if not
	 **/
	public function rest_get_users_permissions_callback() {
		return current_user_can( 'publish_posts' );
	}

	/**
	 * Registers REST API endpoints
	 */
	public function rest_api_register() {

		register_rest_route(
			'dlxplugins/app/v1',
			'/search/pages',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'rest_get_users_permissions_callback' ),
				'callback'            => array( $this, 'rest_get_pages' ),
			)
		);
	}

	/**
	 * Returns the 5 most recent posts for the user
	 *
	 * @param array $request The REST Request data.
	 **/
	public function rest_get_pages( $request ) {
		$output      = get_option( 'post-type-archive-mapping', array() );
		$search = sanitize_text_field( urldecode( $request['search'] ) );
		// Get EDD Query.
		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $search,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( empty( $search ) ) {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		// Perform EDD Query.
		$app_query = new \WP_Query( $args );

		// Return array of downloads with download ID and download name.
		$app_data = array();
		if ( $app_query->have_posts() ) {
			while ( $app_query->have_posts() ) {
				$app_query->the_post();
				$post_title = get_the_title();
				if ( empty( $post_title ) ) {
					$post_title = __( '(no title)', 'archive-pages-pro' );
				}
				$app_data[] = array(
					'value'     => absint( get_the_ID() ),
					'label'     => sanitize_text_field( $post_title ),
					'permalink' => esc_url( get_the_permalink() ),
				);
			}
		}

		wp_send_json_success( $app_data );
	}

	/**
	 * Makes sure the ID we are passed is numeric
	 *
	 * @param mixed $param   The paramater to validate.
	 * @param array $request The REST request.
	 * @param mixed $key     The key to check.
	 *
	 * @return bool Whether to the parameter is numeric or not.
	 **/
	public function rest_api_validate( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Sanitizes user ID
	 *
	 * @param mixed $param   The paramater to validate.
	 * @param array $request The REST request.
	 * @param mixed $key     The key to check.
	 *
	 * @return int Sanitized user ID.
	 **/
	public function rest_api_sanitize( $param, $request, $key ) {
		return absint( $param );
	}
}
