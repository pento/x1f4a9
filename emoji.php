<?php
/*
Plugin Name: 💩
Description: Twitters Emoji for WordPress
Version: 0.3

See https://github.com/twitter/twemoji for the source emoji
*/

class Emoji {
	public $cdn_url;

	public static function init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new Emoji();
		}

		return $instance;
	}

	public function __construct() {
		/**
		 * Filter the URL where emoji images are hosted.
		 *
		 * @since 4.2.0
		 *
		 * @param string The emoji base URL
		 */
		$this->cdn_url = apply_filters( 'emoji_url', '//s0.wp.com/wp-content/mu-plugins/emoji/twemoji/72x72/' );


		wp_register_script( 'twemoji', plugins_url( 'twemoji/twemoji.js',   __FILE__ ) );
		wp_enqueue_script(  'emoji',   plugins_url( 'emoji.js', __FILE__ ), array( 'twemoji' ) );

		wp_localize_script( 'emoji', 'EmojiSettings', array(
			'base_url' => $this->cdn_url,
		) );

		add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
		add_action( 'admin_print_styles', array( $this, 'print_styles' ) );

		add_action( 'mce_external_plugins', array( $this, 'add_mce_plugin' ) );
		add_action( 'wp_enqueue_editor',    array( $this, 'load_mce_script' ) );

		add_action( 'wp_insert_post_data', array( $this, 'filter_post_fields' ), 10, 1 );

		add_filter( 'smilies_src', array( $this, 'filter_smileys' ), 10, 2 );

		add_filter( 'the_content_feed', array( $this, 'feed_emoji' ), 10, 1 );
		add_filter( 'the_excerpt_rss',  array( $this, 'feed_emoji' ), 10, 1 );
		add_filter( 'comment_text_rss', array( $this, 'feed_emoji' ), 10, 1 );

		add_filter( 'wp_mail', array( $this, 'mail_emoji' ), 10, 1 );
	}

	public function add_mce_plugin( $plugins ) {
		$plugins['wpemoji'] = plugins_url( 'tinymce/plugin.js', __FILE__ );
		return $plugins;
	}

	public function load_mce_script( $opts ) {
		if ( $opts['tinymce'] ) {
			wp_enqueue_script( 'emoji' );
		}
	}

	public function filter_post_fields( $data ) {
		global $wpdb;
		$fields = array( 'post_title', 'post_content', 'post_excerpt' );

		foreach( $fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$charset = $wpdb->get_col_charset( $wpdb->posts, $field );
				if ( 'utf8' === $charset ) {
					$data[ $field ] = $this->wp_encode_emoji( $data[ $field ] );
				}
			}
		}
		return $data;
	}

	public function print_styles() {
?>
<style type="text/css">
img.emoji {
	border: none !important;
	box-shadow: none !important;
	height: 1em !important;
	width: 1em !important;
	margin: 0 .05em 0 .1em !important;
	vertical-align: -0.1em !important;
	background: none !important;
	padding: 0 !important;
}

img.wp-smiley {
	height: 1em;
}
</style>
<?php
	}

	/**
	 * Convert any 4 byte emoji in a string to their equivalent HTML entity.
	 * Currently, only Unicode 7 emoji are supported. Unicode 8 emoji will be added
	 * when the spec in finalised, along with the new skin-tone modifiers.
	 *
	 * This allows us to store emoji in a DB using the utf8 character set.
	 *
	 * @since 4.2.0
	 *
	 * @param string $content The content to encode.
	 * @return string The encoded content.
	 */
	public function wp_encode_emoji( $content ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$regex = '/(
			     \x23\xE2\x83\xA3               # Digits
			     [\x30-\x39]\xE2\x83\xA3
			   | \xF0\x9F[\x85-\x88][\xA6-\xBF] # Enclosed characters
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
						/*
						 * UTF-32's hex encoding is the same as HTML's hex encoding.
						 * So, by converting the emoji from UTF-8 to UTF-32, we magically
						 * get the correct hex encoding.
						 */
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

	/**
	 * Convert emoji to a static <img> link.
	 *
	 * @since 4.2.0
	 *
	 * @param string $content The content to encode.
	 * @return string The encoded content.
	 */
	public function wp_staticize_emoji( $content ) {
		$content = $this->wp_encode_emoji( $content );

		$matches = array();
		if ( preg_match_all( '/(&#x1f1(e[6-9a-f]|f[0-9a-f]);){2}/', $content, $matches ) ) {
			if ( ! empty( $matches[0] ) ) {
				foreach ( $matches[0] as $flag ) {
					$chars = str_replace( array( '&#x', ';'), '', $flag );

					list( $char1, $char2 ) = str_split( $chars, 5 );
					$entity = '<img src="https:' . $this->cdn_url . $char1 . '-' . $char2 . '.png" class="wp-smiley" style="height: 1em;" />';

					$content = str_replace( $flag, $entity, $content );
				}
			}
		}

		// Loosely match the Emoji Unicode range.
		$regex = '/(&#x[2-3][0-9a-f]{3};|&#x1f[1-6][0-9a-f]{2};)/';

		$matches = array();
		if ( preg_match_all( $regex, $content, $matches ) ) {
			if ( ! empty( $matches[1] ) ) {
				foreach ( $matches[1] as $emoji ) {
					$char = str_replace( array( '&#x', ';'), '', $emoji );
					$entity = '<img src="https:' . $this->cdn_url . $char . '.png" class="wp-smiley" style="height: 1em;" />';

					$content = str_replace( $emoji, $entity, $content );
				}
			}
		}

		return $content;
	}

	public function filter_smileys( $url, $img ) {
		switch ( $img ) {
			case 'icon_mrgreen.gif':
				return plugins_url( 'smileys/mrgreen.png', __FILE__ );
			case 'icon_neutral.gif':
				return $this->cdn_url . '1f610.png';
			case 'icon_twisted.gif':
				return $this->cdn_url . '1f608.png';
			case 'icon_arrow.gif':
				return $this->cdn_url . '27a1.png';
			case 'icon_eek.gif':
				return $this->cdn_url . '1f62f.png';
			case 'icon_smile.gif':
				return plugins_url( 'smileys/simple-smile.png', __FILE__ );
			case 'icon_confused.gif':
				return $this->cdn_url . '1f62f.png';
			case 'icon_cool.gif':
				return $this->cdn_url . '1f60e.png';
			case 'icon_evil.gif':
				return $this->cdn_url . '1f47f.png';
			case 'icon_biggrin.gif':
				return $this->cdn_url . '1f604.png';
			case 'icon_idea.gif':
				return $this->cdn_url . '1f4a1.png';
			case 'icon_redface.gif':
				return $this->cdn_url . '1f633.png';
			case 'icon_razz.gif':
				return $this->cdn_url . '1f61b.png';
			case 'icon_rolleyes.gif':
				return plugins_url( 'smileys/rolleyes.png', __FILE__ );
			case 'icon_wink.gif':
				return $this->cdn_url . '1f609.png';
			case 'icon_cry.gif':
				return $this->cdn_url . '1f625.png';
			case 'icon_surprised.gif':
				return $this->cdn_url . '1f62f.png';
			case 'icon_lol.gif':
				return $this->cdn_url . '1f604.png';
			case 'icon_mad.gif':
				return $this->cdn_url . '1f621.png';
			case 'icon_sad.gif':
				return $this->cdn_url . '1f626.png';
			case 'icon_exclaim.gif':
				return $this->cdn_url . '2757.png';
			case 'icon_question.gif':
				return $this->cdn_url . '2753.png';
			default:
				return $url;
		}
	}

	public function feed_emoji( $content ) {
		return $this->wp_staticize_emoji( $content, true );
	}

	public function mail_emoji( $mail ) {
		$mail['message'] = $this->wp_staticize_emoji( $mail['message'], true );
		return $mail;
	}
}

add_action( 'init', array( 'Emoji', 'init' ) );
