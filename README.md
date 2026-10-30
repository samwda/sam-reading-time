# ⏱️ Sam Reading Time (SRT) – WordPress Plugin

[![Downloads](https://img.shields.io/wordpress/plugin/dt/sam-reading-time.svg?style=flat-square)](https://wordpress.org/plugins/sam-reading-time/)
[![Rating](https://img.shields.io/wordpress/plugin/rating/sam-reading-time.svg?style=flat-square)](https://wordpress.org/plugins/sam-reading-time/)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/samwda/srt?style=flat-square)](https://github.com/samwda/srt/releases)

**Sam Reading Time (SRT)** is a lightweight WordPress plugin that estimates and displays how long it takes to read a post or page. It uses a simple shortcode and can also output Schema.org `timeRequired` JSON-LD on eligible singular content.

![Plugin Banner](https://ps.w.org/sam-reading-time/assets/banner-1544x500.png)

---

## ✨ Features

- Estimate reading time from the cleaned text content of a post or page.
- Set a custom reading speed; the default is **200 words per minute (WPM)**.
- Display singular, plural, and less-than-a-minute formats.
- Add optional prefix and suffix text, and choose the HTML wrapper tag.
- Optionally hide the output when the estimated time is less than one minute.
- Use `[sam_reading_time]` or calculate from an excerpt with `[sam_reading_time type="excerpt"]`.
- Add a Reading Time column to supported post-type administration lists and sort by it.
- Cache calculated values per post and refresh them when the content or WPM changes.
- Recalculate existing entries in bounded batches when the WPM setting changes.
- Optionally output Schema.org `timeRequired` JSON-LD for eligible singular content.
- Show an administration notice when Yoast SEO, Rank Math, or All in One SEO is detected while this plugin's schema output is enabled.
- Translation-ready and suitable for multilingual sites where each translation is stored as its own WordPress post.
- Includes an optional debug output for inspecting the word count and raw reading-time value.

## 📋 Requirements

- **WordPress:** 6.3 or later
- **PHP:** 7.2 or later

## 🔧 Installation

1. Download the plugin ZIP, or clone the repository into:

   `wp-content/plugins/sam-reading-time/`

2. Activate **Sam Reading Time** from **Plugins → Installed Plugins** in the WordPress administration area.
3. Open **Posts → Sam Reading Time** to configure the reading speed and display options.

You can also install it from the WordPress Plugin Directory by navigating to **Plugins → Add New**, searching for **Sam Reading Time**, and selecting **Install Now** followed by **Activate**.

## 🧩 Usage

Add the shortcode to a post or page:

```text
[sam_reading_time]
```

To estimate the excerpt instead of the main post content:

```text
[sam_reading_time type="excerpt"]
```

The output format is configurable. With the default settings, it will look similar to:

```text
4 minutes read
```

The shortcode can be used in post or page content, supported custom post type content, and template files where a current WordPress post is available.

Example for a PHP template:

```php
echo do_shortcode( '[sam_reading_time]' );
```

The shortcode relies on the current post context. When calling it from a custom template or loop, make sure the global/current post is set correctly.

## 🧠 How Reading Time Is Calculated

1. The plugin removes shortcodes, HTML tags, and selected non-reading content such as code blocks, tables, images, and videos.
2. It counts the remaining whitespace-separated words.
3. The word count is divided by the configured WPM value. The default is **200 WPM**.
4. Results of one minute or more are rounded up to the next whole minute.

For example:

- 765 words ÷ 200 WPM = 3.825 minutes
- Rounded up = **4 minutes**

For content estimated at less than one minute, the plugin displays the configured less-than-a-minute text, unless the hide option is enabled.

### Cache and recalculation

Calculated minutes are stored per post. The cache is checked against the post's content fingerprint and the current WPM setting. Saving a post refreshes its calculation, and changing WPM triggers a recalculation of eligible public post types in batches. Older cache entries are refreshed when they are next requested.

## 🌍 SEO & Structured Data

When enabled, SRT outputs Schema.org `timeRequired` JSON-LD in the document head for eligible singular content with a positive reading-time value. The output does **not** depend on placing the shortcode in the post content.

The schema type is selected according to the WordPress object:

- `Article` for standard posts.
- `WebPage` for standard pages.
- `CreativeWork` for supported custom post types.

Attachments, Elementor library entries, WooCommerce products, and post types without editor support are excluded from this output.

SRT can display an administration warning when Yoast SEO, Rank Math, or All in One SEO is detected. These plugins may output their own structured data, so review your site's final markup and disable SRT's schema option if it would create duplicate or conflicting data.

**Important:** Structured data does not guarantee that a search engine will display a rich result.

## 🌐 Multilingual Support

SRT calculates the reading time from the `post_content` belonging to the current WordPress post ID. This approach supports multilingual setups such as Polylang and WPML when each language version is stored as a separate WordPress post. It does not replace or automatically select another language's translation; each translated post is calculated independently.

## ⚙️ Settings

The settings page provides controls for:

- Words per minute (WPM).
- Singular, plural, and less-than-a-minute text formats.
- Hiding results below one minute.
- Prefix and suffix text.
- The output wrapper (`span`, `div`, `p`, `strong`, or `em`).
- Debug output.
- Schema.org `timeRequired` output.

## 🌍 WordPress.org Repository

Sam Reading Time is available in the [WordPress Plugin Directory](https://wordpress.org/plugins/sam-reading-time/).

## 🛡 License

Released under the **GPLv2 or later** license.

## 👨‍💻 Author

**SAM Web Design Agency**  
Website: [samwda.ir](https://samwda.ir)  
GitHub: [github.com/samwda](https://github.com/samwda)

## 💬 Feedback & Contributions

Issues and pull requests are welcome. For major changes, please open an issue first so the proposed change can be discussed.

---

Enjoy a faster, cleaner way to show estimated reading time with **SRT** ⏱️
