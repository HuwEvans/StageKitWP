<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Members {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    public static function add_menu() {

        add_submenu_page(
            'stagekitwp',
            'Members',
            'Members',
            STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS,
            'stagekitwp-ma-members',
            [__CLASS__, 'render']
        );
    }

    public static function render() {

        if (!current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS)) {
            return;
        }

        $users = get_users();

        ?>

        <div class="wrap">
            <h1>Members</h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Interests</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>

                        <td>
                            <?php
                            $terms = wp_get_object_terms($user->ID, 'stagekitwp_interest');

                            if (!empty($terms) && !is_wp_error($terms)) {
                                $names = wp_list_pluck($terms, 'name');
                                echo esc_html(implode(', ', $names));
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>

                        <td>
                            <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>">
                                Edit Profile
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

        </div>

        <?php
    }
}