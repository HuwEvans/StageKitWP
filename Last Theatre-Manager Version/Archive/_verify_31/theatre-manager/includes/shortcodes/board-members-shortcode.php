<?php
/**
* Shortcode: Board Members Display
* Description: Outputs Board Member entries styled with Display Options.
*/

defined('ABSPATH') || exit;

function tm_board_member_shortcode($atts) {

    $args = array(
        'post_type' => 'board_member',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);

    // Define sorting rules
    $priority_roles = ['President', 'Vice-President', 'Treasurer', 'Secretary'];

    // Custom sorting function
    if ($query->have_posts()) {
        usort($query->posts, function ($a, $b) use ($priority_roles) {
            $position_a = get_post_meta($a->ID, '_tm_position', true);
            $position_b = get_post_meta($b->ID, '_tm_position', true);

            $index_a = array_search($position_a, $priority_roles);
            $index_b = array_search($position_b, $priority_roles);

            // Prioritize predefined roles
            if ($index_a !== false && $index_b !== false) {
                return $index_a - $index_b;
            } elseif ($index_a !== false) {
                return -1;
            } elseif ($index_b !== false) {
                return 1;
            }

            // Alphabetical sorting for other roles
            return strcmp($position_a, $position_b);
        });
    }

    $bg_color = get_option('tm_board_member_bg_color', '#ffffff');
    $text_color = get_option('tm_board_member_text_color', '#000000');
    $border_color = get_option('tm_board_member_border_color', '#000000');
    $border_width = get_option('tm_board_member_border_width', '0');
    $rounded = get_option('tm_board_member_rounded') ? 'true' : 'false';
    $border_radius = get_option('tm_board_member_radius', '20');
    $shadow = get_option('tm_board_member_shadow') ? 'true' : 'false';
    $grid_columns = get_option('tm_board_member_grid_columns', '1');
    $atts = shortcode_atts([
        'show_photos' => 'true',
        'columns' => $grid_columns,
    ], $atts, 'tm_board_members');
	
    $style = "background-color: {$bg_color}; color: {$text_color}; border: {$border_width}px solid {$border_color}; box-sizing: border-box; padding:15px;";
    if ($rounded === 'true') { $style .= " border-radius: {$border_radius}px;"; }
    if ($shadow === 'true') { $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);"; }

    $columns = max(1, intval($atts['columns']));
    $gap = 20;
    $card_width = "calc((100% - " . ($gap * ($columns - 1)) . "px) / {$columns})";

    echo '<style>
        .tm-board-members-grid {
			box-sizing: border-box;
			grid-template-columns: repeat(' . $columns .', 1fr);
            display: grid;
            flex-wrap: wrap;
            gap: 20px;
        }
        .tm-board-member-card {
            box-sizing: border-box;
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            flex: 1 1 auto;
        }
		.tm-board-member-card p,
		.tm-board-member-card h4 {
			color: ' . esc_attr($text_color) . ';
			margin: 5px 0;
		}
        .tm-board-member-card img {
            width: 200px;
            height: 200px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .tm-position-bottom {
            margin-top: auto;
        }
        @media (max-width: 767px) {
            .tm-board-member-card {
                flex: 0 0 100% !important;
            }
        }
        @media (min-width: 768px) and (max-width: 1024px) {
            .tm-board-member-card {
                flex: 0 0 50% !important;
            }
        }
    </style>';

    ob_start();
    if ($query->have_posts()) {
        echo '<div class="tm-board-members-grid">';
        foreach ($query->posts as $post) {
            $company = get_post_meta($post->ID, '_tm_company', true);
            $position = get_post_meta($post->ID, '_tm_position', true);
            $photo = get_post_meta($post->ID, '_tm_photo', true);

            echo '<div class="tm-board-member-card" style="flex: 0 0 ' . $card_width . '; ' . esc_attr($style) . '">';
            
            if ($atts['show_photos'] === 'true' && $photo) {
                $photo_url = tm_get_image_url($photo);
                if ($photo_url) {
                    echo '<img src="' . esc_url($photo_url) . '" alt="Photo" />';
                }
            }

            echo '<h4>' . esc_html(get_the_title($post->ID)) . '</h4>';

            if ($position) {
                echo '<div class="tm-position-bottom"><p><strong></strong> ' . esc_html($position) . '</p></div>';
            }

            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No board members found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('tm_board_members', 'tm_board_member_shortcode');
?>
