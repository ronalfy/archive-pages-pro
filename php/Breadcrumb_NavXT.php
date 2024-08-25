<?php
/**
 * Breadcrumb_NavXT compatibility.
 *
 * @package APP
 */

namespace DLXPlugins\APP;

/**
 * Class Breadcrumb_NavXT
 */
class Breadcrumb_NavXT {

	/**
	 * Main init functioin.
	 */
	public function run() {
		add_action(
			'wp',
			function () {
				if ( get_query_var( 'original_archive_type' ) && get_query_var( 'original_archive_id' ) ) {
					add_filter( 'bcn_before_loop', array( $this, 'modify_breadcrumbs' ), 10, 1 );
				}
			}
		);
	}

	/**
	 * Modify the breadcrumbs.
	 *
	 * @param array $crumbs                The current breadcrumbs.
	 *
	 * @return array Updated breadcrumbs.
	 */
	public function modify_breadcrumbs( $crumbs ) {
		$archive_type = get_query_var( 'original_archive_type' );
		$archive_id   = get_query_var( 'original_archive_id' );

		// Get post type mapped options.
		$post_type_mapped = get_option( 'post-type-archive-mapping', array() );

		// Check if archive ID is in the mapped post types.
		if ( 'page' === $archive_type && isset( $post_type_mapped[ $archive_id ] ) ) {
			// Unset the second crumb.
			array_splice( $crumbs, 1, 1 );

			$post_type = get_post_type_object( $archive_id );

			$crumbs[] = new \bcn_breadcrumb(
				$post_type->label,
				'',
				array(),
				get_post_type_archive_link( $archive_id ),
			);
		}

		// Check if author.
		if ( 'author' === $archive_type ) {
			// Unset the second crumb.
			array_splice( $crumbs, 1, 1 );

			$author_id = absint( $archive_id );
			$author    = get_userdata( $author_id );

			$crumbs[] = new \bcn_breadcrumb(
				$author->display_name,
				'',
				array(),
				get_author_posts_url( $author_id ),
			);
		}

		// Check if term.
		if ( 'term' === $archive_type ) {
			$term_id = absint( $archive_id );
			$term    = get_term_by( 'id', $term_id, get_query_var( 'term_tax' ) );
			// Unset the second crumb.
			array_splice( $crumbs, 1, 1 );

			$crumbs[] = new \bcn_breadcrumb(
				$term->name,
				'',
				array(),
				get_term_link( $term_id ),
			);
		}

		return $crumbs;
	}
}
