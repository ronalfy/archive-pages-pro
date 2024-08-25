<?php
/**
 * RankMath compatibility.
 *
 * @package APP
 */

namespace DLXPlugins\APP;

/**
 * Class RankMath
 */
class RankMath {

	/**
	 * Main init functioin.
	 */
	public function run() {
		add_action(
			'wp',
			function () {
				if ( get_query_var( 'original_archive_type' ) && get_query_var( 'original_archive_id' ) ) {
					add_filter( 'rank_math/frontend/canonical', array( $this, 'modify_canonical_url' ), 10, 2 );
				}
			}
		);
	}

	/**
	 * Modify the canonical URL.
	 *
	 * @param string $canonical The current canonical URL.
	 *
	 * @return string Updated canonical URL.
	 */
	public function modify_canonical_url( $canonical ) {
		$archive_type = get_query_var( 'original_archive_type' );
		$archive_id   = get_query_var( 'original_archive_id' );

		// Get post type mapped options.
		$post_type_mapped = get_option( 'post-type-archive-mapping', array() );

		if ( 'page' === $archive_type && isset( $post_type_mapped[ $archive_id ] ) ) {
			$post_type = $archive_id;
			$canonical = esc_url_raw( get_post_type_archive_link( $post_type ) );
		}
		if ( 'term' === $archive_type ) {
			$term_id   = absint( $archive_id );
			$canonical = esc_url_raw( get_term_link( $term_id ) );
		}
		if ( 'author' === $archive_type ) {
			$author_id = absint( $archive_id );
			$canonical = esc_url_raw( get_author_posts_url( $author_id ) );
		}
		return $canonical;
	}
}
