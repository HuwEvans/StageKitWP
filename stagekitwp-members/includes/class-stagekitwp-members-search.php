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

        // Save or remove a reusable recipient group.
        if (
            isset($_POST['stagekitwp_members_save_group']) &&
            isset($_POST['stagekitwp_members_group_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_group_nonce'])), 'stagekitwp_members_save_group') &&
            current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)
        ) {
            $name = isset($_POST['stagekitwp_members_group_name'])
                ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_group_name']))
                : '';
            if ($name !== '') {
                STAGEKITWP_MEMBERS_Campaigns::save_group($name, STAGEKITWP_MEMBERS_Campaigns::get_filters($_POST));
                echo '<div class="notice notice-success"><p>Recipient group saved.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Enter a name for this recipient group.</p></div>';
            }
        }

        if (
            isset($_POST['stagekitwp_members_delete_group']) &&
            isset($_POST['stagekitwp_members_group_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_group_nonce'])), 'stagekitwp_members_save_group') &&
            current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)
        ) {
            $group_id = sanitize_text_field(wp_unslash($_POST['stagekitwp_members_delete_group']));
            if (STAGEKITWP_MEMBERS_Campaigns::delete_group($group_id)) {
                echo '<div class="notice notice-success"><p>Recipient group deleted. Existing email history is retained.</p></div>';
            }
        }

        // ==========================
        // BULK EMAIL HANDLER
        // ==========================
        if (
            isset($_POST['stagekitwp_members_bulk_email']) &&
            isset($_POST['stagekitwp_members_bulk_email_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_bulk_email_nonce'])), 'stagekitwp_members_bulk_email') &&
            current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)
        ) {
            $subject = isset($_POST['stagekitwp_members_bulk_subject'])
                ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_bulk_subject']))
                : '';
            $message = isset($_POST['stagekitwp_members_bulk_message'])
                ? wp_kses_post(wp_unslash($_POST['stagekitwp_members_bulk_message']))
                : '';

            // Only Executives/admins may override announcement preferences.
            $important = !empty($_POST['stagekitwp_members_bulk_important'])
                && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS);

            $schedule_raw = isset($_POST['stagekitwp_members_schedule_time'])
                ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_schedule_time']))
                : '';
            $schedule_ts = $schedule_raw ? strtotime($schedule_raw) : 0;
            $send_time = $schedule_ts > time() ? $schedule_ts : time();
            $group_id = isset($_POST['stagekitwp_members_email_group'])
                ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_email_group']))
                : '';
            $group = $group_id ? STAGEKITWP_MEMBERS_Campaigns::get_group($group_id) : null;
            $filters = $group
                ? STAGEKITWP_MEMBERS_Campaigns::normalize_filters($group['filters'])
                : STAGEKITWP_MEMBERS_Campaigns::get_filters($_POST);
            $users = STAGEKITWP_MEMBERS_Campaigns::query_users($filters);
            $jobs = [];
            $skipped = 0;
            $context = STAGEKITWP_MEMBERS_Campaigns::campaign_context(
                isset($_POST['stagekitwp_members_campaign_event']) ? absint($_POST['stagekitwp_members_campaign_event']) : 0,
                isset($_POST['stagekitwp_members_campaign_position']) ? absint($_POST['stagekitwp_members_campaign_position']) : 0
            );
            $context['bulk_campaign'] = true;

            foreach ($users as $user) {
                if (!STAGEKITWP_MEMBERS_Notifications::should_notify(
                    $user->ID,
                    STAGEKITWP_MEMBERS_Notifications::TYPE_ANNOUNCEMENT,
                    $important
                )) {
                    $skipped++;
                    continue;
                }
                $jobs[] = [
                    'email' => $user->user_email,
                    'subject' => $subject,
                    'message' => $message,
                    'send_time' => $send_time,
                    'campaign_id' => $group ? $group_id : '',
                    'context' => $context,
                ];
            }

            if (!$subject || !$message) {
                echo '<div class="notice notice-error"><p>Enter both a subject and an email message.</p></div>';
            } elseif (!$jobs) {
                echo '<div class="notice notice-error"><p>No eligible recipients were found. Opted-out members are excluded unless an authorized sender marked this as important.</p></div>';
            } else {
                $queued = STAGEKITWP_MEMBERS_Email_Queue::add_batch($jobs);
                if (is_wp_error($queued)) {
                    echo '<div class="notice notice-error"><p>' . esc_html($queued->get_error_message()) . '</p></div>';
                } else {
                    if ($send_time <= time()) {
                        STAGEKITWP_MEMBERS_Email_Queue::process_queue();
                    }
                    $when = $send_time > time()
                        ? 'scheduled for ' . date_i18n('M j, Y g:i a', $send_time)
                        : 'queued for batched delivery';
                    echo '<div class="notice notice-success"><p>' . count($queued) . ' emails ' . esc_html($when) .
                        ($skipped ? ' (' . intval($skipped) . ' opted out)' : '') . '.</p></div>';
                }
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
        <?php $filters = STAGEKITWP_MEMBERS_Campaigns::get_filters($_GET); ?>
        <form method="get" class="stagekitwp-ma-search-form">
            <input type="text" name="stagekitwp_name" placeholder="Search by name or email"
                   value="<?php echo esc_attr($filters['name']); ?>">

            <select name="stagekitwp_interest">
                <option value="0">All interests</option>
                <?php
                $selected_interest = (int) $filters['interest'];
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

            <label>Role
                <select name="stagekitwp_role">
                    <option value="">All roles</option>
                    <?php foreach (wp_roles()->roles as $role_slug => $role_data) : ?>
                        <option value="<?php echo esc_attr($role_slug); ?>" <?php selected($filters['role'], $role_slug); ?>>
                            <?php echo esc_html(translate_user_role($role_data['name'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Availability date
                <input type="date" name="stagekitwp_availability_date" value="<?php echo esc_attr($filters['availability_date']); ?>">
            </label>
            <label>Status
                <select name="stagekitwp_availability_status">
                    <?php foreach (['' => 'Any availability', 'available' => 'Available', 'tentative' => 'Tentative', 'unavailable' => 'Unavailable'] as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($filters['availability_status'], $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php self::render_event_filter('RSVP', 'stagekitwp_rsvp_event_id', 'stagekitwp_rsvp_status', $filters['rsvp_event_id'], $filters['rsvp_status'], [
                '' => 'Any RSVP status', 'going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Declined', 'attended' => 'Attended',
            ]); ?>
            <?php self::render_event_filter('Volunteer', 'stagekitwp_volunteer_event_id', 'stagekitwp_volunteer_status', $filters['volunteer_event_id'], $filters['volunteer_status'], [
                '' => 'Any volunteer status', 'confirmed' => 'Confirmed', 'waitlist' => 'Waitlist', 'cancelled' => 'Cancelled',
            ]); ?>

            <label>Saved group preview
                <select name="stagekitwp_members_email_group">
                    <option value="">Current filters</option>
                    <?php foreach (STAGEKITWP_MEMBERS_Campaigns::get_groups() as $saved_group) : ?>
                        <option value="<?php echo esc_attr($saved_group['id']); ?>" <?php selected(isset($_GET['stagekitwp_members_email_group']) ? sanitize_text_field(wp_unslash($_GET['stagekitwp_members_email_group'])) : '', $saved_group['id']); ?>>
                            <?php echo esc_html($saved_group['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit">Search</button>
            <?php if (array_filter($filters)): ?>
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>">Reset</a>
            <?php endif; ?>
        </form>

        <?php if (current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)) : ?>
            <form method="post" class="stagekitwp-members-save-group">
                <?php wp_nonce_field('stagekitwp_members_save_group', 'stagekitwp_members_group_nonce'); ?>
                <?php self::render_hidden_filters($filters); ?>
                <label>Save these filters as a group
                    <input type="text" name="stagekitwp_members_group_name" maxlength="100" required>
                </label>
                <button class="button" name="stagekitwp_members_save_group" value="1">Save recipient group</button>
            </form>
        <?php endif; ?>

        <?php
        $result_users = self::get_filtered_users();
        $eligible_count = 0;
        foreach ($result_users as $preview_user) {
            if (STAGEKITWP_MEMBERS_Notifications::should_notify($preview_user->ID, STAGEKITWP_MEMBERS_Notifications::TYPE_ANNOUNCEMENT)) {
                $eligible_count++;
            }
        }
        echo '<p class="stagekitwp-ma-result-count">' . count($result_users) . ' member(s) matched; ' .
            $eligible_count . ' eligible, ' . (count($result_users) - $eligible_count) . ' opted out. Preview capped at ' .
            STAGEKITWP_MEMBERS_Campaigns::MAX_RECIPIENTS . ' recipients.</p>';
        ?>

        <hr>
		<?php
			if (
                isset($_POST['stagekitwp_members_test_email'], $_POST['stagekitwp_members_bulk_email_nonce']) &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_bulk_email_nonce'])), 'stagekitwp_members_bulk_email') &&
                current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)
            ) {
			    $current_user = wp_get_current_user();
			    STAGEKITWP_MEMBERS_Health::send_email(
			        $current_user->user_email,
			        isset($_POST['stagekitwp_members_bulk_subject']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_bulk_subject'])) : '',
			        isset($_POST['stagekitwp_members_bulk_message']) ? wp_kses_post(wp_unslash($_POST['stagekitwp_members_bulk_message'])) : ''
			    );
			    echo '<div class="notice notice-success"><p>Test email sent to yourself</p></div>';
			}
		?>
        <!-- =========================== BULK EMAIL =========================== -->
        <h3>Bulk Email Members</h3>

        <form method="post">

            <?php wp_nonce_field('stagekitwp_members_bulk_email', 'stagekitwp_members_bulk_email_nonce'); ?>
            <?php self::render_hidden_filters($filters); ?>

            <p>
                <label>Email recipient group
                    <select name="stagekitwp_members_email_group">
                        <option value="">Use the current search filters</option>
                        <?php foreach (STAGEKITWP_MEMBERS_Campaigns::get_groups() as $saved_group) : ?>
                            <option value="<?php echo esc_attr($saved_group['id']); ?>">
                                <?php echo esc_html($saved_group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </p>

            <p><strong>Optional merge context</strong></p>
            <p>
                <label>Event
                    <select name="stagekitwp_members_campaign_event">
                        <option value="0">No event</option>
                        <?php foreach (STAGEKITWP_MEMBERS_Campaigns::event_options() as $campaign_event) : ?>
                            <?php
                            $campaign_event_date = (string) get_post_meta($campaign_event->ID, '_stagekitwp_members_event_date', true);
                            $campaign_event_date = $campaign_event_date ? date_i18n(get_option('date_format'), strtotime($campaign_event_date)) : '';
                            ?>
                            <option value="<?php echo (int) $campaign_event->ID; ?>" data-date="<?php echo esc_attr($campaign_event_date); ?>"><?php echo esc_html($campaign_event->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Volunteer position
                    <select name="stagekitwp_members_campaign_position">
                        <option value="0">No position</option>
                        <?php foreach (STAGEKITWP_MEMBERS_Campaigns::position_options() as $position) : ?>
                            <option value="<?php echo (int) $position->id; ?>" data-event="<?php echo (int) $position->event_id; ?>">
                                <?php echo esc_html($position->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </p>

            <?php if (current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)) : ?>
                <p>Each campaign is capped at <?php echo (int) STAGEKITWP_MEMBERS_Campaigns::MAX_RECIPIENTS; ?> recipients and delivered by the queue in batches of <?php echo (int) STAGEKITWP_MEMBERS_Email_Queue::BATCH_SIZE; ?> per minute.</p>
            <?php endif; ?>

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

        <?php self::render_saved_groups(); ?>

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
			
            function escapeHTML(value) {
                return String(value).replace(/[&<>"']/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                })[char]);
            }

			function replaceVars(message, user = {}, context = {}, escapeValues = true) {
                const value = input => escapeValues ? escapeHTML(input) : String(input);
			    return message
			        .replaceAll('{name}', value(user?.name || 'John Doe'))
			        .replaceAll('{email}', value(user?.email || 'user@example.com'))
			        .replaceAll('{interests}', value(user?.interests || ''))
			        .replaceAll('{profile_link}', value(user?.profile || '#'))
			        .replaceAll('{site_name}', value('<?php echo esc_js(get_bloginfo("name")); ?>'))
                    .replaceAll('{event}', value(context?.event || ''))
                    .replaceAll('{position}', value(context?.position || ''))
                    .replaceAll('{date}', value(context?.date || ''));
			}


            // BULK TEMPLATE
            const bulkSelect = document.getElementById('stagekitwp-ma-template-select');
            const campaignEventSelect = document.querySelector('[name="stagekitwp_members_campaign_event"]');
            const campaignPositionSelect = document.querySelector('[name="stagekitwp_members_campaign_position"]');
            const filterCampaignPositions = function() {
                if (!campaignEventSelect || !campaignPositionSelect) return;
                Array.from(campaignPositionSelect.options).forEach(option => {
                    if (option.value !== '0') {
                        option.hidden = option.dataset.event !== campaignEventSelect.value;
                    }
                });
                const selected = campaignPositionSelect.selectedOptions[0];
                if (selected && selected.value !== '0' && selected.dataset.event !== campaignEventSelect.value) {
                    campaignPositionSelect.value = '0';
                }
            };
            campaignEventSelect?.addEventListener('change', filterCampaignPositions);
            filterCampaignPositions();
            if (bulkSelect) {
                bulkSelect.addEventListener('change', function() {
                    const opt = this.selectedOptions[0];
                    document.querySelector('[name="stagekitwp_members_bulk_subject"]').value = opt.dataset.subject || '';
                    tinymce.get('stagekitwp_members_bulk_message')?.setContent(opt.dataset.content || '');
                });
            }

            // BULK PREVIEW
            document.getElementById('stagekitwp-ma-preview-email')?.addEventListener('click', function() {

                let subject = document.querySelector('[name="stagekitwp_members_bulk_subject"]').value;
                let content = tinymce.get('stagekitwp_members_bulk_message')?.getContent() || '';
				content = decodeContent(content);
                const eventSelect = document.querySelector('[name="stagekitwp_members_campaign_event"]');
                const positionSelect = document.querySelector('[name="stagekitwp_members_campaign_position"]');
                const eventOption = eventSelect?.selectedOptions[0];
                const positionOption = positionSelect?.selectedOptions[0];
                const context = {
                    event: eventOption?.value !== '0' ? eventOption?.textContent || '' : '',
                    position: positionOption?.value !== '0' ? positionOption?.textContent || '' : '',
                    date: eventOption?.dataset.date || ''
                };
                const options = positionSelect ? Array.from(positionSelect.options) : [];
                options.forEach(option => {
                    if (option.value !== '0') {
                        option.hidden = option.dataset.event !== eventOption?.value;
                    }
                });
                if (positionOption?.value !== '0' && positionOption?.dataset.event !== eventOption?.value) {
                    positionSelect.value = '0';
                    context.position = '';
                }
                subject = replaceVars(subject, {}, context, false);
                content = replaceVars(content, {}, context);

                const box = document.getElementById('stagekitwp-ma-preview-container');
                box.style.display = 'block';
                box.innerHTML = `<h3>${escapeHTML(subject)}</h3><hr>${content}`;
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
        $group_id = isset($_GET['stagekitwp_members_email_group'])
            ? sanitize_text_field(wp_unslash($_GET['stagekitwp_members_email_group']))
            : '';
        $group = $group_id ? STAGEKITWP_MEMBERS_Campaigns::get_group($group_id) : null;
        $filters = $group
            ? STAGEKITWP_MEMBERS_Campaigns::normalize_filters($group['filters'])
            : STAGEKITWP_MEMBERS_Campaigns::get_filters($_GET);
        return STAGEKITWP_MEMBERS_Campaigns::query_users($filters);
    }

    private static function render_hidden_filters($filters) {
        $names = [
            'name' => 'stagekitwp_name',
            'interest' => 'stagekitwp_interest',
            'role' => 'stagekitwp_role',
            'availability_date' => 'stagekitwp_availability_date',
            'availability_status' => 'stagekitwp_availability_status',
            'rsvp_event_id' => 'stagekitwp_rsvp_event_id',
            'rsvp_status' => 'stagekitwp_rsvp_status',
            'volunteer_event_id' => 'stagekitwp_volunteer_event_id',
            'volunteer_status' => 'stagekitwp_volunteer_status',
        ];
        foreach ($names as $key => $name) {
            printf(
                '<input type="hidden" name="%s" value="%s">',
                esc_attr($name),
                esc_attr($filters[$key])
            );
        }
    }

    private static function render_event_filter($label, $event_field, $status_field, $selected_event, $selected_status, $statuses) {
        ?>
        <label><?php echo esc_html($label); ?> event
            <select name="<?php echo esc_attr($event_field); ?>">
                <option value="0">Any event</option>
                <?php foreach (STAGEKITWP_MEMBERS_Campaigns::event_options() as $event) : ?>
                    <option value="<?php echo (int) $event->ID; ?>" <?php selected((int) $selected_event, (int) $event->ID); ?>>
                        <?php echo esc_html($event->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?php echo esc_html($label); ?> status
            <select name="<?php echo esc_attr($status_field); ?>">
                <?php foreach ($statuses as $value => $status_label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_status, $value); ?>><?php echo esc_html($status_label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php
    }

    private static function render_saved_groups() {
        if (!current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL)) {
            return;
        }
        $groups = STAGEKITWP_MEMBERS_Campaigns::get_groups();
        if (!$groups) {
            return;
        }
        echo '<section class="stagekitwp-members-email-groups"><h3>Saved recipient groups</h3><ul>';
        foreach ($groups as $group) {
            $users = STAGEKITWP_MEMBERS_Campaigns::query_users($group['filters']);
            $counts = STAGEKITWP_MEMBERS_Email_Log::group_counts($group['id']);
            echo '<li><strong>' . esc_html($group['name']) . '</strong>: ' . count($users) .
                ' matched; ' . (int) $counts['success'] . ' sent, ' . (int) $counts['failed'] . ' failed.';
            if (current_user_can('manage_options')) {
                $history_url = add_query_arg([
                    'page' => 'stagekitwp-ma-admin',
                    'stagekitwp_members_tab' => 'email_logs',
                    'stagekitwp_members_group' => $group['id'],
                ], admin_url('admin.php'));
                echo ' <a href="' . esc_url($history_url) . '">View history</a>';
            }
            echo '<form method="post" style="display:inline;margin-left:1em">';
            wp_nonce_field('stagekitwp_members_save_group', 'stagekitwp_members_group_nonce');
            echo '<button class="button-link-delete" name="stagekitwp_members_delete_group" value="' . esc_attr($group['id']) . '" onclick="return confirm(\'Delete this recipient group? Email history is retained.\')">Delete</button>';
            echo '</form></li>';
        }
        echo '</ul></section>';
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