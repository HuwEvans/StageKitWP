<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Search {

    public static function init() {
        add_shortcode('stagekitwp_members_producer_dashboard', [__CLASS__, 'render_dashboard']);
    }

    public static function render_dashboard() {

        if (!current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS)) {
            return '<p>Access denied</p>';
        }

        // ==========================
        // BULK EMAIL HANDLER
        // ==========================
        if (
            isset($_POST['stagekitwp_members_bulk_email']) &&
            isset($_POST['stagekitwp_members_bulk_email_nonce']) &&
            wp_verify_nonce($_POST['stagekitwp_members_bulk_email_nonce'], 'stagekitwp_members_bulk_email') &&
            current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)
        ) {
            $subject = sanitize_text_field($_POST['stagekitwp_members_bulk_subject']);
            $message = wp_kses_post($_POST['stagekitwp_members_bulk_message']);

            // "Important" bypasses the announcement opt-out. Only Executives/
            // admins may override preferences.
            $important = !empty($_POST['stagekitwp_members_bulk_important'])
                && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS);

            // Optional "Schedule Send" (datetime-local). When it is in the
            // future, queue the emails instead of sending immediately.
            $schedule_raw = isset($_POST['stagekitwp_members_schedule_time'])
                ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_schedule_time']))
                : '';
            $schedule_ts = $schedule_raw ? strtotime($schedule_raw) : 0;
            $is_scheduled = ($schedule_ts && $schedule_ts > time());

            $users = self::get_filtered_users();
            $count   = 0;
            $skipped = 0;

            foreach ($users as $user) {

                // Respect each member's announcement preference.
                if (!STAGEKITWP_MEMBERS_Notifications::should_notify(
                        $user->ID,
                        STAGEKITWP_MEMBERS_Notifications::TYPE_ANNOUNCEMENT,
                        $important
                    )) {
                    $skipped++;
                    continue;
                }

                if ($is_scheduled) {
                    STAGEKITWP_MEMBERS_Email_Queue::add_to_queue($user->user_email, $subject, $message, $schedule_ts);
                } else {
                    STAGEKITWP_MEMBERS_Health::send_email($user->user_email, $subject, $message);
                }
                $count++;
            }

            $suffix = $skipped ? ' (' . intval($skipped) . ' opted out)' : '';

            if ($is_scheduled) {
                echo '<div class="notice notice-success"><p>' . intval($count) .
                    ' emails scheduled for ' . esc_html(date_i18n('M j, Y g:i a', $schedule_ts)) .
                    $suffix . '.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Bulk email sent to ' . intval($count) .
                    ' members' . $suffix . '.</p></div>';
            }
        }

        // ==========================
        // INDIVIDUAL EMAIL
        // ==========================
        if (
            isset($_POST['stagekitwp_members_send_member_email']) &&
            isset($_POST['stagekitwp_members_email_nonce']) &&
            wp_verify_nonce($_POST['stagekitwp_members_email_nonce'], 'stagekitwp_members_send_member_email') &&
            current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)
        ) {

            $user = get_userdata(intval($_POST['stagekitwp_members_target_user']));

            if ($user) {
                STAGEKITWP_MEMBERS_Health::send_email(
                    $user->user_email,
                    sanitize_text_field($_POST['stagekitwp_members_email_subject']),
                    wp_kses_post($_POST['stagekitwp_members_email_message'])
                );

                echo '<div class="notice notice-success"><p>Email sent to ' . esc_html($user->display_name) . '</p></div>';
            }
        }

        // ==========================
        // CONVERSATION HANDLER
        // ==========================
        if (
            isset($_POST['stagekitwp_start_conversation']) &&
            isset($_POST['stagekitwp_target_user']) &&
            current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MESSAGE_MEMBERS)
        ) {

            $target = get_userdata(intval($_POST['stagekitwp_target_user']));
            $me     = wp_get_current_user();

            if ($target) {
                $title = $me->display_name . ' & ' . $target->display_name;
                $conversation_id = STAGEKITWP_MEMBERS_Messaging::create_conversation(
                    [get_current_user_id(), $target->ID],
                    $title
                );

                if (!is_wp_error($conversation_id) && $conversation_id) {
                    $link = STAGEKITWP_MEMBERS_Messaging::thread_url($conversation_id);
                    echo '<div class="notice notice-success"><p>Conversation started with ' .
                        esc_html($target->display_name) . '. ' .
                        '<a href="' . esc_url($link) . '">Open conversation</a></p></div>';
                }
            }
        }

        ob_start();
        ?>

        <h2>Producer Dashboard</h2>

        <!-- =========================== SEARCH =========================== -->
        <form method="get" class="stagekitwp-ma-search-form">
            <input type="text" name="stagekitwp_name" placeholder="Search by name or email"
                   value="<?php echo esc_attr($_GET['stagekitwp_name'] ?? ''); ?>">

            <select name="stagekitwp_interest">
                <option value="0">All interests</option>
                <?php
                $selected_interest = (int) ($_GET['stagekitwp_interest'] ?? 0);
                $interest_terms = get_terms(['taxonomy' => 'stagekitwp_interest', 'hide_empty' => false]);
                if (!is_wp_error($interest_terms)) {
                    foreach ($interest_terms as $term) {
                        printf(
                            '<option value="%d" %s>%s</option>',
                            (int) $term->term_id,
                            selected($selected_interest, $term->term_id, false),
                            esc_html($term->name)
                        );
                    }
                }
                ?>
            </select>

            <button type="submit">Search</button>
            <?php if (!empty($_GET['stagekitwp_name']) || !empty($_GET['stagekitwp_interest'])): ?>
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>">Reset</a>
            <?php endif; ?>
        </form>

        <?php
        $result_users = self::get_filtered_users();
        echo '<p class="stagekitwp-ma-result-count">' . count($result_users) . ' member(s) found.</p>';
        ?>

        <hr>
		<?php
			if (isset($_POST['stagekitwp_members_test_email'])) {
			
			    $current_user = wp_get_current_user();
			
			    STAGEKITWP_MEMBERS_Health::send_email(
			        $current_user->user_email,
			        sanitize_text_field($_POST['stagekitwp_members_bulk_subject']),
			        wp_kses_post($_POST['stagekitwp_members_bulk_message'])
			    );
			
			    echo '<div class="notice notice-success"><p>Test email sent to yourself</p></div>';
			}
		?>
        <!-- =========================== BULK EMAIL =========================== -->
        <h3>Bulk Email Members</h3>

        <form method="post">

            <?php wp_nonce_field('stagekitwp_members_bulk_email', 'stagekitwp_members_bulk_email_nonce'); ?>

            <select id="stagekitwp-ma-template-select">
                <option value="">Select Template</option>

                <?php
                if (class_exists('STAGEKITWP_MEMBERS_Email_Templates')) {
                    foreach (STAGEKITWP_MEMBERS_Email_Templates::get_templates() as $tpl):
                        $subject = get_post_meta($tpl->ID, '_stagekitwp_members_subject', true);
                ?>
                        <option
                            data-subject="<?php echo esc_attr($subject); ?>"
                            data-content='<?php echo json_encode($tpl->post_content); ?>'
                        >
                            <?php echo esc_html($tpl->post_title); ?>
                        </option>
                <?php endforeach; } ?>
            </select>

            <p>
                <input type="text" name="stagekitwp_members_bulk_subject" placeholder="Subject" style="width:100%;">
            </p>

            <?php
            wp_editor('', 'stagekitwp_members_bulk_message', [
                'textarea_name' => 'stagekitwp_members_bulk_message',
                'textarea_rows' => 5
            ]);
            ?>
			<p>
			    <label>Schedule Send:</label><br>
			    <input type="datetime-local" name="stagekitwp_members_schedule_time">
			</p>
			<?php if (current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS)): ?>
			<p>
			    <label title="Delivers even to members who opted out of announcements">
			        <input type="checkbox" name="stagekitwp_members_bulk_important" value="1">
			        Mark as important (ignore opt-outs)
			    </label>
			</p>
			<?php endif; ?>
            <p>
                <button type="button" id="stagekitwp-ma-preview-email" class="button">Preview Email</button>
            </p>
			<button type="submit" name="stagekitwp_members_test_email" class="button">
			    Send Test to Myself
			</button>
            <div id="stagekitwp-ma-preview-container" style="display:none; margin-top:15px; border:1px solid #ccc; padding:10px;"></div>

            <p>
                <button class="button button-primary" name="stagekitwp_members_bulk_email">Send Bulk Email</button>
            </p>

        </form>

        <hr>

        <!-- =========================== USERS =========================== -->

        <?php foreach ($result_users as $user): ?>

		<?php
		$terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'names']);
		$interests = !empty($terms) ? implode(', ', $terms) : '';
		$profile_link = site_url('/profile/?user_id=' . $user->ID);
		?>

            
		<div class="stagekitwp-user-card"
		     data-name="<?php echo esc_attr($user->display_name); ?>"
		     data-email="<?php echo esc_attr($user->user_email); ?>"
		     data-interests="<?php echo esc_attr($interests); ?>"
		     data-profile="<?php echo esc_attr($profile_link); ?>"
		     style="border:1px solid #ccc; padding:15px; margin-bottom:15px;">


                <h3><?php echo esc_html($user->display_name); ?></h3>

                <p>
                    <strong>Email:</strong>
                    <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                        <?php echo esc_html($user->user_email); ?>
                    </a>
                </p>

                <!-- MESSAGE -->
                <form method="post">
                    <input type="hidden" name="stagekitwp_target_user" value="<?php echo esc_attr($user->ID); ?>">
                    <button name="stagekitwp_start_conversation">Message Member</button>
                </form>

                <!-- EMAIL -->
                <details>
                    <summary><strong>Email Member</strong></summary>

                    <select class="stagekitwp-ma-template-select-single">
                        <option value="">Load Template</option>

                        <?php foreach (STAGEKITWP_MEMBERS_Email_Templates::get_templates() as $tpl):
                            $subject = get_post_meta($tpl->ID, '_stagekitwp_members_subject', true);
                        ?>
                            <option
                                data-subject="<?php echo esc_attr($subject); ?>"
                                data-content='<?php echo json_encode($tpl->post_content); ?>'
                            >
                                <?php echo esc_html($tpl->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <form method="post">

                        <?php wp_nonce_field('stagekitwp_members_send_member_email', 'stagekitwp_members_email_nonce'); ?>

                        <input type="hidden" name="stagekitwp_members_target_user" value="<?php echo esc_attr($user->ID); ?>">

                        <p>
                            <input type="text" name="stagekitwp_members_email_subject" placeholder="Subject" style="width:100%;">
                        </p>

                        <?php
                        $editor_id = 'editor_' . $user->ID;

                        wp_editor('', $editor_id, [
                            'textarea_name' => 'stagekitwp_members_email_message',
                            'textarea_rows' => 4
                        ]);
                        ?>

                        <p>
                            <button type="button" class="stagekitwp-ma-preview-single button">Preview</button>
                        </p>

                        <div class="stagekitwp-ma-preview-single-container" style="display:none; margin-top:10px; border:1px solid #ccc; padding:10px;"></div>

                        <p>
                            <button class="button button-primary" name="stagekitwp_members_send_member_email">Send Email</button>
                        </p>

                    </form>

                </details>

            </div>

        <?php endforeach; ?>

        <script>
        document.addEventListener('DOMContentLoaded', function() {

			function decodeContent(content) {
			
			    try {
			        content = JSON.parse(content);
			    } catch(e) {}
			
			    // ✅ Fix line breaks
			    content = content.replace(/\\r\\n/g, '<br>');
			
			    // ✅ Fix common HTML entities
			    const textarea = document.createElement('textarea');
			    textarea.innerHTML = content;
			    content = textarea.value;
			
			    return content;
			}
			
			function replaceVars(message, user = {}) {
			    return message
			        .replaceAll('{name}', user?.name || 'John Doe')
			        .replaceAll('{email}', user?.email || 'user@example.com')
			        .replaceAll('{interests}', user?.interests || '')
			        .replaceAll('{profile_link}', user?.profile || '#')
			        .replaceAll('{site_name}', '<?php echo esc_js(get_bloginfo("name")); ?>');
			}


            // BULK TEMPLATE
            const bulkSelect = document.getElementById('stagekitwp-ma-template-select');
            if (bulkSelect) {
                bulkSelect.addEventListener('change', function() {
                    const opt = this.selectedOptions[0];
                    document.querySelector('[name="stagekitwp_members_bulk_subject"]').value = opt.dataset.subject || '';
                    tinymce.get('stagekitwp_members_bulk_message')?.setContent(opt.dataset.content || '');
                });
            }

            // BULK PREVIEW
            document.getElementById('stagekitwp-ma-preview-email')?.addEventListener('click', function() {

                const subject = document.querySelector('[name="stagekitwp_members_bulk_subject"]').value;
                let content = tinymce.get('stagekitwp_members_bulk_message')?.getContent() || '';
				content = decodeContent(content);
                content = replaceVars(content);

                const box = document.getElementById('stagekitwp-ma-preview-container');
                box.style.display = 'block';
                box.innerHTML = `<h3>${subject}</h3><hr>${content}`;
            });

            // SINGLE TEMPLATE
            document.querySelectorAll('.stagekitwp-ma-template-select-single').forEach(select => {
                select.addEventListener('change', function() {

                    const container = this.closest('details');
                    const opt = this.selectedOptions[0];

                    container.querySelector('[name="stagekitwp_members_email_subject"]').value = opt.dataset.subject || '';

                    const textarea = container.querySelector('textarea');
                    

					let content = decodeContent(opt.dataset.content || '');
					tinymce.get(textarea.id)?.setContent(content);


                });
            });

            // SINGLE PREVIEW
            document.querySelectorAll('.stagekitwp-ma-preview-single').forEach(btn => {
                btn.addEventListener('click', function() {

                    const container = this.closest('details');

					const card = this.closest('.stagekitwp-user-card');
					
					const userData = {
					    name: card.dataset.name,
					    email: card.dataset.email,
					    interests: card.dataset.interests,
					    profile: card.dataset.profile
					};


                    const subject = container.querySelector('[name="stagekitwp_members_email_subject"]').value;

                    const textarea = container.querySelector('textarea');

                    // Pull the live editor content for THIS member's form
                    // (previously referenced an undefined `opt`).
                    let content = tinymce.get(textarea.id)?.getContent() || textarea.value || '';
                    content = decodeContent(content);

                    content = replaceVars(content, userData);

                    const box = container.querySelector('.stagekitwp-ma-preview-single-container');
                    box.style.display = 'block';
                    box.innerHTML = `<h4>${subject}</h4><hr>${content}`;
                });
            });

        });
        </script>

        <?php

        return ob_get_clean();
    }

    /**
     * Return members filtered by the current request's name/interest query.
     * Reads stagekitwp_name and stagekitwp_interest from $_GET. Capped for safety.
     */
    private static function get_filtered_users() {
        return self::query_members(
            isset($_GET['stagekitwp_name']) ? sanitize_text_field(wp_unslash($_GET['stagekitwp_name'])) : '',
            isset($_GET['stagekitwp_interest']) ? (int) $_GET['stagekitwp_interest'] : 0
        );
    }

    /**
     * Core member query used by the dashboard and CSV export.
     *
     * @param string $name     Name/email search term.
     * @param int    $interest stagekitwp_interest term id, or 0 for any.
     * @return WP_User[]
     */
    public static function query_members($name = '', $interest = 0, $limit = 500, $directory_only = false) {

        $args = [
            'number'  => $limit,
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ];

        // Directory view: only members who opted in (meta '1'). Hidden by
        // default, so we match the explicit opt-in value rather than
        // EXISTS, which keeps SQLite and MySQL behaviour identical.
        if ($directory_only) {
            $args['meta_key']   = 'stagekitwp_members_directory_listed';
            $args['meta_value'] = '1';
        }

        if ($name !== '') {
            // Match display name, login, or email.
            $args['search']         = '*' . $name . '*';
            $args['search_columns'] = ['display_name', 'user_login', 'user_email', 'user_nicename'];
        }

        // Interest is a user taxonomy, so filter by object IDs in that term.
        if ($interest > 0) {
            $ids = get_objects_in_term($interest, 'stagekitwp_interest');
            if (is_wp_error($ids) || empty($ids)) {
                return [];
            }
            $args['include'] = array_map('intval', $ids);
        }

        return get_users($args);
    }
}