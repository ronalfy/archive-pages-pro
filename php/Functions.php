<?php
/**
 * Helper functions for the plugin.
 *
 * @package APP
 */

namespace DLXPlugins\APP;

/**
 * Class Functions
 */
class Functions {

	/**
	 * Checks if the plugin is on a multisite install.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network_admin Check if in network admin.
	 *
	 * @return true if multisite, false if not.
	 */
	public static function is_multisite( $network_admin = false ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		$is_network_admin = false;
		if ( $network_admin ) {
			if ( is_network_admin() ) {
				if ( is_multisite() && is_plugin_active_for_network( self::get_plugin_slug() ) ) {
					return true;
				}
			} else {
				return false;
			}
		}
		if ( is_multisite() && is_plugin_active_for_network( self::get_plugin_slug() ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the post type data.
	 *
	 * @return array|bool Post type data.
	 */
	public static function get_post_type_data() {
		$args = array(
			'show_ui' => true,
			'public'  => true,
		);
		/**
		 * Filter the default post type args.
		 *
		 * @param array $args Default post type args.
		 */
		$args       = apply_filters( 'archive_pages_pro_post_type_args', $args );
		$post_types = get_post_types(
			$args,
			'objects'
		);
		$excluded   = array( 'attachment', 'revision', 'nav_menu_item', 'gblocks_templates', 'post', 'page', 'gblocks_global_style' );
		/**
		 * Filter the post types to exclude.
		 *
		 * @param array $excluded Post types to exclude.
		 */
		$excluded = apply_filters( 'archive_pages_pro_excluded_post_types', $excluded );

		foreach ( $excluded as $exclude ) {
			if ( isset( $post_types[ $exclude ] ) ) {
				unset( $post_types[ $exclude ] );
			}
		}
		$options              = Options::get_options();
		$post_types_with_data = array();
		foreach ( $post_types  as $post_type ) {
			$post_type_object = get_post_type_object( $post_type->name );
			if ( ! $post_type_object ) {
				continue;
			}

			$data = array(
				'name'                  => $options['post_types'][ $post_type_object->name ]['name'] ?? $post_type_object->name,
				'label'                 => $options['post_types'][ $post_type_object->name ]['label'] ?? $post_type_object->label,
				'is_public'             => $options['post_types'][ $post_type_object->name ]['public'] ?? (bool) $post_type_object->public,
				'enable_has_archive'    => $options['post_types'][ $post_type_object->name ]['enable_has_archive'] ?? (bool) $post_type_object->has_archive,
				'enable_show_in_rest'   => $options['post_types'][ $post_type_object->name ]['enable_show_in_rest'] ?? (bool) $post_type_object->show_in_rest,
				'enable_with_front'     => $options['post_types'][ $post_type_object->name ]['enable_with_front'] ?? (bool) $post_type_object->rewrite['with_front'],
				'enable_page_templates' => $options['post_types'][ $post_type_object->name ]['enable_page_templates'] ?? false,
			);
			$post_types_with_data[ $post_type_object->name ] = $data;
		}
		return $post_types_with_data;
	}

	/**
	 * Get the post type data.
	 *
	 * @return array|bool Post type data.
	 */
	public static function get_taxonomy_data() {
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);

		/**
		 * Filter the default post type args.
		 *
		 * @param array $args Default post type args.
		 */
		$args       = apply_filters( 'archive_pages_pro_taxonomy_args', $args );
		$taxonomies = get_taxonomies(
			$args,
			'objects'
		);
		$excluded   = array( 'category', 'post_tag', 'post_format' );
		/**
		 * Filter the post types to exclude.
		 *
		 * @param array $excluded Post types to exclude.
		 */
		$excluded = apply_filters( 'archive_pages_pro_excluded_taxonomies', $excluded );

		foreach ( $excluded as $exclude ) {
			if ( isset( $taxonomies[ $exclude ] ) ) {
				unset( $taxonomies[ $exclude ] );
			}
		}
		$options              = Options::get_options();
		$taxonomies_with_data = array();
		foreach ( $taxonomies  as $taxonomy ) {
			$taxonomy_object = get_taxonomy( $taxonomy->name );
			if ( ! $taxonomy_object ) {
				continue;
			}

			$data                                    = array(
				'name'                => $options['taxonomies'][ $taxonomy->name ]['name'] ?? $taxonomy->name,
				'label'               => $options['taxonomies'][ $taxonomy->name ]['label'] ?? $taxonomy->label,
				'is_public'           => $options['taxonomies'][ $taxonomy->name ]['public'] ?? (bool) $taxonomy->public,
				'disable_archive'     => $options['taxonomies'][ $taxonomy->name ]['disable_archive'] ?? false,
				'enable_show_in_rest' => $options['taxonomies'][ $taxonomy->name ]['enable_show_in_rest'] ?? (bool) $taxonomy->show_in_rest,
				'enable_with_front'   => $options['taxonomies'][ $taxonomy->name ]['enable_with_front'] ?? (bool) $taxonomy->rewrite['with_front'],
			);
			$taxonomies_with_data[ $taxonomy->name ] = $data;
		}
		return $taxonomies_with_data;
	}

	/**
	 * Gets a user ID for the user.
	 *
	 * @return int user_id
	 */
	public static function get_user_id() {
		// Get user ID.
		$user_id = filter_input( INPUT_GET, 'user_id', FILTER_VALIDATE_INT );
		if ( ( 0 === $user_id || null === $user_id ) && IS_PROFILE_PAGE ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}
		return $user_id;
	}

	/**
	 * Sanitize an attribute based on type.
	 *
	 * @param array  $attributes Array of attributes.
	 * @param string $attribute  The attribute to sanitize.
	 * @param string $type       The type of sanitization you need (values can be integer, text, float, boolean, url).
	 *
	 * @return mixed Sanitized attribute. wp_error on failure.
	 */
	public static function sanitize_attribute( $attributes, $attribute, $type = 'text' ) {
		if ( isset( $attributes[ $attribute ] ) ) {
			switch ( $type ) {
				case 'raw':
					return $attributes[ $attribute ];
				case 'post_text':
				case 'post':
					return wp_kses_post( $attributes[ $attribute ] );
				case 'string':
				case 'text':
					return sanitize_text_field( $attributes[ $attribute ] );
				case 'bool':
				case 'boolean':
					return filter_var( $attributes[ $attribute ], FILTER_VALIDATE_BOOLEAN );
				case 'int':
				case 'integer':
					return absint( $attributes[ $attribute ] );
				case 'float':
					if ( is_float( $attributes[ $attribute ] ) ) {
						return $attributes[ $attribute ];
					}
					return 0;
				case 'url':
					return esc_url( $attributes[ $attribute ] );
				case 'default':
					return new \WP_Error( 'has_dlx_unknown_type', __( 'Unknown type.', 'archive-pages-pro' ) );
			}
		}
		return new \WP_Error( 'has_dlx_attribute_not_found', __( 'Attribute not found.', 'archive-pages-pro' ) );
	}

	/**
	 * Get the current admin tab.
	 *
	 * @return null|string Current admin tab.
	 */
	public static function get_admin_tab() {
		$tab = filter_input( INPUT_GET, 'tab', FILTER_DEFAULT );
		if ( $tab && is_string( $tab ) ) {
			return sanitize_text_field( sanitize_title( $tab ) );
		}
		return null;
	}

	/**
	 * Return the URL to the admin screen
	 *
	 * @param string $tab     Tab path to load.
	 * @param string $sub_tab Subtab path to load.
	 *
	 * @return string URL to admin screen. Output is not escaped.
	 */
	public static function get_settings_url( $tab = '', $sub_tab = '' ) {
		$options_url = admin_url( 'options-general.php?page=archive-pages-pro' );
		if ( ! empty( $tab ) ) {
			$options_url = add_query_arg( array( 'tab' => sanitize_title( $tab ) ), $options_url );
			if ( ! empty( $sub_tab ) ) {
				$options_url = add_query_arg( array( 'subtab' => sanitize_title( $sub_tab ) ), $options_url );
			}
		}
		return $options_url;
	}

	/**
	 * Allow display and visiblity to style attributes.
	 *
	 * @param array $css CSS rules.
	 */
	public static function safe_css( $css = array() ) {
		$css[] = 'display';
		$css[] = 'visibility';
		return $css;
	}

	/**
	 * Checks to see if an asset is activated or not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path to the asset.
	 * @param string $type Type to check if it is activated or not.
	 *
	 * @return bool true if activated, false if not.
	 */
	public static function is_activated( $path, $type = 'plugin' ) {

		// Gets all active plugins on the current site.
		$active_plugins = self::is_multisite() ? get_site_option( 'active_sitewide_plugins' ) : get_option( 'active_plugins', array() );
		if ( in_array( $path, $active_plugins, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Array data that must be sanitized.
	 *
	 * @param array $data Data to be sanitized.
	 *
	 * @return array Sanitized data.
	 */
	public static function sanitize_array_recursive( array $data ) {
		$sanitized_data = array();
		foreach ( $data as $key => $value ) {
			if ( '0' === $value ) {
				$value = 0;
			}
			if ( 'true' === $value ) {
				$value = true;
			} elseif ( 'false' === $value ) {
				$value = false;
			}
			if ( is_array( $value ) ) {
				$value                  = self::sanitize_array_recursive( $value );
				$sanitized_data[ $key ] = $value;
				continue;
			}
			if ( is_bool( $value ) ) {
				$sanitized_data[ $key ] = (bool) $value;
				continue;
			}
			if ( is_int( $value ) ) {
				$sanitized_data[ $key ] = (int) $value;
				continue;
			}
			if ( is_string( $value ) ) {
				$sanitized_data[ $key ] = sanitize_text_field( $value );
				continue;
			}
		}
		return $sanitized_data;
	}

	/**
	 * Get the post types for the plugin.
	 *
	 * @see get_post_types
	 *
	 * @return array Post types.
	 */
	public static function get_post_types( $all = false ) {
		$post_type_args = array(
			'public'      => true,
			'has_archive' => true,
			'_builtin'    => $all,
		);

		// If all, remove has_archive from the args.
		if ( $all ) {
			unset( $post_type_args['has_archive'] );
			unset( $post_type_args['public'] );
		}

		/**
		 * Filter: archive_pages_pro_post_type_args.
		 *
		 * @param array $post_type_args Post type arguments.
		 */
		$post_type_args = apply_filters( 'archive_pages_pro_post_type_args', $post_type_args );
		$post_types     = get_post_types( $post_type_args, 'objects' );
		return $post_types;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @return string plugin slug.
	 */
	public static function get_plugin_slug() {
		return dirname( plugin_basename( ARCHIVE_PAGES_PRO_FILE ) );
	}

	/**
	 * Return the basefile for the plugin.
	 *
	 * @return string base file for the plugin.
	 */
	public static function get_plugin_file() {
		return plugin_basename( ARCHIVE_PAGES_PRO_FILE );
	}

	/**
	 * Return the version for the plugin.
	 *
	 * @return float version for the plugin.
	 */
	public static function get_plugin_version() {
		return ARCHIVE_PAGES_PRO_VERSION;
	}

	/**
	 * Returns appropriate html for KSES.
	 *
	 * @param bool $svg         Whether to add SVG data to KSES.
	 * @param bool $with_tables Whether to add tables to KSES.
	 */
	public static function get_kses_allowed_html( $svg = true, $with_tables = false ) {
		$allowed_tags = wp_kses_allowed_html( 'post' );

		$allowed_tags['nav']        = array(
			'class' => array(),
		);
		$allowed_tags['a']['class'] = array();

		// Add form input fields.
		$allowed_tags['input'] = array(
			'type'        => array(),
			'class'       => array(),
			'id'          => array(),
			'name'        => array(),
			'value'       => array(),
			'placeholder' => array(),
			'required'    => array(),
			'checked'     => array(),
		);

		// Add button fields.
		$allowed_tags['button'] = array(
			'type'      => array(),
			'class'     => array(),
			'id'        => array(),
			'name'      => array(),
			'data-type' => array(),
		);

		// Add select field.
		$allowed_tags['select'] = array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
		);

		// Add options field.
		$allowed_tags['option'] = array(
			'value'    => array(),
			'selected' => array(),
		);

		if ( ! $svg && ! $with_tables ) {
			return $allowed_tags;
		}
		if ( $svg ) {
			$allowed_tags['svg'] = array(
				'xmlns'       => array(),
				'fill'        => array(),
				'viewbox'     => array(),
				'role'        => array(),
				'aria-hidden' => array(),
				'focusable'   => array(),
				'class'       => array(),
				'width'       => array(),
				'height'      => array(),
			);

			$allowed_tags['path'] = array(
				'd'       => array(),
				'fill'    => array(),
				'opacity' => array(),
			);

			$allowed_tags['g'] = array();

			$allowed_tags['circle'] = array(
				'cx'     => array(),
				'cy'     => array(),
				'r'      => array(),
				'fill'   => array(),
				'stroke' => array(),
			);

			$allowed_tags['use'] = array(
				'xlink:href' => array(),
			);

			$allowed_tags['symbol'] = array(
				'aria-hidden' => array(),
				'viewBox'     => array(),
				'id'          => array(),
				'xmls'        => array(),
			);
		}

		// Add HTML table markup.
		if ( $with_tables ) {
			$allowed_tags['html']  = array(
				'lang' => array(),
			);
			$allowed_tags['head']  = array();
			$allowed_tags['title'] = array();
			$allowed_tags['meta']  = array(
				'http-equiv' => array(),
				'content'    => array(),
				'name'       => array(),
			);
			$allowed_tags['body']  = array(
				'style' => array(),
			);
			$allowed_tags['style'] = array();
			$allowed_tags['table'] = array(
				'class'        => array(),
				'width'        => array(),
				'border'       => array(),
				'cellpadding'  => array(),
				'cellspacing'  => array(),
				'role'         => array(),
				'presentation' => array(),
				'align'        => array(),
				'bgcolor'      => array(),
			);
			$allowed_tags['tbody'] = array();
			$allowed_tags['thead'] = array();
			$allowed_tags['tr']    = array(
				'bgcolor' => array(),
				'align'   => array(),
				'style'   => array(),
			);
			$allowed_tags['th']    = array();
			$allowed_tags['td']    = array(
				'class' => array(),
				'width' => array(),
				'style' => array(),
			);
			if ( ! isset( $allowed_tags['div'] ) ) {
				$allowed_tags['div'] = array(
					'style' => array(),
					'align' => array(),
					'class' => array(),
				);
			} else {
				$allowed_tags['div']['style'] = array();
				$allowed_tags['div']['align'] = array();
				$allowed_tags['div']['class'] = array();
			}
			if ( ! isset( $allowed_tags['p'] ) ) {
				$allowed_tags['p'] = array(
					'style' => array(),
				);
			} else {
				$allowed_tags['p']['style'] = array();
			}
			$allowed_tags['h1'] = array(
				'style' => array(),
			);
			$allowed_tags['h2'] = array(
				'style' => array(),
			);
		}

		return $allowed_tags;
	}

	/**
	 * Get the plugin directory for a path.
	 *
	 * @param string $path The path to the file.
	 *
	 * @return string The new path.
	 */
	public static function get_plugin_dir( $path = '' ) {
		$dir = rtrim( plugin_dir_path( ARCHIVE_PAGES_PRO_FILE ), '/' );
		if ( ! empty( $path ) && is_string( $path ) ) {
			$dir .= '/' . ltrim( $path, '/' );
		}
		return $dir;
	}

	/**
	 * Return a plugin URL path.
	 *
	 * @param string $path Path to the file.
	 *
	 * @return string URL to to the file.
	 */
	public static function get_plugin_url( $path = '' ) {
		$dir = rtrim( plugin_dir_url( ARCHIVE_PAGES_PRO_FILE ), '/' );
		if ( ! empty( $path ) && is_string( $path ) ) {
			$dir .= '/' . ltrim( $path, '/' );
		}
		return $dir;
	}

	/**
	 * Gets the highest priority for a filter.
	 *
	 * @param int $subtract The amount to subtract from the high priority.
	 *
	 * @return int priority.
	 */
	public static function get_highest_priority( $subtract = 0 ) {
		$highest_priority = PHP_INT_MAX;
		$subtract         = absint( $subtract );
		if ( 0 === $subtract ) {
			--$highest_priority;
		} else {
			$highest_priority = absint( $highest_priority - $subtract );
		}
		return $highest_priority;
	}
}
