<?php
/**
 * Central reading-time calculation engine for Sam Reading Time.
 *
 * @package SamReadingTime
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SRT_Reading_Engine {

    /**
     * Increment this whenever calculation semantics change.
     *
     * @var int
     */
    const ALGORITHM_VERSION = 1;

    /**
     * Return reading metrics for a post or its excerpt.
     *
     * @param int    $post_id             WordPress post ID.
     * @param string $content_type       Either "content" or "excerpt".
     * @param bool   $force_recalculation Ignore a valid cache entry.
     * @return array
     */
    public static function get_metrics( $post_id, $content_type = 'content', $force_recalculation = false ) {
        $post = get_post( $post_id );

        if ( ! $post instanceof WP_Post ) {
            return self::empty_metrics( $post_id, $content_type );
        }

        if ( ! in_array( $content_type, array( 'content', 'excerpt' ), true ) ) {
            $content_type = 'content';
        }

        $content = ( 'excerpt' === $content_type )
            ? get_the_excerpt( $post->ID )
            : (string) $post->post_content;

        return self::calculate( $post->ID, $content, $content_type, $force_recalculation );
    }

    /**
     * Calculate metrics from a content string and persist a versioned cache.
     *
     * @param int    $post_id       WordPress post ID.
     * @param string $content       Raw content.
     * @param string $content_type  Cache context.
     * @param bool   $force         Ignore a valid cache entry.
     * @return array
     */
    public static function calculate( $post_id, $content, $content_type = 'content', $force = false ) {
        $post_id      = absint( $post_id );
        $content_type = ( 'excerpt' === $content_type ) ? 'excerpt' : 'content';
        $content      = (string) $content;
        $wpm          = max( 1, (int) get_option( 'sam_reading_time_words_per_minute', 200 ) );
        $hash         = hash( 'sha256', $content_type . "\0" . $content );
        $keys         = self::get_cache_keys( $content_type );

        if ( ! $force ) {
            $cached_minutes = get_post_meta( $post_id, $keys['minutes'], true );
            $cached_wpm     = (int) get_post_meta( $post_id, $keys['wpm'], true );
            $cached_hash    = (string) get_post_meta( $post_id, $keys['hash'], true );
            $cached_version = (int) get_post_meta( $post_id, $keys['version'], true );
            $cached_words   = get_post_meta( $post_id, $keys['words'], true );
            $cached_seconds = get_post_meta( $post_id, $keys['seconds'], true );
            $cached_exact   = get_post_meta( $post_id, $keys['exact'], true );

            if (
                '' !== $cached_minutes &&
                '' !== $cached_words &&
                '' !== $cached_seconds &&
                '' !== $cached_exact &&
                $cached_wpm === $wpm &&
                $cached_hash === $hash &&
                $cached_version === self::ALGORITHM_VERSION
            ) {
                return array(
                    'post_id'                  => $post_id,
                    'content_type'             => $content_type,
                    'language'                 => self::get_content_language( $post_id ),
                    'word_count'               => max( 0, (int) $cached_words ),
                    'reading_time_minutes'     => max( 0, (int) $cached_minutes ),
                    'reading_time_seconds'     => max( 0, (int) $cached_seconds ),
                    'reading_time_minutes_exact' => max( 0, (float) $cached_exact ),
                    'reading_speed'            => $wpm,
                    'algorithm_version'        => self::ALGORITHM_VERSION,
                    'content_hash'             => $hash,
                    'cache_hit'                => true,
                );
            }
        }

        $clean_content = self::prepare_content( $content );
        $word_count    = self::count_words( $clean_content );
        $exact_minutes = $word_count > 0 ? ( $word_count / $wpm ) : 0;
        $seconds       = $word_count > 0 ? (int) ceil( $exact_minutes * 60 ) : 0;
        $minutes       = $word_count > 0 ? (int) ceil( $exact_minutes ) : 0;

        update_post_meta( $post_id, $keys['minutes'], $minutes );
        update_post_meta( $post_id, $keys['wpm'], $wpm );
        update_post_meta( $post_id, $keys['hash'], $hash );
        update_post_meta( $post_id, $keys['version'], self::ALGORITHM_VERSION );
        update_post_meta( $post_id, $keys['words'], $word_count );
        update_post_meta( $post_id, $keys['seconds'], $seconds );
        update_post_meta( $post_id, $keys['exact'], $exact_minutes );

        return array(
            'post_id'                    => $post_id,
            'content_type'               => $content_type,
            'language'                   => self::get_content_language( $post_id ),
            'word_count'                 => $word_count,
            'reading_time_minutes'       => $minutes,
            'reading_time_seconds'       => $seconds,
            'reading_time_minutes_exact' => $exact_minutes,
            'reading_speed'              => $wpm,
            'algorithm_version'          => self::ALGORITHM_VERSION,
            'content_hash'               => $hash,
            'cache_hit'                  => false,
        );
    }

    /**
     * Remove elements that should not contribute to estimated reading time.
     *
     * @param string $content Raw WordPress content.
     * @return string
     */
    public static function prepare_content( $content ) {
        $content = (string) $content;
        $content = strip_shortcodes( $content );

        // Remove code and non-reading embeds before stripping HTML.
        $patterns = array(
            '/<!--.*?-->/s',
            '/<(pre|code|script|style|noscript)\b[^>]*>.*?<\/\1\s*>/is',
            '/\[(code|sourcecode)\b[^\]]*\].*?\[\/\1\]/is',
            '/<(table|video|audio|iframe|object|svg)\b[^>]*>.*?<\/\1\s*>/is',
            '/<img\b[^>]*>/i',
        );

        foreach ( $patterns as $pattern ) {
            $cleaned = preg_replace( $pattern, ' ', $content );
            if ( null !== $cleaned ) {
                $content = $cleaned;
            }
        }

        // Treat non-breaking and zero-width spaces as boundaries, but keep
        // ZWNJ/ZWJ inside words (important for Persian and Indic scripts).
        $content = preg_replace( '/[\x{00A0}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $content );
        $content = wp_strip_all_tags( $content );
        $content = preg_replace( '/\s+/u', ' ', $content );

        return trim( (string) $content );
    }

    /**
     * Count Unicode-aware reading units.
     *
     * CJK ideographs/kana/hangul are counted individually because they do not
     * use whitespace-delimited words. ZWNJ and ZWJ remain part of a word.
     *
     * @param string $content Plain text.
     * @return int
     */
    public static function count_words( $content ) {
        $content = trim( (string) $content );

        if ( '' === $content ) {
            return 0;
        }

        $pattern = '/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]|[\p{L}\p{N}][\p{L}\p{N}\p{M}\x{200C}\x{200D}]*(?:[\'\x{2019}\-][\p{L}\p{N}][\p{L}\p{N}\p{M}\x{200C}\x{200D}]*)*/u';
        $count   = preg_match_all( $pattern, $content, $matches );

        if ( false === $count ) {
            // Fallback for environments with a PCRE/Unicode limitation.
            $words = preg_split( '/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY );
            return is_array( $words ) ? count( $words ) : 0;
        }

        return (int) $count;
    }

    /**
     * Return the language associated with this post where known.
     *
     * @param int $post_id WordPress post ID.
     * @return string
     */
    private static function get_content_language( $post_id ) {
        $language = '';

        if ( function_exists( 'pll_get_post_language' ) ) {
            $language = (string) pll_get_post_language( $post_id, 'slug' );
        }

        if ( '' === $language && has_filter( 'wpml_post_language_details' ) ) {
            $details = apply_filters( 'wpml_post_language_details', null, $post_id );
            if ( is_array( $details ) && ! empty( $details['language_code'] ) ) {
                $language = (string) $details['language_code'];
            }
        }

        if ( '' === $language ) {
            $language = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        }

        return sanitize_key( $language );
    }

    /**
     * Cache metadata names. The original content keys stay unchanged so older
     * installations keep their existing reading-time column and integrations.
     *
     * @param string $content_type Content context.
     * @return array
     */
    private static function get_cache_keys( $content_type ) {
        if ( 'excerpt' === $content_type ) {
            return array(
                'minutes' => '_sam_reading_time_excerpt_minutes',
                'wpm'     => '_sam_reading_time_excerpt_wpm',
                'hash'    => '_sam_reading_time_excerpt_content_hash',
                'version' => '_sam_reading_time_excerpt_algorithm_version',
                'words'   => '_sam_reading_time_excerpt_word_count',
                'seconds' => '_sam_reading_time_excerpt_seconds',
                'exact'   => '_sam_reading_time_excerpt_exact_minutes',
            );
        }

        return array(
            'minutes' => 'sam_reading_time_minutes',
            'wpm'     => '_sam_reading_time_wpm',
            'hash'    => '_sam_reading_time_content_hash',
            'version' => '_sam_reading_time_algorithm_version',
            'words'   => '_sam_reading_time_word_count',
            'seconds' => '_sam_reading_time_seconds',
            'exact'   => '_sam_reading_time_exact_minutes',
        );
    }

    /**
     * Return a consistent empty result for invalid post IDs.
     *
     * @param int    $post_id      WordPress post ID.
     * @param string $content_type Content context.
     * @return array
     */
    private static function empty_metrics( $post_id, $content_type ) {
        return array(
            'post_id'                    => absint( $post_id ),
            'content_type'               => $content_type,
            'language'                   => '',
            'word_count'                 => 0,
            'reading_time_minutes'       => 0,
            'reading_time_seconds'       => 0,
            'reading_time_minutes_exact' => 0,
            'reading_speed'              => max( 1, (int) get_option( 'sam_reading_time_words_per_minute', 200 ) ),
            'algorithm_version'          => self::ALGORITHM_VERSION,
            'content_hash'               => '',
            'cache_hit'                  => false,
        );
    }
}
