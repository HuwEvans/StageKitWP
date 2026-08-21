<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Messaging {

    public static function init() {
        add_action('init', [__CLASS__, 'register']);
        add_shortcode('stagekitwp_members_conversations', [__CLASS__, 'conversation_list']);
        add_shortcode('stagekitwp_members_conversation', [__CLASS__, 'conversation_thread']);
        add_action('wp_insert_post', [__CLASS__, 'notify_new_conversation'], 10, 3);
        add_action('comment_post', [__CLASS__, 'notify_new_reply'], 10, 3);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    /**
     * Load messaging styles only on pages using our shortcodes.
     */
    public static function enqueue_assets() {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if (!$post) {
            return;
        }
        if (
            has_shortcode($post->post_content, 'stagekitwp_members_conversation') ||
            has_shortcode($post->post_content, 'stagekitwp_members_conversations')
        ) {
            wp_enqueue_style(
                'stagekitwp-ma-messaging',
                STAGEKITWPMA_URL . 'assets/css/messaging.css',
                [],
                STAGEKITWPMA_VERSION
            );
        }
    }

    /**
     * True when the current (or given) user participates in a conversation.
     */
    public static function is_participant($post_id, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id) {
            return false;
        }
        $participants = (array) get_post_meta($post_id, 'stagekitwp_members_participants', true);
        return in_array((int) $user_id, array_map('intval', $participants), true);
    }

    /**
     * URL of the page hosting [stagekitwp_members_conversation], with the id appended.
     */
    public static function thread_url($post_id) {
        $page = get_page_by_path('messages');
        $base = $page ? get_permalink($page) : home_url('/messages/');
        $base = apply_filters('stagekitwp_members_messages_url', $base);
        return add_query_arg('conversation', (int) $post_id, $base);
    }

    /**
     * Register CPT
     */
    public static function register() {

        register_post_type('stagekitwp_conv', [
            'label' => 'Conversations',
            'public' => false,
            'supports' => ['title', 'editor', 'comments'],
        ]);
    }

    /**
     * Create conversation
     */
    public static function create_conversation($user_ids = [], $title = 'Conversation', $first_message = '') {

        // Normalise: unique integer participant IDs.
        $user_ids = array_values(array_unique(array_map('intval', (array) $user_ids)));
        $user_ids = array_filter($user_ids);

        $post_id = wp_insert_post([
            'post_type'   => 'stagekitwp_conv',
            'post_title'  => $title !== '' ? $title : 'Conversation',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            return $post_id;
        }

        update_post_meta($post_id, 'stagekitwp_members_participants', $user_ids);

        // Optionally seed the thread with an opening message.
        if ($first_message !== '') {
            self::add_message($post_id, get_current_user_id(), $first_message);
        }

        return $post_id;
    }

    /**
     * Post a message into a conversation as a comment. Returns comment ID.
     */
    public static function add_message($post_id, $user_id, $content) {

        $content = trim(wp_kses_post($content));
        if ($content === '') {
            return 0;
        }

        $user = get_userdata($user_id);

        return wp_insert_comment([
            'comment_post_ID'      => (int) $post_id,
            'user_id'              => (int) $user_id,
            'comment_author'       => $user ? $user->display_name : 'Member',
            'comment_author_email' => $user ? $user->user_email : '',
            'comment_content'      => $content,
            'comment_approved'     => 1,
            'comment_type'         => 'stagekitwp_members_message',
        ]);
    }

    /**
     * Show list of conversations
     */
    public static function conversation_list() {

        if (!is_user_logged_in()) {
            return '<p>Please log in.</p>';
        }

        $user_id = get_current_user_id();

        // Participants are stored as a serialized array of integers, e.g.
        // a:2:{i:0;i:9;i:1;i:10;}. Match the value token "i:<id>;" so the
        // LIKE hits the integer, not a quoted string.
        $query = new WP_Query([
            'post_type'      => 'stagekitwp_conv',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'stagekitwp_members_participants',
                    'value'   => 'i:' . (int) $user_id . ';',
                    'compare' => 'LIKE',
                ]
            ]
        ]);

        ob_start();

        echo '<div class="stagekitwp-ma-conversations">';
        echo '<h3>Your Conversations</h3>';

        if ($query->have_posts()) {

            echo '<ul class="stagekitwp-ma-conversation-list">';

            while ($query->have_posts()) {
                $query->the_post();
                $cid = get_the_ID();

                $others = self::other_participant_names($cid, $user_id);
                $count  = get_comments_number($cid);

                echo '<li class="stagekitwp-ma-conversation-item">';
                echo '<a href="' . esc_url(self::thread_url($cid)) . '">';
                echo '<span class="stagekitwp-ma-conversation-title">' . esc_html(get_the_title()) . '</span>';
                if ($others !== '') {
                    echo ' <span class="stagekitwp-ma-conversation-with">with ' . esc_html($others) . '</span>';
                }
                echo ' <span class="stagekitwp-ma-conversation-count">(' . intval($count) . ' messages)</span>';
                echo '</a></li>';
            }

            echo '</ul>';

        } else {
            echo '<p>No conversations found.</p>';
        }

        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }
    /**
     * Comma-separated names of the other participants in a conversation.
     */
    public static function other_participant_names($post_id, $exclude_user_id) {
        $ids   = (array) get_post_meta($post_id, 'stagekitwp_members_participants', true);
        $names = [];
        foreach ($ids as $id) {
            if ((int) $id === (int) $exclude_user_id) {
                continue;
            }
            $u = get_userdata($id);
            if ($u) {
                $names[] = $u->display_name;
            }
        }
        return implode(', ', $names);
    }

    /**
     * Full conversation thread + reply form. Participant-guarded.
     * Reads the conversation id from ?conversation= or shortcode att id.
     */
    public static function conversation_thread($atts = []) {

        if (!is_user_logged_in()) {
            return '<p>Please log in to view messages.</p>';
        }

        $atts    = shortcode_atts(['id' => 0], $atts);
        $post_id = (int) $atts['id'];
        if (!$post_id && isset($_GET['conversation'])) {
            $post_id = (int) $_GET['conversation'];
        }

        if (!$post_id) {
            // No specific conversation selected: fall back to the list.
            return self::conversation_list();
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'stagekitwp_conv') {
            return '<p>Conversation not found.</p>';
        }

        $user_id = get_current_user_id();
        if (!self::is_participant($post_id, $user_id)) {
            return '<p>You do not have access to this conversation.</p>';
        }

        // Handle a reply submission.
        $notice = '';
        if (
            isset($_POST['stagekitwp_members_reply_submit']) &&
            isset($_POST['stagekitwp_members_reply_nonce']) &&
            wp_verify_nonce($_POST['stagekitwp_members_reply_nonce'], 'stagekitwp_members_reply_' . $post_id)
        ) {
            $body = isset($_POST['stagekitwp_members_reply']) ? wp_kses_post(wp_unslash($_POST['stagekitwp_members_reply'])) : '';
            if (trim($body) !== '') {
                self::add_message($post_id, $user_id, $body);
                $notice = 'Message sent.';
            } else {
                $notice = 'Please enter a message.';
            }
        }

        $messages = get_comments([
            'post_id' => $post_id,
            'status'  => 'approve',
            'orderby' => 'comment_date',
            'order'   => 'ASC',
        ]);

        ob_start();
        ?>
        <div class="stagekitwp-ma-thread">

            <p class="stagekitwp-ma-thread-back">
                <a href="<?php echo esc_url(self::messages_base_url()); ?>">&larr; All conversations</a>
            </p>

            <h3 class="stagekitwp-ma-thread-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
            <p class="stagekitwp-ma-thread-with">With: <?php echo esc_html(self::other_participant_names($post_id, $user_id)); ?></p>

            <?php if ($notice): ?>
                <p class="stagekitwp-ma-thread-notice"><?php echo esc_html($notice); ?></p>
            <?php endif; ?>

            <div class="stagekitwp-ma-messages">
                <?php if (empty($messages)): ?>
                    <p class="stagekitwp-ma-no-messages">No messages yet. Start the conversation below.</p>
                <?php else: foreach ($messages as $m):
                    $mine = ((int) $m->user_id === (int) $user_id);
                ?>
                    <div class="stagekitwp-ma-message <?php echo $mine ? 'is-mine' : 'is-theirs'; ?>">
                        <div class="stagekitwp-ma-message-meta">
                            <strong><?php echo esc_html($m->comment_author); ?></strong>
                            <span class="stagekitwp-ma-message-date"><?php echo esc_html(mysql2date('M j, Y g:i a', $m->comment_date)); ?></span>
                        </div>
                        <div class="stagekitwp-ma-message-body"><?php echo wp_kses_post(wpautop($m->comment_content)); ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <form method="post" class="stagekitwp-ma-reply-form">
                <?php wp_nonce_field('stagekitwp_members_reply_' . $post_id, 'stagekitwp_members_reply_nonce'); ?>
                <p>
                    <label for="stagekitwp-ma-reply">Your message</label><br>
                    <textarea id="stagekitwp-ma-reply" name="stagekitwp_members_reply" rows="4" style="width:100%;" required></textarea>
                </p>
                <p>
                    <button type="submit" name="stagekitwp_members_reply_submit">Send</button>
                </p>
            </form>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Base URL of the messages page (no conversation param).
     */
    public static function messages_base_url() {
        $page = get_page_by_path('messages');
        $base = $page ? get_permalink($page) : home_url('/messages/');
        return apply_filters('stagekitwp_members_messages_url', $base);
    }

	public static function notify_new_conversation($post_id, $post, $update) {

	    // ✅ Only new conversations
	    if ($update || $post->post_type !== 'stagekitwp_conv') {
	        return;
	    }
	
	    $participants = get_post_meta($post_id, 'stagekitwp_members_participants', true);
	
	    if (empty($participants)) {
	        return;
	    }
	
	    foreach ($participants as $user_id) {
	
	        // Don't notify the person who created it.
	        if ((int) $user_id === get_current_user_id()) continue;
	
	        $user = get_userdata($user_id);
	        if (!$user) continue;
	
	        // Respect the member's notification preference.
	        if (!STAGEKITWP_MEMBERS_Notifications::should_notify($user->ID, STAGEKITWP_MEMBERS_Notifications::TYPE_CONVERSATION)) {
	            continue;
	        }
	
	        $body = 'A new conversation has been started with you.' . "\n\n" . self::thread_url($post_id);
	        if (class_exists('STAGEKITWP_MEMBERS_Health')) {
	            STAGEKITWP_MEMBERS_Health::send_email($user->user_email, 'New Conversation Started', $body);
	        } else {
	            wp_mail($user->user_email, 'New Conversation Started', $body);
	        }
	    }
	}
	
	public static function notify_new_reply($comment_ID, $comment_approved, $commentdata) {

	    if ($comment_approved != 1) {
	        return;
	    }
	
	    $post_id = $commentdata['comment_post_ID'];
	    $post    = get_post($post_id);
	
	    if ($post->post_type !== 'stagekitwp_conv') {
	        return;
	    }
	
	    $participants = get_post_meta($post_id, 'stagekitwp_members_participants', true);
	
	    if (empty($participants)) {
	        return;
	    }
	
	    $comment_user_id = $commentdata['user_id'];
	
	    foreach ($participants as $user_id) {
	
	        // ✅ Don't email sender
	        if ($user_id == $comment_user_id) {
	            continue;
	        }
	
	        $user = get_userdata($user_id);
	        if (!$user) continue;
	
	        // Respect the member's notification preference.
	        if (!STAGEKITWP_MEMBERS_Notifications::should_notify($user->ID, STAGEKITWP_MEMBERS_Notifications::TYPE_MESSAGE)) {
	            continue;
	        }
	
	        $body = 'You have a new reply in your conversation.' . "\n\n" . self::thread_url($post_id);
	        if (class_exists('STAGEKITWP_MEMBERS_Health')) {
	            STAGEKITWP_MEMBERS_Health::send_email($user->user_email, 'New Message Received', $body);
	        } else {
	            wp_mail($user->user_email, 'New Message Received', $body);
	        }
	    }
	}
}