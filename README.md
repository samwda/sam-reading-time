# ⏱️ Sam Reading Time (SRT) – WordPress Plugin
[![Downloads](https://img.shields.io/wordpress/plugin/dt/sam-reading-time.svg?style=flat-square)](https://wordpress.org/plugins/sam-reading-time/)
[![Rating](https://img.shields.io/wordpress/plugin/rating/sam-reading-time.svg?style=flat-square)](https://wordpress.org/plugins/sam-reading-time/)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/samwda/srt?style=flat-square)](https://github.com/samwda/srt/releases)

**Sam Reading Time (SRT)** is a super lightweight WordPress plugin that calculates and displays the estimated reading time for your posts and pages using a simple shortcode.

![Plugin-Banner](https://ps.w.org/sam-reading-time/assets/banner-1544x500.png)
---

## ✨ Features

- Estimate reading time based on word count
- Lightweight and easy to use
- Works via `[sam_reading_time]` shortcode
- No settings page or database queries
- Clean output – fully theme-compatible
- Translation-ready and extendable

---

## 🔧 Installation

1. Download the plugin ZIP or clone the repository into your WordPress plugin directory:  
   `/wp-content/plugins/sam-reading-time/`

2. Activate the plugin from the WordPress admin dashboard.

---

## 🧩 Usage

To display the reading time anywhere in your post, simply add the following shortcode:

```
[sam_reading_time]
```

This will output something like:

> Reading Time: 4 minutes

You can place this shortcode in:

- Post or page content
- Custom post types
- Template files (using `do_shortcode()`)

Example for PHP templates:

```php
echo do_shortcode('[sam_reading_time]');
```

---

## 🧠 How Reading Time Is Calculated

- The plugin counts all words in the current post or page content.
- Default reading speed is **200 words per minute**.
- The number of words is divided by 200, then rounded up to the nearest full minute.

For example:

- 765 words / 200 = 3.825
- Rounded = 4 minutes

---

## 🌍 SEO & Rich Snippets

- Supports Schema.org `timeRequired` JSON-LD for Google Rich Snippets.
- Can be toggled on/off in settings.
- Enhances search visibility and user engagement.

---

## 🌐 Multilingual Support

- Fully compatible with Polylang and WPML.
- Calculates reading time correctly for translated content.

---

## 🌍 WordPress.org Repository

Sam Reading Time is officially listed in the [WordPress Plugin Directory](https://wordpress.org/plugins/sam-reading-time/).

### 📦 Install from WordPress Admin

1. Navigate to **Plugins → Add New**.
2. Search for **Sam Reading Time**.
3. Click **Install Now**, then **Activate**.

---

## 🛡 License

Released under the **GPLv2 License**  

---

## 👨‍💻 Author

**SAM Web Design Agency**  
🔗 Website: [samwda.ir](https://samwda.ir)  
📦 GitHub: [github.com/samwda](https://github.com/samwda)

---

## 💬 Feedback & Contributions

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

---

Enjoy fast, clean reading time calculation with **SRT** ⏱✨
