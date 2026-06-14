=== Sam Reading Time ===
Contributors: samwda, smahjoob
Donate link: https://samwda.ir
Tags: reading time, estimated reading time, shortcode, post meta, simple plugin
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display estimated reading time for your posts using a clean shortcode. Includes a lightweight settings panel under the "Posts" menu.

== Description ==

**Sam Reading Time (SRT)** is a lightweight and efficient plugin to show estimated reading time in WordPress posts using the `[sam_reading_time]` shortcode.

Includes a minimal settings page directly accessible under the "Posts" admin menu.

**Features include:**
- `[sam_reading_time]` shortcode for displaying reading time
- Settings panel for WPM (words per minute) speed and output format
- Clean output: "Reading Time: 4 minutes"
- Compatible with all post types
- Easy to use and theme-friendly
- No external dependencies or JS
-SEO & Rich Snippets
-Multilingual Support

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to `Posts > Reading Time Settings` to customize behavior.
4. Use `[sam_reading_time]` shortcode in posts or pages to show the reading time.

== Usage ==

Insert the shortcode anywhere inside your post or page content:

    [sam_reading_time]

Or use it in template files like so:

    echo do_shortcode('[sam_reading_time]');

Settings can be accessed via **Posts > Reading Time Settings**, where you can adjust:

- Words-per-minute speed
- Prefix/suffix text around reading time

== Frequently Asked Questions ==

= Can I change the reading speed? =
Yes. You can adjust the WPM value from the settings page.

= Where is the settings page? =
Under the WordPress Dashboard menu: `Posts > Reading Time Settings`.

= Can I use it in custom post types? =
Yes, as long as they support `the_content`.

== Screenshots ==

1. Reading time output in a post.
2. Simple settings panel under "Posts".

== Changelog ==

= 2.3 =
* Better text parsing for precise reading time estimation and minor bug fixes.

= 2.2 =
* Improved performance by storing reading time as post meta instead of calculating on every request.
* Faster and more reliable sorting of reading time column in admin post lists.
* Added proper text domain loading (sam-reading-time) for full translation support.
* More stable and accurate Schema.org timeRequired JSON-LD output.
* Minor UI refinements in admin settings page with a cleaner, more minimal red theme.
* Better handling of translated content for Polylang and WPML.

= 2.0 =
* Added admin post list column to display reading time for all post types.
* Added sorting capability for reading time column.
* Accurate reading time calculation excluding code blocks, images, videos, and HTML tags.
* Full support for Custom Post Types (CPT).
* Added Schema.org `timeRequired` JSON-LD markup for SEO Rich Snippets.
* Shortcode-aware schema: schema markup only added if [sam_reading_time] is used.
* Full compatibility with Polylang and WPML for translated content.

= 1.0 =
* Initial public release with shortcode and settings page.

== Upgrade Notice ==
= 2.0.0 =
This update introduces a major upgrade:

* Admin post list column for reading time with sorting.
* Schema.org `timeRequired` JSON-LD for Google Rich Snippets.
* Full compatibility with Polylang and WPML.

Make sure to review the new settings after upgrading!

= 1.0 =
First stable release of Sam Reading Time (SRT).

== Credits ==

Developed by SAM Web Design Agency – https://samwda.ir

== License ==

This plugin is licensed under the GPLv2 or later.
See https://www.gnu.org/licenses/gpl-2.0.html for details.
