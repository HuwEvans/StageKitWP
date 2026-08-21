<?php
/**
* Shortcode: Sponsors Display
* Description: Outputs Sponsor entries grouped by level with responsive styling.
*/

defined('ABSPATH') || exit;

function tm_sponsor_shortcode($atts) {
    $atts = shortcode_atts([
        'show_name' => 'true',
        'show_company' => 'true',
        'show_logo' => 'true',
        'show_website' => 'true'
    ], $atts, 'tm_sponsors');

    $args = array(
        'post_type' => 'sponsor',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);

    $bg_color = get_option('tm_sponsor_bg_color', '#ffffff');
    $text_color = get_option('tm_sponsor_text_color', '#000000');
	$border_color = get_option('tm_sponsor_border_color', '#000000');
    $border_width = get_option('tm_sponsor_border_width', '0');
    $rounded = get_option('tm_sponsor_rounded') ? 'true' : 'false';
    $border_radius = get_option('tm_sponsor_radius', '20');
    $shadow = get_option('tm_sponsor_shadow') ? 'true' : 'false';

    $style = "background-color: {$bg_color}; color: {$text_color}; border: {$border_width}px solid {$border_color};";
    if ($rounded === 'true') {
        $style .= " border-radius: {$border_radius}px;";
    }
    if ($shadow === 'true') {
        $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);";
    }

    $levels = ['Platinum' => [], 'Gold' => [], 'Silver' => [], 'Bronze' => []];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $level = get_post_meta(get_the_ID(), '_tm_level', true);
            $level = $level ? $level : 'Bronze';
            if (!isset($levels[$level])) {
                $levels[$level] = [];
            }
            $levels[$level][] = [
                'title' => get_the_title(),
                'company' => get_post_meta(get_the_ID(), '_tm_company', true),
                'website' => get_post_meta(get_the_ID(), '_tm_website', true),
                'logo' => get_post_meta(get_the_ID(), '_tm_logo', true),
                'level' => $level
            ];
        }
        wp_reset_postdata();
    }

    echo '<style>
        .tm-sponsor-section h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .tm-sponsor-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
            justify-content: center;
        }
        .tm-sponsor-card {
            box-sizing: border-box;
            padding: 5px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1 1 auto;
        }
        .tm-sponsor-card h3,
		.tm-sponsor-card h4,
        .tm-sponsor-card p,
        .tm-sponsor-card a {
            color: ' . esc_attr($text_color) . ';
            margin: 5px 0;
        }
        .tm-sponsor-card img {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        @media (max-width: 767px) {
            .platinum .tm-sponsor-card,
            .gold .tm-sponsor-card,
            .silver .tm-sponsor-card,
            .bronze .tm-sponsor-card {
                flex: 0 0 100% !important;
            }
        }
        @media (min-width: 768px) and (max-width: 1024px) {
            .gold .tm-sponsor-card,
            .silver .tm-sponsor-card,
            .bronze .tm-sponsor-card {
                flex: 0 0 calc(50% - 10px) !important;
            }
        }
        @media (min-width: 1025px) {
            .platinum .tm-sponsor-card {
                flex: 0 0 100%;
            }
            .gold .tm-sponsor-card {
                flex: 0 0 calc(50% - 10px);
            }
            .silver .tm-sponsor-card {
                flex: 0 0 calc(33.333% - 13.33px);
            }
            .bronze .tm-sponsor-card {
                flex: 0 0 calc(25% - 15px);
            }
        }
    </style>';

    ob_start();
    foreach ($levels as $level => $entries) {
        if (!empty($entries)) {
            echo '<div class="tm-sponsor-section">';
            echo '<h2>' . esc_html($level) . ' Sponsors</h2>';
            echo '<div class="tm-sponsor-grid ' . strtolower($level) . '">';
            foreach ($entries as $entry) {
                $show_name = $atts['show_name'] === 'true';
                $show_company = $atts['show_company'] === 'true';
                $show_logo = $atts['show_logo'] === 'true';
                $show_website = $atts['show_website'] === 'true';

                echo '<div class="tm-sponsor-card" style="' . esc_attr($style) . '">';

                if ($show_logo && !empty($entry['logo'])) {
                    $logo_url = tm_get_image_url($entry['logo']);
                    if ($logo_url) {
                        echo '<div>';
                        if (!empty($entry['website']) && $show_website) {
                            echo '<a href="' . esc_url($entry['website']) . '" target="_blank">';
                            echo '<img src="' . esc_url($logo_url) . '" alt="Logo" />';
                            echo '</a>';
                        } else {
                            echo '<img src="' . esc_url($logo_url) . '" alt="Logo" />';
                        }
                        echo '</div>';
                    }
                }

                if ($show_name) {
                    echo '<h4>' . esc_html($entry['title']) . '</h4>';
                }

                if ($show_company && $entry['company']) {
                    echo '<h4>' . esc_html($entry['company']) . '</h4>';
                }

                if ($show_website && $entry['website']) {
                    echo '<p><a href="' . esc_url($entry['website']) . '" target="_blank">' . esc_html($entry['website']) . '</a></p>';
                }

                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
    }

    return ob_get_clean();
}
add_shortcode('tm_sponsors', 'tm_sponsor_shortcode');
?>
