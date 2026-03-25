<?php

if (!defined('ABSPATH')) {
    exit;
}

class SCC_Plugin {

    private static $instance = null;
    private $option_name = 'scc_settings';
    private $shortcode_tag = 'smart_category_cloud';

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_shortcode(): void {
        add_shortcode($this->shortcode_tag, [$this, 'render_shortcode']);
    }

    public function register_assets(): void {
        wp_register_style(
            'scc-public',
            SCC_URL . 'assets/css/public.css',
            [],
            SCC_VERSION
        );

        wp_register_script(
            'scc-public',
            SCC_URL . 'assets/js/public.js',
            [],
            SCC_VERSION,
            true
        );
    }

    public function render_shortcode($atts = []): string {

        $atts = shortcode_atts([
            'posts_per_page' => 10,
            'min_font'       => 14,
            'max_font'       => 42,
        ], $atts, $this->shortcode_tag);

        wp_enqueue_style('scc-public');
        wp_enqueue_script('scc-public');

        $terms = get_terms([
            'taxonomy'   => 'category',
            'hide_empty' => true,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return '<p>No categories found.</p>';
        }

        $max_count = max(array_map(function ($t) {
            return (int)$t->count;
        }, $terms));

        $now = current_time('timestamp', true);

        $cloud = [];

        foreach ($terms as $term) {

            $latest = new WP_Query([
                'post_type' => 'post',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
                'fields' => 'ids',
                'tax_query' => [[
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ]],
            ]);

            $latest_ts = 0;
            if (!empty($latest->posts[0])) {
                $latest_ts = get_post_time('U', true, $latest->posts[0]);
            }

            $days = $latest_ts ? ($now - $latest_ts) / DAY_IN_SECONDS : 9999;

            $count_score = $max_count > 0
                ? log(1 + $term->count) / log(1 + $max_count)
                : 0;

            $recency_score = exp(-$days / 90);

            $score = (0.65 * $count_score) + (0.35 * $recency_score);

            $font = $atts['min_font'] + ($score * ($atts['max_font'] - $atts['min_font']));

            $cloud[] = [
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
                'font' => round($font, 1),
            ];
        }

        usort($cloud, function ($a, $b) {
            return $b['font'] <=> $a['font'];
        });

        $selected = isset($_GET['scc_cat']) ? sanitize_title($_GET['scc_cat']) : '';

        ob_start();

        echo '<div class="scc">';
        echo '<div class="scc-cloud">';

        foreach ($cloud as $item) {

            $url = add_query_arg('scc_cat', $item['slug']);
            $active = $selected === $item['slug'] ? 'active' : '';

            echo '<a class="scc-term ' . esc_attr($active) . '" href="' . esc_url($url) . '" style="font-size:' . esc_attr($item['font']) . 'px">';
            echo esc_html($item['name']);
            echo '</a>';
        }

        echo '</div>';

        $query_args = [
            'post_type' => 'post',
            'posts_per_page' => (int)$atts['posts_per_page'],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($selected) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'category',
                'field' => 'slug',
                'terms' => $selected,
            ]];
        }

        $q = new WP_Query($query_args);

        echo '<div class="scc-posts">';

        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();

                echo '<div class="scc-post">';
                echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
                echo '<div class="scc-date">' . esc_html(get_the_date()) . '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>No posts found.</p>';
        }

        echo '</div>';
        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }
}
