<?php
/*
Plugin Name: 💩
Description: Twitters Emoji for WordPress
Version: 0.1

See https://github.com/twitter/twemoji for the source emoji
*/

class Emoji {
	public static function init() {
		wp_register_script( 'twemoji', plugins_url( 'twemoji/twemoji.js',   __FILE__ ) );
		wp_enqueue_script(  'emoji',   plugins_url( 'emoji.js', __FILE__ ), array( 'twemoji' ) );

		wp_enqueue_style( 'emoji-css', plugins_url( 'emoji.css', __FILE__ ) );

		add_action( 'mce_external_plugins', array( __CLASS__, 'add_mce_plugin' ) );
		add_action( 'wp_enqueue_editor',    array( __CLASS__, 'load_mce_script' ) );

		add_action( 'wp_insert_post_data', array( __CLASS__, 'filter_post_fields' ), 10, 1 );
	}

	public static function add_mce_plugin( $plugins ) {
		$plugins['emoji'] = plugins_url( 'tinymce/plugin.js', __FILE__ );
		return $plugins;
	}

	public static function load_mce_script( $opts ) {
		if ( $opts['tinymce'] ) {
			wp_enqueue_script( 'emoji' );
		}
	}

	public static function filter_post_fields( $data ) {
		global $wpdb;
		$fields = array( 'post_title', 'post_content', 'post_excerpt' );

		foreach( $fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$charset = $wpdb->get_col_charset( $wpdb->posts, $field );
				if ( 'utf8' === $charset ) {
					$data[ $field ] = Emoji::wp_encode_emoji( $data[ $field ] );
				}
			}
		}
		return $data;
	}

	/**
	 * Convert any 4 byte emoji in a string to their equivalent HTML entity.
	 * Currently, only Unicode 7 emoji are supported. Unicode 8 emoji will be added
	 * when the spec in finalised, along with the new skin-tone modifiers.
	 *
	 * This allows us to store emoji in a DB using the utf8 character set.
	 *
	 * @since 4.2.0
	 * @param  string $content The content to encode
	 * @return string The encoded content
	 */
	public static function wp_encode_emoji( $content ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$regex = '/(
			     \x23\xE2\x83\xA3               # Digits
			     [\x30-\x39]\xE2\x83\xA3
			   | \xF0\x9F[\x85-\x88][\xB0-\xBF] # Enclosed characters
			   | \xF0\x9F[\x8C-\x97][\x80-\xBF] # Misc
			   | \xF0\x9F\x98[\x80-\xBF]        # Smilies
			   | \xF0\x9F\x99[\x80-\x8F]
			   | \xF0\x9F\x9A[\x80-\xBF]        # Transport and map symbols
			   | \xF0\x9F\x99[\x80-\x85]
			)/x';

			$matches = array();
			if ( preg_match_all( $regex, $content, $matches ) ) {
				if ( ! empty( $matches[1] ) ) {
					foreach( $matches[1] as $emoji ) {
						$unpacked = unpack( 'H*', mb_convert_encoding( $emoji, 'UTF-32', 'UTF-8' ) );
						if ( isset( $unpacked[1] ) ) {
							$entity = '&#x' . trim( $unpacked[1], '0' ) . ';';
							$content = str_replace( $emoji, $entity, $content );
						}
					}
				}
			}
		}

		return $content;
	}
}

add_action( 'init', array( 'Emoji', 'init' ) );
