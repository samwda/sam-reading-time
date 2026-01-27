<?php
/**
 * Plugin Name: Sam Reading Time
 * Plugin URI:  https://github.com/samwda/srt/
 * Description: A lightweight WordPress plugin to display the estimated reading time of posts and pages using the [sam_reading_time] shortcode.
 * Version:     2.2
 * Author:      SAM Web Design Agency
 * Author URI:  https://samwda.ir
 * License:     GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sam-reading-time
 * Requires at least: 6.3
 * Requires PHP: 7.2
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Main class for the Sam Reading Time Plugin.
 * Manages all plugin functionalities including the shortcode and settings.
 */
class Sam_Reading_Time_Plugin {

    private $schema_should_output = false;

    /**
     * Constructor.
     * Registers necessary WordPress hooks.
     */
    public function __construct() {
        // Load translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Register the shortcode
        add_shortcode( 'sam_reading_time', array( $this, 'display_reading_time_shortcode' ) );

        // Add admin menu and settings page
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'initialize_settings' ) );

        // Enqueue styles for the frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugin_styles' ) );

        // Enqueue admin styles (inline CSS for settings page)
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

        add_filter( 'manage_posts_columns', array( $this, 'add_reading_time_column' ) );
        add_filter( 'manage_pages_columns', array( $this, 'add_reading_time_column' ) );
        add_action( 'manage_posts_custom_column', array( $this, 'show_reading_time_column' ), 10, 2 );
        add_action( 'manage_pages_custom_column', array( $this, 'show_reading_time_column' ), 10, 2 );
        add_action( 'init', array( $this, 'add_cpt_reading_time_column_support' ) );
        add_filter( 'manage_edit-post_sortable_columns', array( $this, 'make_reading_time_column_sortable' ) );
        add_filter( 'manage_edit-page_sortable_columns', array( $this, 'make_reading_time_column_sortable' ) );
        add_action( 'pre_get_posts', array( $this, 'reading_time_orderby_query' ) );
        add_action( 'wp_head', array( $this, 'add_time_required_jsonld' ) );

        // Update reading time meta when posts are saved
        add_action( 'save_post', array( $this, 'update_reading_time_meta' ), 10, 2 );
    }

    /**
     * Load plugin textdomain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'sam-reading-time', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Enqueues the plugin's CSS file for the frontend.
     * (Kept empty intentionally.)
     */
    public function enqueue_plugin_styles() {
        // No external CSS file is enqueued by default.
    }

    /**
     * Enqueues the plugin's admin CSS for the settings page using admin_head and inline <style>.
     * MINIMAL, RED-THEMED POLISH ONLY.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_styles( $hook ) {
        if ( 'posts_page_sam-reading-time' !== $hook ) {
            return;
        }
        // Add minimal inline CSS to admin_head
        add_action( 'admin_head', function() {
            ?>
            <style>
            /* Minimal admin panel polish — red theme (#d00) */
            .sam-settings-container {
              background: #ffffff;
              border-radius: 8px;
              border: 1px solid #f5dcdc;
              padding: 28px;
              max-width: 760px;
              margin: 28px auto;
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
              color: #222;
            }

            .sam-settings-container h1 {
              color: #d00;
              font-size: 20px;
              margin-bottom: 10px;
              font-weight: 700;
            }

            .sam-settings-container p {
              color: #333;
              line-height: 1.6;
              margin-bottom: 10px;
            }

            .sam-settings-container input[type="text"],
            .sam-settings-container input[type="number"],
            .sam-settings-container select,
            .sam-settings-container textarea {
              border: 1px solid #f0d6d6;
              background: #fff;
              color: #111;
              border-radius: 6px;
              padding: 8px 10px;
              font-size: 14px;
              margin-bottom: 8px;
              width: 100%;
              box-sizing: border-box;
            }

            .sam-settings-container input[type="checkbox"] {
              accent-color: #d00;
              width: 16px;
              height: 16px;
              vertical-align: middle;
              margin-right: 6px;
            }

            .sam-settings-container .description {
              color: #666;
              font-size: 13px;
              margin-top: 4px;
              margin-bottom: 12px;
            }

            .sam-settings-container .usage-instructions {
              background: #fff7f7;
              border-left: 4px solid #d00;
              border-radius: 6px;
              padding: 12px;
              margin-top: 14px;
            }

            .sam-settings-container code, .sam-settings-container pre {
              background: #fff;
              color: #d00;
              border: 1px solid #f1d3d3;
              border-radius: 4px;
              padding: 6px 8px;
              font-size: 13px;
              font-family: "Fira Mono", "Consolas", "Menlo", monospace;
            }

            .sam-settings-container .button-primary {
              background: #d00;
              border: none;
              color: #fff;
              font-weight: 700;
              border-radius: 6px;
              padding: 9px 18px;
              font-size: 14px;
              cursor: pointer;
            }

            .sam-settings-container .button-primary:hover {
              background: #b30000;
            }

            @media (max-width: 600px) {
              .sam-settings-container { padding: 18px; margin: 18px 12px; }
            }
            </style>
            <?php
        } );
    }

    /**
     * Counts the number of words in a given text content.
     * This function is optimized for better support of Unicode languages (like Persian).
     *
     * @param string $content The text content to count words from.
     * @return int The number of words.
     */
    private function count_words( $content ) {
        // 1. Remove other shortcodes from the content to prevent them from being counted as words.
        $content = strip_shortcodes( $content );

        // 2. Remove all HTML tags from the content.
        $content = wp_strip_all_tags( $content );

        // 3. Replace special characters (like newlines, tabs) and multiple spaces with a single space.
        $content = preg_replace( '/\s+/u', ' ', $content ); // Use /u for Unicode support
        $content = trim( $content ); // Remove leading and trailing spaces.

        // 4. Split words based on whitespace and count them.
        $words = preg_split( '/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY );

        return is_array( $words ) ? count( $words ) : 0;
    }

    /**
     * Callback function for the [sam_reading_time] shortcode.
     * Calculates and displays the estimated reading time based on global settings.
     *
     * @param array $atts Attributes passed to the shortcode (only 'type' is considered for content source).
     * @return string Formatted reading time HTML.
     */
    public function display_reading_time_shortcode( $atts ) {
        $this->schema_should_output = true;

        // Get global settings directly. Shortcode attributes are ignored for most settings.
        $words_per_minute        = get_option( 'sam_reading_time_words_per_minute', 200 );
        /* translators: %1$s: The number of minutes. */
        $singular_format         = get_option( 'sam_reading_time_singular_format', esc_html__( '%1$s minute read', 'sam-reading-time' ) );
        /* translators: %1$s: The number of minutes. */
        $plural_format           = get_option( 'sam_reading_time_plural_format', esc_html__( '%1$s minutes read', 'sam-reading-time' ) );
        $less_than_a_minute_format = get_option( 'sam_reading_time_less_than_a_minute_format', esc_html__( 'Less than a minute read', 'sam-reading-time' ) );
        $prefix_text             = get_option( 'sam_reading_time_prefix_text', '' );
        $suffix_text             = get_option( 'sam_reading_time_suffix_text', '' );
        $wrapper_tag             = get_option( 'sam_reading_time_wrapper_tag', 'span' );
        $hide_if_less_than_a_minute = get_option( 'sam_reading_time_hide_if_less_than_a_minute', false );
        $enable_debug_output     = get_option( 'sam_reading_time_enable_debug_output', false );

        $content_type            = isset( $atts['type'] ) && in_array( $atts['type'], array( 'content', 'excerpt' ), true ) ? $atts['type'] : 'content';

        global $post;
        $post_id = null;
        $content_to_count = '';

        if ( is_singular() || ( function_exists( 'get_the_ID' ) && get_the_ID() ) ) {
            $post_id = get_the_ID();
        } elseif ( is_a( $post, 'WP_Post' ) ) {
            $post_id = $post->ID;
        }

        if ( ! $post_id ) {
            if ( $enable_debug_output ) {
                $debug_message = esc_html__( 'Sam Reading Time Debug: Post ID not found. Shortcode might be used in an unsupported context (e.g., outside the main loop, non-singular page).', 'sam-reading-time' );
                return '<span style="color: red; direction:ltr; text-align:left; display:block; padding: 5px; border: 1px dashed red;">' . $debug_message . '</span>';
            }
            return '';
        }

        if ( 'excerpt' === $content_type ) {
            $content_to_count = get_the_excerpt( $post_id );
        } else {
            $content_to_count = get_post_field( 'post_content', $post_id );
        }

        if ( empty( $content_to_count ) ) {
            if ( $enable_debug_output ) {
                return '<span style="color: orange; direction:ltr; text-align:left; display:block; padding: 5px; border: 1px dashed orange;">' . sprintf( esc_html__( 'Sam Reading Time Debug: No content found for Post ID %1$s.', 'sam-reading-time' ), absint( $post_id ) ) . '</span>';
            }
            return '';
        }

        $word_count = $this->count_words( $this->clean_content_for_reading_time( $content_to_count ) );

        if ( $word_count === 0 ) {
            if ( $enable_debug_output ) {
                return '<span style="color: orange; direction:ltr; text-align:left; display:block; padding: 5px; border: 1px dashed orange;">' . sprintf( esc_html__( 'Sam Reading Time Debug: Word count is 0 for Post ID %1$s.', 'sam-reading-time' ), absint( $post_id ) ) . '</span>';
            }
            return '';
        }

        $valid_words_per_minute = max( 1, (int) $words_per_minute );
        $raw_reading_time = $word_count / $valid_words_per_minute;

        $formatted_reading_time = '';

        if ( $raw_reading_time < 1 ) {
            if ( $hide_if_less_than_a_minute ) {
                return '';
            }
            $formatted_reading_time = $less_than_a_minute_format;
        } else {
            $display_time_value = (int) ceil( $raw_reading_time );
            if ( $display_time_value === 1 ) {
                $formatted_reading_time = sprintf( $singular_format, $display_time_value );
            } else {
                $formatted_reading_time = sprintf( $plural_format, $display_time_value );
            }
        }

        $final_output = $prefix_text . $formatted_reading_time . $suffix_text;

        if ( $enable_debug_output ) {
            $final_output .= ' <span style="font-size:0.8em; opacity:0.7; direction:ltr; text-align:left; background-color: #f0f0f0; padding: 2px 5px; border-radius: 3px;">(' . sprintf( esc_html__( 'Words: %1$s, Raw Time: %2$s', 'sam-reading-time' ), absint( $word_count ), number_format( $raw_reading_time, 2 ) ) . ')</span>';
        }

        $classes = array( 'reading-time' );
        $class_attr = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';

        return '<' . esc_attr( $wrapper_tag ) . ' ' . $class_attr . '>' . $final_output . '</' . esc_attr( $wrapper_tag ) . '>';
    }

    /**
     * Adds the plugin's settings page to the WordPress admin menu.
     * It's added as a submenu under the 'Posts' menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php',
            esc_html__( 'Sam Reading Time Settings', 'sam-reading-time' ),
            esc_html__( 'Sam Reading Time', 'sam-reading-time' ),
            'manage_options',
            'sam-reading-time',
            array( $this, 'options_page_html' )
        );
    }

    /**
     * Initializes and registers plugin settings.
     */
    public function initialize_settings() {
        add_settings_section(
            'sam_reading_time_plugin_section',
            esc_html__( 'General Settings', 'sam-reading-time' ),
            array( $this, 'reading_time_settings_section_callback' ),
            'sam-reading-time'
        );

        add_settings_field(
            'sam_reading_time_words_per_minute',
            esc_html__( 'Words Per Minute (WPM)', 'sam-reading-time' ),
            array( $this, 'words_per_minute_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_words_per_minute',
            array(
                'type'              => 'integer',
                'sanitize_callback' => array( $this, 'sanitize_words_per_minute' ),
                'default'           => 200,
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_singular_format',
            esc_html__( 'Singular Format (e.g., 1 minute)', 'sam-reading-time' ),
            array( $this, 'singular_format_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_singular_format',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( '%1$s minute read', 'sam-reading-time' ),
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_plural_format',
            esc_html__( 'Plural Format (e.g., 2 minutes)', 'sam-reading-time' ),
            array( $this, 'plural_format_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_plural_format',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( '%1$s minutes read', 'sam-reading-time' ),
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_less_than_a_minute_format',
            esc_html__( 'Less Than A Minute Format', 'sam-reading-time' ),
            array( $this, 'less_than_a_minute_format_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_less_than_a_minute_format',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( 'Less than a minute read', 'sam-reading-time' ),
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_hide_if_less_than_a_minute',
            esc_html__( 'Hide if Less Than A Minute', 'sam-reading-time' ),
            array( $this, 'hide_if_less_than_a_minute_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_hide_if_less_than_a_minute',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_prefix_text',
            esc_html__( 'Prefix Text', 'sam-reading-time' ),
            array( $this, 'prefix_text_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_prefix_text',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_suffix_text',
            esc_html__( 'Suffix Text', 'sam-reading-time' ),
            array( $this, 'suffix_text_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_suffix_text',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_wrapper_tag',
            esc_html__( 'Wrapper HTML Tag', 'sam-reading-time' ),
            array( $this, 'wrapper_tag_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_wrapper_tag',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_wrapper_tag' ),
                'default'           => 'span',
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_enable_debug_output',
            esc_html__( 'Enable Debug Output', 'sam-reading-time' ),
            array( $this, 'enable_debug_output_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_enable_debug_output',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
                'show_in_rest'      => false,
            )
        );

        add_settings_field(
            'sam_reading_time_enable_schema_time_required',
            esc_html__( 'Enable Schema.org timeRequired', 'sam-reading-time' ),
            array( $this, 'enable_schema_time_required_callback' ),
            'sam-reading-time',
            'sam_reading_time_plugin_section'
        );
        register_setting(
            'sam_reading_time',
            'sam_reading_time_enable_schema_time_required',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true,
                'show_in_rest' => false,
            )
        );
    }

    /**
     * Callback for the settings section description.
     */
    public function reading_time_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure the general display settings for the Sam Reading Time plugin here.', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the Words Per Minute (WPM) settings field.
     */
    public function words_per_minute_callback() {
        $wpm = get_option( 'sam_reading_time_words_per_minute', 200 );
        echo '<input type="number" name="sam_reading_time_words_per_minute" value="' . absint( $wpm ) . '" min="1" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Average number of words a person reads per minute.', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Sanitization callback for Words Per Minute (WPM).
     *
     * @param int $input The input value.
     * @return int The sanitized value.
     */
    public function sanitize_words_per_minute( $input ) {
        $input = intval( $input );
        return ( $input > 0 ) ? $input : 200; // Ensure the value is positive.
    }

    /**
     * Callback for the Singular Format settings field.
     */
    public function singular_format_callback() {
        $format = get_option( 'sam_reading_time_singular_format', esc_html__( '%1$s minute read', 'sam-reading-time' ) );
        echo '<input type="text" name="sam_reading_time_singular_format" value="' . esc_attr( $format ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Use %s for the reading time. Example: "1 minute read"', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the Plural Format settings field.
     */
    public function plural_format_callback() {
        $format = get_option( 'sam_reading_time_plural_format', esc_html__( '%1$s minutes read', 'sam-reading-time' ) );
        echo '<input type="text" name="sam_reading_time_plural_format" value="' . esc_attr( $format ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Use %s for the reading time. Example: "2 minutes read"', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the "Less Than A Minute" Format settings field.
     */
    public function less_than_a_minute_format_callback() {
        $format = get_option( 'sam_reading_time_less_than_a_minute_format', esc_html__( 'Less than a minute read', 'sam-reading-time' ) );
        echo '<input type="text" name="sam_reading_time_less_than_a_minute_format" value="' . esc_attr( $format ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Text to display for articles that take less than one minute to read.', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for Hide if Less Than A Minute settings field.
     */
    public function hide_if_less_than_a_minute_callback() {
        $hide = get_option( 'sam_reading_time_hide_if_less_than_a_minute', false );
        echo '<input type="checkbox" name="sam_reading_time_hide_if_less_than_a_minute" value="1" ' . checked( 1, $hide, false ) . ' />';
        echo '<p class="description">' . esc_html__( 'Check this box to hide the reading time output if it is less than one minute.', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the Prefix Text settings field.
     */
    public function prefix_text_callback() {
        $prefix = get_option( 'sam_reading_time_prefix_text', '' );
        echo '<input type="text" name="sam_reading_time_prefix_text" value="' . esc_attr( $prefix ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Text to display before the reading time. Example: "Estimated reading time: "', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the Suffix Text settings field.
     */
    public function suffix_text_callback() {
        $suffix = get_option( 'sam_reading_time_suffix_text', '' );
        echo '<input type="text" name="sam_reading_time_suffix_text" value="' . esc_attr( $suffix ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Text to display after the reading time. Example: " (approx.)"', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the Wrapper HTML Tag settings field.
     */
    public function wrapper_tag_callback() {
        $tag = get_option( 'sam_reading_time_wrapper_tag', 'span' );
        ?>
        <select name="sam_reading_time_wrapper_tag">
            <option value="span" <?php selected( $tag, 'span' ); ?>>span</option>
            <option value="div" <?php selected( $tag, 'div' ); ?>>div</option>
            <option value="p" <?php selected( $tag, 'p' ); ?>>p</option>
            <option value="strong" <?php selected( $tag, 'strong' ); ?>>strong</option>
            <option value="em" <?php selected( $tag, 'em' ); ?>>em</option>
        </select>
        <p class="description"><?php echo esc_html__( 'Choose the HTML tag to wrap the reading time output. This affects its display behavior.', 'sam-reading-time' ) . '<br>' . esc_html__( 'For inline display, use "span". For block display, use "div" or "p".', 'sam-reading-time' ); ?></p>
        <?php
    }

    /**
     * Sanitization callback for Wrapper HTML Tag.
     *
     * @param string $input The input value.
     * @return string The sanitized value.
     */
    public function sanitize_wrapper_tag( $input ) {
        $allowed_tags = array( 'span', 'div', 'p', 'strong', 'em' );
        return in_array( $input, $allowed_tags, true ) ? sanitize_key( $input ) : 'span';
    }

    /**
     * Callback for the Enable Debug Output checkbox.
     */
    public function enable_debug_output_callback() {
        $enable_debug = get_option( 'sam_reading_time_enable_debug_output', false );
        echo '<input type="checkbox" name="sam_reading_time_enable_debug_output" value="1" ' . checked( 1, $enable_debug, false ) . ' />';
        echo '<p class="description">' . esc_html__( 'Check this box to display word count and raw reading time next to the output for debugging purposes.', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Callback for the Enable Schema.org timeRequired checkbox.
     */
    public function enable_schema_time_required_callback() {
        $enable = get_option( 'sam_reading_time_enable_schema_time_required', true );
        echo '<input type="checkbox" name="sam_reading_time_enable_schema_time_required" value="1" ' . checked( 1, $enable, false ) . ' />';
        echo '<p class="description">' . esc_html__( 'Enable Schema.org timeRequired JSON-LD markup for Google Rich Snippets. Note: Markup will only be output if the [sam_reading_time] shortcode is used in the post content.', 'sam-reading-time' ) . '</p>';
    }

    /**
     * Displays the HTML for the plugin's settings page.
     */
    public function options_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <div class="sam-settings-container">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'sam_reading_time' );
                    do_settings_sections( 'sam-reading-time' );
                    submit_button( esc_html__( 'Save Changes', 'sam-reading-time' ) );
                    ?>
                </form>

                <div class="usage-instructions">
                    <h2><?php esc_html_e( 'How to Use the Sam Reading Time Plugin', 'sam-reading-time' ); ?></h2>
                    <p><?php esc_html_e( 'This plugin allows you to display the estimated reading time of your posts and pages using a simple shortcode. All display formats and calculation settings are managed from this page.', 'sam-reading-time' ); ?></p>

                    <h3><?php esc_html_e( 'Basic Usage', 'sam-reading-time' ); ?></h3>
                    <p><?php esc_html_e( 'Simply add the following shortcode anywhere in your post or page content:', 'sam-reading-time' ); ?></p>
                    <p><code>[sam_reading_time]</code></p>
                    <p><?php esc_html_e( 'This will display the reading time based on the global settings configured above.', 'sam-reading-time' ); ?></p>

                    <h3><?php esc_html_e( 'Custom Styling', 'sam-reading-time' ); ?></h3>
                    <p><?php esc_html_e( 'The output of the shortcode is wrapped in an HTML tag with the default class ', 'sam-reading-time' ); ?><code>.reading-time</code>.
                    <?php esc_html_e( 'For custom styling, please use the WordPress Customizer (Appearance > Customize > Additional CSS).', 'sam-reading-time' ); ?></p>
                    <pre><code>.reading-time {
    font-weight: bold;
    color: #007bff;
    font-size: 0.95em;
    margin-right: 10px;
    padding: 5px 10px;
    background-color: #f0f8ff;
    border-radius: 5px;
}</code></pre>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Adds support for reading time column in custom post types.
     */
    public function add_cpt_reading_time_column_support() {
        $post_types = get_post_types( [ 'public' => true ], 'names' );
        foreach ( $post_types as $type ) {
            if ( ! in_array( $type, array( 'post', 'page' ), true ) ) {
                add_filter( "manage_{$type}_columns", array( $this, 'add_reading_time_column' ) );
                add_action( "manage_{$type}_custom_column", array( $this, 'show_reading_time_column' ), 10, 2 );
                add_filter( "manage_edit-{$type}_sortable_columns", array( $this, 'make_reading_time_column_sortable' ) );
            }
        }
    }

    /**
     * Adds the reading time column to the posts and pages list.
     */
    public function add_reading_time_column( $columns ) {
        $columns['reading_time'] = __( 'Reading Time', 'sam-reading-time' );
        return $columns;
    }

    /**
     * Displays the reading time in the custom column for posts and pages.
     */
    public function show_reading_time_column( $column, $post_id ) {
        if ( $column === 'reading_time' ) {
            $minutes = (int) $this->get_reading_time( $post_id ); // integer
            echo $minutes ? esc_html( $minutes . ' min' ) : '';
        }
    }

    /**
     * Makes the reading time column sortable.
     */
    public function make_reading_time_column_sortable( $columns ) {
        $columns['reading_time'] = 'reading_time';
        return $columns;
    }

    /**
     * Modifies the query to sort by reading time (stored in postmeta).
     */
    public function reading_time_orderby_query( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }
        $orderby = $query->get( 'orderby' );
        if ( 'reading_time' === $orderby ) {
            $query->set( 'meta_key', 'sam_reading_time_minutes' );
            $query->set( 'orderby', 'meta_value_num' );
        }
    }

    /**
     * Removes shortcodes, images, videos, and HTML tags for accurate reading time calculation.
     */
    private function clean_content_for_reading_time( $content ) {
        $content = strip_shortcodes( $content );
        $content = preg_replace( '/<pre.*?<\/pre>|<code.*?<\/code>/is', '', $content );
        $content = preg_replace( '/<img[^>]+>|<video.*?<\/video>/is', '', $content );
        $content = wp_strip_all_tags( $content );
        $content = preg_replace( '/\s+/u', ' ', $content );
        return trim( $content );
    }

    /**
     * Retrieves the reading time from post meta or calculates it if not present.
     *
     * IMPORTANT: This now RETURNS an integer number of minutes (0 if none).
     *
     * @param int $post_id
     * @return int Minutes (integer)
     */
    public function get_reading_time( $post_id ) {
        $meta = get_post_meta( $post_id, 'sam_reading_time_minutes', true );
        if ( $meta !== '' && $meta !== false ) {
            return (int) $meta;
        }

        $content = $this->get_translated_content( $post_id );
        $word_count = $this->count_words( $this->clean_content_for_reading_time( $content ) );
        $wpm = get_option( 'sam_reading_time_words_per_minute', 200 );
        $minutes = $word_count ? (int) ceil( $word_count / max( 1, (int) $wpm ) ) : 0;

        update_post_meta( $post_id, 'sam_reading_time_minutes', $minutes );

        return $minutes;
    }

    /**
     * Updates the reading time post meta when a post is saved.
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function update_reading_time_meta( $post_id, $post = null ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( ! $post ) {
            $post = get_post( $post_id );
        }

        $post_type = get_post_type( $post_id );
        if ( ! $post_type || ! post_type_supports( $post_type, 'editor' ) ) {
            return;
        }

        $content = $this->get_translated_content( $post_id );
        $word_count = $this->count_words( $this->clean_content_for_reading_time( $content ) );
        $wpm = get_option( 'sam_reading_time_words_per_minute', 200 );
        $minutes = $word_count ? (int) ceil( $word_count / max( 1, (int) $wpm ) ) : 0;

        update_post_meta( $post_id, 'sam_reading_time_minutes', $minutes );
    }

    /**
     * Adds JSON-LD Schema.org markup to the head of each post for reading time.
     */
    public function add_time_required_jsonld() {
        $enable = get_option( 'sam_reading_time_enable_schema_time_required', true );
        if ( ! $enable || ! $this->schema_should_output ) {
            return;
        }
        if ( is_singular() ) {
            global $post;
            if ( ! $post instanceof WP_Post ) {
                return;
            }
            $minutes = (int) $this->get_reading_time( $post->ID );
            if ( $minutes <= 0 ) {
                return;
            }
            $iso_duration = 'PT' . $minutes . 'M';
            echo '<script type="application/ld+json">' . json_encode( array(
                "@context" => "https://schema.org",
                "@type" => "Article",
                "timeRequired" => $iso_duration,
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
        }
    }

    /**
     * Retrieves the translated content for compatibility with Polylang and WPML.
     */
    public function get_translated_content( $post_id ) {
        if ( function_exists( 'pll_get_post' ) ) {
            $lang = pll_current_language();
            $translated_id = pll_get_post( $post_id, $lang );
            if ( $translated_id ) {
                $post = get_post( $translated_id );
                return $post ? $post->post_content : '';
            }
        }
        if ( function_exists( 'icl_object_id' ) ) {
            $lang = apply_filters( 'wpml_current_language', NULL );
            $translated_id = icl_object_id( $post_id, get_post_type( $post_id ), true, $lang );
            if ( $translated_id ) {
                $post = get_post( $translated_id );
                return $post ? $post->post_content : '';
            }
        }
        $post = get_post( $post_id );
        return $post ? $post->post_content : '';
    }
}

// Create an instance of the plugin class to activate functionalities.
new Sam_Reading_Time_Plugin();
