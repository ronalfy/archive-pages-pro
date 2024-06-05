<?php
/**
 * Yoast compatibility.
 *
 * @package APP
 */

namespace DLXPlugins\APP;

/**
 * Class Yoast
 */
class Yoast {

	/**
	 * Main init functioin.
	 */
	public function run() {
		add_action(
			'wp',
			function () {
				if ( get_query_var( 'original_archive_type' ) && get_query_var( 'original_archive_id' ) ) {
					add_filter( 'wpseo_opengraph_desc', array( $this, 'opengraph_desc' ), 20, 1 );
					add_filter( 'wpseo_twitter_description', array( $this, 'opengraph_desc' ), 20, 1 );
					add_filter( 'wpseo_opengraph_title', array( $this, 'opengraph_title' ), 20, 1 );
					add_filter( 'wpseo_twitter_title', array( $this, 'opengraph_title' ), 20, 1 );
					add_filter( 'wpseo_opengraph_url', array( $this, 'opengraph_url' ), 20, 1 );
					add_filter( 'wpseo_canonical', array( $this, 'modify_canonical_url' ), 10, 2 );
					// Disable schemas on archives.
					add_filter( 'wpseo_json_ld_output', '__return_false' );
					add_filter( 'wpseo_breadcrumb_links', array( $this, 'modify_breadcrumbs' ), 20, 1 );
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
		return $canonical;
	}

	/**
	 * Modify the breadcrumbs.
	 *
	 * @param array $links The current breadcrumbs.
	 *
	 * @return array Updated breadcrumbs.
	 */
	public function modify_breadcrumbs( $links ) {
		$archive_type = get_query_var( 'original_archive_type' );
		$archive_id   = get_query_var( 'original_archive_id' );

		// Get post type mapped options.
		$post_type_mapped = get_option( 'post-type-archive-mapping', array() );

		// Check if archive ID is in the mapped post types.
		if ( 'page' === $archive_type && isset( $post_type_mapped[ $archive_id ] ) ) {
			// Get the post type label.
			$post_type = get_post_type_object( $archive_id );
			$mapped_id = absint( $post_type_mapped[ $archive_id ] );

			// Find the links by ref and modify to the post type archive.
			foreach ( $links as $index => &$link ) {
				if ( $mapped_id === $link['id'] ) {
					$link['url']  = get_post_type_archive_link( $archive_id );
					$link['text'] = $post_type->label;
				}
			}
		}

		if ( 'term' === $archive_type ) {
			$term_id = absint( $archive_id );
			$term    = get_term_by( 'id', $term_id, get_query_var( 'term_tax' ) );
			if ( false !== $term && ! is_wp_error( $term ) ) {
				// Get mapped term page.
				$term_page_id = get_term_meta( $term_id, '_term_archive_mapping', true );
				if ( $term_page_id ) {
					$term_page_id = absint( $term_page_id );
					foreach ( $links as $index => &$link ) {
						if ( $term_page_id === $link['id'] ) {
							$link['url']  = get_term_link( $term_id );
							$link['text'] = $term->name;
						}
					}
				}
			}
		}

		// Author archives.
		if ( 'author' === $archive_type ) {
			$author_id = absint( $archive_id );
			$author    = get_userdata( $author_id );

			/**
			 * Filter the author base for the breadcrumb.
			 *
			 * @param string $breadcrumb_base The author base.
			 *
			 * @since 1.0.0
			 */
			$breadcrumb_base = apply_filters(
				'archive_pages_pro_yoast_breadcrumb_author_base',
				__( 'Authors', 'archive-pages-pro' )
			);

			// Fill out the author archive link data.
			$author_link = array(
				/* author archives don't have a base URL */
				'url'  => '',
				/* get the text (i.e., Author) */
				'text' => sanitize_text_field( $breadcrumb_base ),
				'id'   => 0,
			);

			// Add the author link to the breadcrumbs.
			if ( false !== $author && ! is_wp_error( $author ) ) {
				// Get user mapped page ID.
				$author_page_id = get_user_meta( $author_id, 'app_archive_page_id', true );

				if ( $author_page_id && '0' !== $author_page_id ) {
					$author_page_id = absint( $author_page_id );
					// Add author link to the second position.
					array_splice( $links, 1, 0, array( $author_link ) );

					foreach ( $links as $index => &$link ) {
						if ( $author_page_id === $link['id'] ) {
							$link['url']  = get_author_posts_url( $author_id );
							$link['text'] = $author->display_name;
							$link['id']   = $author_page_id;
						}
					}
				}
			}
		}

		return $links;
	}

	/**
	 * Override Opengraph Description.
	 *
	 * @param string $description Open graph description.
	 *
	 * @return string description.
	 */
	public function opengraph_desc( $description ) {
		$archive_type = get_query_var( 'original_archive_type' );
		$archive_id   = get_query_var( 'original_archive_id' );

		if ( 'page' === $archive_type ) {
			$yoast_titles = get_option( 'wpseo_titles' );
			$post_type    = $archive_id;
			if ( isset( $yoast_titles[ 'metadesc-' . $post_type ] ) ) {
				return $yoast_titles[ 'metadesc-' . $post_type ];
			}
		}
		if ( 'term' === $archive_type ) {
			$yoast_tax_meta = get_option( 'wpseo_taxonomy_meta' );

			$term_id          = absint( $archive_id );
			$term             = get_term_by( 'id', $term_id, get_query_var( 'term_tax' ) );
			$term_description = get_term_field( 'description', $term_id );

			// Get Yoast term description meta.
			$yoast_term_description = '';
			if ( isset( $yoast_tax_meta[ get_query_var( 'term_tax' ) ][ $term_id ]['wpseo_desc'] ) ) {
				$yoast_term_description = $yoast_tax_meta[ get_query_var( 'term_tax' ) ][ $term_id ]['wpseo_desc'];
			}
			if ( is_wp_error( $term_description ) && '' === $yoast_term_description ) {
				return $description;
			}
			if ( '' !== $yoast_term_description ) {
				return $yoast_term_description;
			}
			return wp_strip_all_tags( $term_description );
		}
		return $description;
	}

	/**
	 * Change the opengraph url.
	 *
	 * @param string $url The URL to override.
	 *
	 * @return string Updated URL.
	 */
	public function opengraph_url( $url ) {
		$archive_type = get_query_var( 'original_archive_type' );
		$archive_id   = get_query_var( 'original_archive_id' );
		if ( 'page' === $archive_type ) {
			$post_type = $archive_id;
			$url       = rawurlencode( get_post_type_archive_link( $post_type ) );
			return $url;
		}
		if ( 'term' === $archive_type ) {
			$term_id = absint( $archive_id );
			$url     = rawurlencode( get_term_link( $term_id ) );
			return $url;
		}
		return $url;
	}

	/**
	 * Change the opengraph title.
	 *
	 * @param string $title The Title to override.
	 *
	 * @return string Updated Title.
	 */
	public function opengraph_title( $title ) {
		$archive_type = get_query_var( 'original_archive_type' );
		$archive_id   = get_query_var( 'original_archive_id' );
		if ( 'page' === $archive_type ) {
			$post_type      = $archive_id;
			$post_type_data = get_post_type_object( $post_type );

			// Get post type mapped options.
			$post_type_mapped = get_option( 'post-type-archive-mapping', array() );
			$mapped_id        = absint( $post_type_mapped[ $archive_id ] );

			// Get archive title from post type object.
			$title = isset( $post_type_data->labels->name ) ? apply_filters( 'post_type_archive_title', $post_type_data->labels->name, $post_type ) : $title;

			// Get the title for the post type archive from yoast settings.
			$yoast_options = get_option( 'wpseo_titles' );
			if ( isset( $yoast_options[ 'title-' . $post_type ] ) ) {
				$title_format = $yoast_options[ 'title-' . $post_type ];

				// Replace title format with actual title.
				if ( class_exists( 'WPSEO_Replace_Vars' ) ) {
					$replace_vars = \YoastSEO()->classes->get( \WPSEO_Replace_Vars::class );
					$maybe_title  = $replace_vars->replace( $title_format, get_post( $mapped_id ) );
					if ( null !== $maybe_title ) {
						$title = $maybe_title;
					}
				}
			}
			return $title;
		}
		if ( 'term' === $archive_type ) {
			$term_id = absint( $archive_id );
			$term    = get_term_by( 'id', $term_id, get_query_var( 'term_tax' ) );
			if ( is_wp_error( $term ) ) {
				return $title;
			}
			$title = apply_filters( 'single_term_title', $term->name );

			// Get the title from Yoast meta settings.
			$yoast_options = get_option( 'wpseo_titles' );
			if ( isset( $yoast_options[ 'title-tax-' . get_query_var( 'term_tax' ) ] ) ) {
				$title_format = $yoast_options[ 'title-tax-' . get_query_var( 'term_tax' ) ];

				// Replace title format with actual title.
				if ( class_exists( 'WPSEO_Replace_Vars' ) ) {
					$replace_vars = \YoastSEO()->classes->get( \WPSEO_Replace_Vars::class );
					$maybe_title  = $replace_vars->replace( $title_format, $term );
					if ( null !== $maybe_title ) {
						$title = $maybe_title;
					}
				}
			}

			return $title;
		}
		return $title;
	}
}
