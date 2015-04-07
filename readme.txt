=== x1f4a9 ===
Contributors: pento
Donate link: http://wordpressfoundation.org/donate/
Tags: post, emoji
Requires at least: 4.2
Tested up to: 4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

If only there was a consistent emoji experience for WordPress. That would be 😎👍.

*This was an experimental plugin to develop Emoji support in WordPress. It has been superceded by WordPress 4.2, and no longer works.*

== Contributing ==

Development of this plugin is done on [Github](https://github.com/pento/x1f4a9). Pull requests are welcome.

== Changelog ==

= 0.4 =

* Add support for static emoji images in RSS and Email
* Performance improvements in TinyMCE
* Add fallbacks to Twemoji for flags only, which Firefox OS X doesn't support
* Add an emoji_url filter, for changing where emoji images are loaded from
* Fix emoji incorrectly being parsed in tags that don't support <img> children

= 0.3 =

* Only load emoji when the browser doesn't have native support
* Replace emoji with static images in RSS and email
* Remove the bonus smilies added in 0.2
* Replace some more of the smilies with emoji

= 0.2 =

* Add emoji encoding for non-utf8mb4 character sets
* Replace WordPress' existing smilies with shiny new ones

= 0.1 =

* Initial version, based on WordPress.com's emoji implementation