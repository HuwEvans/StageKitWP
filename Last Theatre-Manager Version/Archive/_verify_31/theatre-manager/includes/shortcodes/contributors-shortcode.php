<?php
/**
* Shortcode: Contributors Display
* Description: Outputs Contributor entries grouped by level with responsive styling.
*/

defined('ABSPATH') || exit;

function tm_contributors_shortcode($atts) {
    $args = array(
        'post_type' => 'contributor',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $contributors = new WP_Query($args);

    // Get display settings
    $bg_color = get_option('tm_contributor_bg_color', '#ffffff');
    $text_color = get_option('tm_contributor_text_color', '#000000');
	$border_color = get_option('tm_contributor_border_color', '#000000');
    $border_width = get_option('tm_contributor_border_width', '0');
    $rounded = get_option('tm_contributor_rounded') ? 'true' : 'false';
    $border_radius = get_option('tm_contributor_radius', '20');
    $shadow = get_option('tm_contributor_shadow') ? 'true' : 'false';

    // Build style string
    $style = "background-color: {$bg_color}; color: {$text_color}; border: {$border_width}px solid {$border_color};";
    if ($rounded === 'true') {
        $style .= " border-radius: {$border_radius}px;";
    }
    if ($shadow === 'true') {
        $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);";
    }

    // Group contributors by level
    $levels = ['Platinum' => [], 'Gold' => [], 'Silver' => [], 'Bronze' => []];
    if ($contributors->have_posts()) {
        while ($contributors->have_posts()) {
            $contributors->the_post();
            $level = get_post_meta(get_the_ID(), '_tm_level', true);
            $level = $level ? $level : 'Bronze';
            if (!isset($levels[$level])) {
                $levels[$level] = [];
            }
            $levels[$level][] = [
                'title' => get_the_title(),
                'company' => get_post_meta(get_the_ID(), '_tm_company', true),
                'level' => $level
            ];
        }
        wp_reset_postdata();
    }

    // Output styles
    echo '<style>
        .tm-contributor-section h2 {
            text-align: center;
            margin-bottom: 5px;
			margin-top: 5px;
        }
        .tm-contributor-section {
            margin-bottom: 5px;
			margin-top: 5px;
        }        .tm-contributor-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .tm-contributor-card {
            box-sizing: border-box;
            padding: 5px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1 1 auto;
        }
		.tm-contributor-card h2,
		.tm-contributor-card h3,
        .tm-contributor-card h4,
		.tm-contributor-card p {
            color: ' . esc_attr($text_color) . ';
			margin: 5px 0;
        }
        @media (max-width: 767px) {
            .platinum .tm-contributor-card,
            .gold .tm-contributor-card,
            .silver .tm-contributor-card,
            .bronze .tm-contributor-card {
                flex: 0 0 100% !important;
            }
        }
        @media (min-width: 768px) and (max-width: 1024px) {
            .gold .tm-contributor-card,
            .silver .tm-contributor-card,
            .bronze .tm-contributor-card {
                flex: 0 0 calc(50% - 10px) !important;
            }
        }
        @media (min-width: 1025px) {
            .platinum .tm-contributor-card {
                flex: 0 0 100%;
            }
            .gold .tm-contributor-card {
                flex: 0 0 calc(50% - 10px);
            }
            .silver .tm-contributor-card {
                flex: 0 0 calc(33.333% - 13.33px);
            }
            .bronze .tm-contributor-card {
                flex: 0 0 calc(25% - 15px);
            }
        }
    </style>';

    ob_start();
    foreach ($levels as $level => $entries) {
        if (!empty($entries)) {
            echo '<div class="tm-contributor-section">';
            echo '<h2>' . esc_html($level) . ' Contributors</h2>';
            echo '<div class="tm-contributor-grid ' . strtolower($level) . '">';
            foreach ($entries as $entry) {
                echo '<div class="tm-contributor-card" style="' . esc_attr($style) . '">';
                echo '<h4 style="color: ' . esc_attr($text_color) . ';">' . esc_html($entry['title']) . '</h4>';
                if ($entry['company']) {
                    echo '<p style="color: ' . esc_attr($text_color) . ';"><strong></strong> ' . esc_html($entry['company']) . '</p>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
    }

    return ob_get_clean();
}
add_shortcode('tm_contributors', 'tm_contributors_shortcode');
?>
