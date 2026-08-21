<?php
/**
* Shortcode: Contributors Display
* Description: Outputs Contributor entries grouped by level with responsive styling.
*/

defined('ABSPATH') || exit;

function stagekitwp_contributors_shortcode($atts) {
    $atts = shortcode_atts( array(
        'platinum_label' => '',
        'gold_label'     => '',
        'silver_label'   => '',
        'bronze_label'   => '',
    ), $atts, 'stagekitwp_contributors' );

    $args = array(
        'post_type' => 'contributor',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $contributors = new WP_Query($args);

    // Get display settings
    $bg_color     = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_contributor_bg_color', '' ), '#ffffff' );
    $text_color   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_contributor_text_color', '' ), '#000000' );
    $border_color = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_contributor_border_color', '' ), '#000000' );
    $border_width = absint( get_option( 'stagekitwp_contributor_border_width', '0' ) );
    $rounded      = get_option( 'stagekitwp_contributor_rounded' ) ? 'true' : 'false';
    $border_radius = absint( get_option( 'stagekitwp_contributor_radius', '20' ) );
    $shadow       = get_option( 'stagekitwp_contributor_shadow' ) ? 'true' : 'false';
    $base_font    = sanitize_text_field( get_option( 'stagekitwp_contributor_base_font', 'Arial, sans-serif' ) );

    // Build style string
    $style = "background-color: {$bg_color}; color: {$text_color}; font-family: {$base_font}; border: {$border_width}px solid {$border_color};";
    if ($rounded === 'true') {
        $style .= " border-radius: {$border_radius}px;";
    }
    if ($shadow === 'true') {
        $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);";
    }

    // Group contributors by level
    $levels = ['Platinum' => [], 'Gold' => [], 'Silver' => [], 'Bronze' => []];
    $level_labels = stagekitwp_get_level_display_labels( 'contributor', $atts );
    if ($contributors->have_posts()) {
        while ($contributors->have_posts()) {
            $contributors->the_post();
            $level = get_post_meta(get_the_ID(), '_stagekitwp_level', true);
            $level = $level ? $level : 'Bronze';
            if (!isset($levels[$level])) {
                $levels[$level] = [];
            }
            $levels[$level][] = [
                'title' => get_the_title(),
                'company' => get_post_meta(get_the_ID(), '_stagekitwp_company', true),
                'level' => $level
            ];
        }
        wp_reset_postdata();
    }

    // Output styles
    echo '<style>
        .stagekitwp-contributor-section h2 {
            text-align: center;
            margin-bottom: 5px;
			margin-top: 5px;
        }
        .stagekitwp-contributor-section {
            margin-bottom: 5px;
			margin-top: 5px;
        }        .stagekitwp-contributor-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .stagekitwp-contributor-card {
            box-sizing: border-box;
            padding: 5px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1 1 auto;
        }
		.stagekitwp-contributor-card h2,
		.stagekitwp-contributor-card h3,
        .stagekitwp-contributor-card h4,
		.stagekitwp-contributor-card p {
            color: ' . esc_attr($text_color) . ';
			margin: 5px 0;
        }
        @media (max-width: 767px) {
            .platinum .stagekitwp-contributor-card,
            .gold .stagekitwp-contributor-card,
            .silver .stagekitwp-contributor-card,
            .bronze .stagekitwp-contributor-card {
                flex: 0 0 100% !important;
            }
        }
        @media (min-width: 768px) and (max-width: 1024px) {
            .gold .stagekitwp-contributor-card,
            .silver .stagekitwp-contributor-card,
            .bronze .stagekitwp-contributor-card {
                flex: 0 0 calc(50% - 10px) !important;
            }
        }
        @media (min-width: 1025px) {
            .platinum .stagekitwp-contributor-card {
                flex: 0 0 100%;
            }
            .gold .stagekitwp-contributor-card {
                flex: 0 0 calc(50% - 10px);
            }
            .silver .stagekitwp-contributor-card {
                flex: 0 0 calc(33.333% - 13.33px);
            }
            .bronze .stagekitwp-contributor-card {
                flex: 0 0 calc(25% - 15px);
            }
        }
    </style>';

    ob_start();
    foreach ($levels as $level => $entries) {
        if (!empty($entries)) {
            $display_label = isset( $level_labels[ $level ] ) ? $level_labels[ $level ] : $level;
            echo '<div class="stagekitwp-contributor-section">';
            echo '<h2>' . esc_html($display_label) . ' Contributors</h2>';
            echo '<div class="stagekitwp-contributor-grid ' . strtolower($level) . '">';
            foreach ($entries as $entry) {
                echo '<div class="stagekitwp-contributor-card" style="' . esc_attr($style) . '">';
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
add_shortcode('stagekitwp_contributors', 'stagekitwp_contributors_shortcode');
?>
