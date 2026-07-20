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

        add_action('admin_menu', [$this, 'add_settings_page']);

        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_shortcode(): void {

        add_shortcode(
            $this->shortcode_tag,
            [$this, 'render_shortcode']
        );
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

    public function add_settings_page(): void {

        add_options_page(
            __('Smart Category Cloud Settings', 'wordpress-smart-category-cloud'),
            __('Smart Category Cloud', 'wordpress-smart-category-cloud'),
            'manage_options',
            'smart-category-cloud',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {

        register_setting(
            'scc_settings_group',
            $this->option_name,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => [
                    'show_separators' => 0,
                ],
            ]
        );

        add_settings_section(
            'scc_appearance_section',
            __('Appearance', 'wordpress-smart-category-cloud'),
            [$this, 'render_appearance_section'],
            'smart-category-cloud'
        );

        add_settings_field(
            'scc_show_separators',
            __('Category separators', 'wordpress-smart-category-cloud'),
            [$this, 'render_show_separators_field'],
            'smart-category-cloud',
            'scc_appearance_section'
        );
    }

    public function sanitize_settings($input): array {

        return [
            'show_separators' =>
                is_array($input) && !empty($input['show_separators'])
                    ? 1
                    : 0,
        ];
    }

    public function get_settings(): array {

        $defaults = [
            'show_separators' => 0,
        ];

        $settings = get_option($this->option_name, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        return wp_parse_args($settings, $defaults);
    }

    public function render_appearance_section(): void {

        echo '<p>';

        echo esc_html__(
            'Control how the category cloud appears to visitors.',
            'wordpress-smart-category-cloud'
        );

        echo '</p>';
    }

    public function render_show_separators_field(): void {

        $settings = $this->get_settings();
        ?>

        <label for="scc_show_separators">
            <input
                type="checkbox"
                id="scc_show_separators"
                name="<?php echo esc_attr($this->option_name); ?>[show_separators]"
                value="1"
                <?php checked(1, (int) $settings['show_separators']); ?>
            >

            <?php
            echo esc_html__(
                'Display a dot between category links.',
                'wordpress-smart-category-cloud'
            );
            ?>
        </label>

        <?php
    }

    public function render_settings_page(): void {

        if (!current_user_can('manage_options')) {
            return;
        }
        ?>

        <div class="wrap">
            <h1>
                <?php
                echo esc_html__(
                    'Smart Category Cloud Settings',
                    'wordpress-smart-category-cloud'
                );
                ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('scc_settings_group');

                do_settings_sections('smart-category-cloud');

                submit_button();
                ?>
            </form>
        </div>

        <?php
    }

    public function render_shortcode($atts = []): string {

        $atts = shortcode_atts(
            [
                'posts_per_page' => 10,
                'min_font'       => 14,
                'max_font'       => 42,
            ],
            $atts,
            $this->shortcode_tag
        );

        $posts_per_page = max(1, (int) $atts['posts_per_page']);
        $min_font       = max(1, (float) $atts['min_font']);
        $max_font       = max($min_font, (float) $atts['max_font']);

        wp_enqueue_style('scc-public');
        wp_enqueue_script('scc-public');

        $settings = $this->get_settings();

        $cloud_classes = ['scc-cloud'];

        if (!empty($settings['show_separators'])) {
            $cloud_classes[] = 'scc-has-separators';
        }

        $terms = get_terms(
            [
                'taxonomy'   => 'category',
                'hide_empty' => true,
            ]
        );

        if (is_wp_error($terms) || empty($terms)) {
            return '<p>' .
                esc_html__(
                    'No categories found.',
                    'wordpress-smart-category-cloud'
                ) .
                '</p>';
        }

        $max_count = max(
            array_map(
                function ($term) {
                    return (int) $term->count;
                },
                $terms
            )
        );

        $now   = current_time('timestamp', true);
        $cloud = [];

        foreach ($terms as $term) {

            $latest = new WP_Query(
                [
                    'post_type'              => 'post',
                    'posts_per_page'         => 1,
                    'orderby'                => 'date',
                    'order'                  => 'DESC',
                    'fields'                 => 'ids',
                    'no_found_rows'          => true,
                    'ignore_sticky_posts'    => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'tax_query'              => [
                        [
                            'taxonomy' => 'category',
                            'field'    => 'term_id',
                            'terms'    => (int) $term->term_id,
                        ],
                    ],
                ]
            );

            $latest_ts = 0;

            if (!empty($latest->posts[0])) {
                $latest_ts = get_post_time(
                    'U',
                    true,
                    $latest->posts[0]
                );
            }

            $days = $latest_ts
                ? max(0, ($now - $latest_ts) / DAY_IN_SECONDS)
                : 9999;

            $count_score = $max_count > 0
                ? log(1 + $term->count) / log(1 + $max_count)
                : 0;

            $recency_score = exp(-$days / 90);

            $score =
                (0.65 * $count_score) +
                (0.35 * $recency_score);

            $font =
                $min_font +
                ($score * ($max_font - $min_font));

            $cloud[] = [
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => (int) $term->count,
                'font'  => round($font, 1),
            ];
        }

        usort(
            $cloud,
            function ($a, $b) {
                return $b['font'] <=> $a['font'];
            }
        );

        $selected = isset($_GET['scc_cat'])
            ? sanitize_title(wp_unslash($_GET['scc_cat']))
            : '';

        ob_start();

        echo '<div class="scc">';

        echo '<div class="' .
            esc_attr(implode(' ', $cloud_classes)) .
            '">';

        $cloud_count = count($cloud);

        foreach ($cloud as $index => $item) {

            $url = add_query_arg(
                'scc_cat',
                $item['slug']
            );

            $active = $selected === $item['slug']
                ? ' active'
                : '';

            echo '<a class="scc-term' .
                esc_attr($active) .
                '" href="' .
                esc_url($url) .
                '" style="font-size:' .
                esc_attr($item['font']) .
                'px">';

            echo esc_html($item['name']);

            echo '</a>';

            /*
             * Add a separator after every category except the final one.
             *
             * The separator is based on the smaller of the two neighboring
             * category font sizes. This prevents a separator between a large
             * and small category from overpowering the smaller category.
             */
            if (
                !empty($settings['show_separators']) &&
                $index < ($cloud_count - 1)
            ) {
                $next_item = $cloud[$index + 1];

                $separator_size = round(
                    min(
                        (float) $item['font'],
                        (float) $next_item['font']
                    ) * 0.70,
                    1
                );

                echo '<span class="scc-separator" aria-hidden="true" style="font-size:' .
                    esc_attr($separator_size) .
                    'px">&bull;</span>';
            }
        }

        echo '</div>';

        $query_args = [
            'post_type'           => 'post',
            'posts_per_page'      => $posts_per_page,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
        ];

        if ($selected) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $selected,
                ],
            ];
        }

        $query = new WP_Query($query_args);

        echo '<div class="scc-posts">';

        if ($query->have_posts()) {

            while ($query->have_posts()) {
                $query->the_post();

                echo '<div class="scc-post">';

                echo '<h3><a href="' .
                    esc_url(get_permalink()) .
                    '">' .
                    esc_html(get_the_title()) .
                    '</a></h3>';

                echo '<div class="scc-date">' .
                    esc_html(get_the_date()) .
                    '</div>';

                echo '</div>';
            }
        } else {
            echo '<p>' .
                esc_html__(
                    'No posts found.',
                    'wordpress-smart-category-cloud'
                ) .
                '</p>';
        }

        echo '</div>';

        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }
}
