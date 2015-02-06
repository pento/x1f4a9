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
}

add_action( 'init', array( 'Emoji', 'init' ) );
