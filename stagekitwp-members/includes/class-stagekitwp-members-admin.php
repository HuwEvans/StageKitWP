<?php

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-stagekitwp-members-admin.php';
return;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class STAGEKITWP_MEMBERS_Admin {

    public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 10 );
		add_action( 'admin_menu', [ __CLASS__, 'hide_top_level_menu' ], 9999 );
		add_action( 'admin_post_stagekitwp_members_save_announcement', [ __CLASS__, 'handle_save_announcement' ] );
		add_action( 'admin_post_stagekitwp_members_save_event', [ __CLASS__, 'handle_save_event' ] );
		add_action( 'admin_post_stagekitwp_members_save_email_template', [ __CLASS__, 'handle_save_email_template' ] );
    }

    public static function register_menu() {
        add_menu_page(
			'Members',
			'Members',
			STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS,
			'stagekitwp-ma-admin',
			[ __CLASS__, 'render' ],
			'dashicons-groups',
			26
		);

        add_submenu_page(
			'stagekitwp-core',
			'Instructions',
			'Members',
			STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS,
			'stagekitwp-ma-admin',
			[ __CLASS__, 'render' ]
		);
    }

    public static function hide_top_level_menu() {
		if ( current_user_can( 'manage_options' ) ) {
			remove_menu_page( 'stagekitwp-ma-admin' );
		}
    }

    public static function render() {
		$hub_tabs = self::get_tabs();
		$active_tab = isset( $_GET['stagekitwp_members_tab'] ) ? sanitize_key( wp_unslash( $_GET['stagekitwp_members_tab'] ) ) : 'overview';
		if ( ! isset( $hub_tabs[ $active_tab ] ) ) {
			$active_tab = 'overview';
		}
        ?>
		<div class="wrap stagekitwp-ma-hub">
			<h1>Members</h1>
			<nav class="nav-tab-wrapper stagekitwp-ma-hub-tabs" role="tablist" style="margin-bottom: 16px;">
				<?php foreach ( $hub_tabs as $key => $tab ) : ?>
					<button type="button" class="nav-tab<?php echo esc_attr( $key === $active_tab ? ' nav-tab-active' : '' ); ?>" data-stagekitwp-ma-tab="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $tab['label'] ); ?></button>
				<?php endforeach; ?>
			</nav>

			<div class="stagekitwp-ma-hub-panels">
				<?php foreach ( $hub_tabs as $key => $tab ) : ?>
					<div class="stagekitwp-ma-hub-panel" data-stagekitwp-ma-panel="<?php echo esc_attr( $key ); ?>" style="<?php echo $key === $active_tab ? '' : 'display:none;'; ?>">
						<?php echo self::render_tab_panel( $key ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<script>
		(function() {
			const tabs = document.querySelectorAll('[data-stagekitwp-ma-tab]');
			const panels = document.querySelectorAll('[data-stagekitwp-ma-panel]');
			if (!tabs.length || !panels.length) return;
			function activate(tabKey) {
				tabs.forEach(tab => tab.classList.toggle('nav-tab-active', tab.dataset.stagekitwpMaTab === tabKey));
				panels.forEach(panel => panel.style.display = panel.dataset.stagekitwpMaPanel === tabKey ? '' : 'none');
				const url = new URL(window.location.href);
				url.searchParams.set('stagekitwp_members_tab', tabKey);
				history.replaceState({}, '', url.toString());
			}
			tabs.forEach(tab => tab.addEventListener('click', function() {
				activate(this.dataset.stagekitwpMaTab);
			}));
			activate('<?php echo esc_js( $active_tab ); ?>');
		})();
		</script>
        <?php
    }

    private static function get_tabs() {
		$tabs = array(
			'overview'        => array( 'label' => 'Overview', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS, 'callback' => [ __CLASS__, 'render_overview_panel' ] ),
			'members'         => array( 'label' => 'Members', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS, 'callback' => [ 'STAGEKITWP_MEMBERS_Members', 'render' ] ),
			'invitations'     => array( 'label' => 'Invitations', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS, 'callback' => [ 'STAGEKITWP_MEMBERS_Invitations_Admin', 'render' ] ),
			'export'          => array( 'label' => 'Export', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS, 'callback' => [ 'STAGEKITWP_MEMBERS_Export', 'render' ] ),
			'email_queue'     => array( 'label' => 'Email Queue', 'cap' => 'manage_options', 'callback' => [ 'STAGEKITWP_MEMBERS_Email_Queue_Admin', 'render' ] ),
			'email_logs'      => array( 'label' => 'Email Logs', 'cap' => 'manage_options', 'callback' => [ 'STAGEKITWP_MEMBERS_Email_Log', 'render' ] ),
			'settings'        => array( 'label' => 'Settings', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_SETTINGS, 'callback' => [ 'STAGEKITWP_MEMBERS_Settings', 'render' ] ),
			'health'          => array( 'label' => 'Health', 'cap' => 'manage_options', 'callback' => [ 'STAGEKITWP_MEMBERS_Health', 'render' ] ),
			'announcements'   => array( 'label' => 'Announcements', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS, 'callback' => [ __CLASS__, 'render_announcements_panel' ] ),
			'events'          => array( 'label' => 'Events', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS, 'callback' => [ __CLASS__, 'render_events_panel' ] ),
			'email_templates' => array( 'label' => 'Email Templates', 'cap' => STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS, 'callback' => [ __CLASS__, 'render_email_templates_panel' ] ),
		);

		$visible = array();
		foreach ( $tabs as $key => $tab ) {
			if ( current_user_can( $tab['cap'] ) ) {
				$visible[ $key ] = $tab;
			}
		}

		return $visible;
    }

    private static function capture_render( $callback ) {
		if ( ! is_callable( $callback ) ) {
			return '<p>Content unavailable.</p>';
		}

		ob_start();
		call_user_func( $callback );
		return ob_get_clean();
    }

    private static function render_tab_panel( $key ) {
		$hub_tabs = self::get_tabs();
		if ( ! isset( $hub_tabs[ $key ] ) ) {
			return '';
		}

		return self::capture_render( $hub_tabs[ $key ]['callback'] );
    }

    private static function render_overview_panel() {
		?>
		<h2>Overview</h2>
		<p>This plugin provides a complete member management system integrated with WordPress users and StageKitWP Core.</p>

		<hr>

		<h2>User Roles</h2>
		<ul>
			<li><strong>Member:</strong> Can manage their profile and RSVP to events.</li>
			<li><strong>Producer:</strong> Can search members and manage participation.</li>
			<li><strong>Executive:</strong> Full administrative access.</li>
		</ul>

		<hr>

		<h2>Shortcodes</h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Feature</th>
					<th>Shortcode</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Profile Editor</td>
					<td><code>[stagekitwp_members_profile]</code></td>
					<td>Allows members to edit their profile (bio, resume, image).</td>
				</tr>
				<tr>
					<td>Member Search</td>
					<td><code>[stagekitwp_member_search]</code></td>
					<td>Producer tool to search members.</td>
				</tr>
				<tr>
					<td>Event RSVP</td>
					<td><code>[stagekitwp_members_rsvp]</code></td>
					<td>Displays RSVP button on event pages.</td>
				</tr>
				<tr>
					<td>Conversations</td>
					<td><code>[stagekitwp_members_conversations]</code></td>
					<td>Displays user conversations list.</td>
				</tr>
			</tbody>
		</table>

		<hr>

		<h2>Profiles</h2>
		<p>Members can update:</p>
		<ul>
			<li>Bio (rich text)</li>
			<li>Resume (rich text)</li>
			<li>Profile image upload</li>
		</ul>

		<hr>

		<h2>Events & RSVP</h2>
		<ul>
			<li>Events are managed via the <strong>Events</strong> custom post type.</li>
			<li>Add <code>[stagekitwp_members_rsvp]</code> to event pages.</li>
			<li>Users can RSVP once per event.</li>
		</ul>

		<hr>

		<h2>Invitations</h2>
		<ul>
			<li>Invitations are sent via email with secure tokens.</li>
			<li>Tokens expire automatically.</li>
			<li>Users must confirm before becoming active.</li>
		</ul>

		<hr>

		<h2>Messaging System</h2>
		<ul>
			<li>Producers can start conversations with members from the Producer Dashboard.</li>
			<li>Messages are handled via WordPress comments.</li>
			<li>Users will receive email notifications when a new conversation is started or a new reply is added.</li>
			<li>Conversations are private and only visible to participants.</li>
		</ul>

		<hr>

		<h2>Security</h2>
		<ul>
			<li>Nonce validation on all forms</li>
			<li>Capability checks enforced</li>
			<li>Sanitized inputs and escaped outputs</li>
		</ul>

		<h2>Health Check</h2>
		<ul>
			<li>Use the "Send Test Email" button to verify email functionality.</li>
			<li>If email fails, install an SMTP plugin (e.g., WP Mail SMTP).</li>
			<li>The system will alert you if email notifications are not working.</li>
		</ul>
		<?php
    }

    private static function render_announcements_panel() {
		$items = get_posts( array(
			'post_type'      => 'stagekitwp_ann',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		$selected_id = isset( $_GET['stagekitwp_members_item'] ) ? absint( wp_unslash( $_GET['stagekitwp_members_item'] ) ) : 0;
		$create_new  = isset( $_GET['stagekitwp_members_new'] ) && '1' === (string) wp_unslash( $_GET['stagekitwp_members_new'] );
		$selected    = $selected_id ? get_post( $selected_id ) : null;
		if ( $selected && 'stagekitwp_ann' !== $selected->post_type ) {
			$selected = null;
		}
		if ( ! $selected && ! $create_new ) {
			$selected = $items ? $items[0] : null;
		}
		if ( $selected ) {
			$selected_id = (int) $selected->ID;
		}
		$send_email = $selected_id ? get_post_meta( $selected_id, '_stagekitwp_members_send_email', true ) : '1';
		$important  = $selected_id ? get_post_meta( $selected_id, '_stagekitwp_members_important', true ) : '0';
		?>
		<h2>Announcements</h2>
		<div class="stagekitwp-ma-inline-shell" style="display:grid;grid-template-columns:minmax(300px,340px) minmax(0,1fr);gap:20px;align-items:start;">
			<aside class="stagekitwp-ma-inline-list" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:16px;">
				<p><a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-ma-admin', 'stagekitwp_members_tab' => 'announcements', 'stagekitwp_members_new' => '1' ), admin_url( 'admin.php' ) ) ); ?>">Add New Announcement</a></p>
				<table class="widefat striped">
					<thead><tr><th>Title</th><th>Status</th><th>Date</th><th></th></tr></thead>
					<tbody>
						<?php if ( empty( $items ) ) : ?>
							<tr><td colspan="4">No announcements found.</td></tr>
						<?php else : foreach ( $items as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item->post_title ); ?></td>
								<td><?php echo esc_html( $item->post_status ); ?></td>
								<td><?php echo esc_html( get_the_date( '', $item ) ); ?></td>
								<td><a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-ma-admin', 'stagekitwp_members_tab' => 'announcements', 'stagekitwp_members_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>">Edit</a></td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</aside>
			<div class="stagekitwp-ma-inline-editor" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:16px;">
				<?php if ( ! $selected && ! $create_new ) : ?>
					<p>No announcement selected yet. Choose one from the list or add a new one.</p>
				<?php else : ?>
					<?php $title = $selected ? $selected->post_title : ''; $content = $selected ? $selected->post_content : ''; $status = $selected ? $selected->post_status : 'draft'; ?>
					<h3><?php echo $selected ? esc_html( $selected->post_title ) : 'New Announcement'; ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'stagekitwp_members_save_announcement', 'stagekitwp_members_nonce' ); ?>
						<input type="hidden" name="action" value="stagekitwp_members_save_announcement">
						<input type="hidden" name="stagekitwp_members_item_id" value="<?php echo (int) $selected_id; ?>">
						<input type="hidden" name="stagekitwp_members_redirect_tab" value="announcements">
						<p><label><strong>Title</strong><br><input type="text" name="stagekitwp_members_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>"></label></p>
						<p><label><strong>Status</strong><br><select name="stagekitwp_members_status"><option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option><option value="publish" <?php selected( $status, 'publish' ); ?>>Publish</option><option value="private" <?php selected( $status, 'private' ); ?>>Private</option></select></label></p>
						<p><label><strong>Content</strong><br><textarea name="stagekitwp_members_content" rows="10" class="large-text"><?php echo esc_textarea( $content ); ?></textarea></label></p>
						<p><label><input type="checkbox" name="stagekitwp_members_send_email" value="1" <?php checked( $send_email, '1' ); ?>> Also email members</label></p>
						<p><label><input type="checkbox" name="stagekitwp_members_important" value="1" <?php checked( $important, '1' ); ?>> Important (bypass opt-out)</label></p>
						<p class="submit"><button type="submit" class="button button-primary">Save Announcement</button></p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
    }

    private static function render_events_panel() {
		$items = get_posts( array(
			'post_type'      => 'stagekitwp_event',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		$selected_id = isset( $_GET['stagekitwp_members_item'] ) ? absint( wp_unslash( $_GET['stagekitwp_members_item'] ) ) : 0;
		$create_new  = isset( $_GET['stagekitwp_members_new'] ) && '1' === (string) wp_unslash( $_GET['stagekitwp_members_new'] );
		$selected    = $selected_id ? get_post( $selected_id ) : null;
		if ( $selected && 'stagekitwp_event' !== $selected->post_type ) {
			$selected = null;
		}
		if ( ! $selected && ! $create_new ) {
			$selected = $items ? $items[0] : null;
		}
		if ( $selected ) {
			$selected_id = (int) $selected->ID;
		}
		$date       = $selected_id ? get_post_meta( $selected_id, '_stagekitwp_members_event_date', true ) : '';
		$start_time = $selected_id ? get_post_meta( $selected_id, '_stagekitwp_members_start_time', true ) : '';
		$end_time   = $selected_id ? get_post_meta( $selected_id, '_stagekitwp_members_end_time', true ) : '';
		$visibility = $selected_id ? get_post_meta( $selected_id, STAGEKITWP_MEMBERS_Events::META_VISIBILITY, true ) : 'private';
		?>
		<h2>Events</h2>
		<div class="stagekitwp-ma-inline-shell" style="display:grid;grid-template-columns:minmax(300px,340px) minmax(0,1fr);gap:20px;align-items:start;">
			<aside class="stagekitwp-ma-inline-list" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:16px;">
				<p><a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-ma-admin', 'stagekitwp_members_tab' => 'events', 'stagekitwp_members_new' => '1' ), admin_url( 'admin.php' ) ) ); ?>">Add New Event</a></p>
				<table class="widefat striped">
					<thead><tr><th>Title</th><th>Status</th><th>Date</th><th></th></tr></thead>
					<tbody>
						<?php if ( empty( $items ) ) : ?>
							<tr><td colspan="4">No events found.</td></tr>
						<?php else : foreach ( $items as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item->post_title ); ?></td>
								<td><?php echo esc_html( $item->post_status ); ?></td>
								<td><?php echo esc_html( get_the_date( '', $item ) ); ?></td>
								<td><a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-ma-admin', 'stagekitwp_members_tab' => 'events', 'stagekitwp_members_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>">Edit</a></td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</aside>
			<div class="stagekitwp-ma-inline-editor" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:16px;">
				<?php if ( ! $selected && ! $create_new ) : ?>
					<p>No event selected yet. Choose one from the list or add a new one.</p>
				<?php else : ?>
					<?php $title = $selected ? $selected->post_title : ''; $content = $selected ? $selected->post_content : ''; $status = $selected ? $selected->post_status : 'draft'; ?>
					<h3><?php echo $selected ? esc_html( $selected->post_title ) : 'New Event'; ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'stagekitwp_members_save_event', 'stagekitwp_members_nonce' ); ?>
						<input type="hidden" name="action" value="stagekitwp_members_save_event">
						<input type="hidden" name="stagekitwp_members_item_id" value="<?php echo (int) $selected_id; ?>">
						<input type="hidden" name="stagekitwp_members_redirect_tab" value="events">
						<p><label><strong>Title</strong><br><input type="text" name="stagekitwp_members_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>"></label></p>
						<p><label><strong>Status</strong><br><select name="stagekitwp_members_status"><option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option><option value="publish" <?php selected( $status, 'publish' ); ?>>Publish</option><option value="private" <?php selected( $status, 'private' ); ?>>Private</option></select></label></p>
						<p><label><strong>Date</strong><br><input type="date" name="stagekitwp_members_event_date" value="<?php echo esc_attr( $date ); ?>"></label></p>
						<p><label><strong>Start Time</strong><br><input type="time" name="stagekitwp_members_start_time" value="<?php echo esc_attr( $start_time ); ?>"></label></p>
						<p><label><strong>End Time</strong><br><input type="time" name="stagekitwp_members_end_time" value="<?php echo esc_attr( $end_time ); ?>"></label></p>
						<p><label><strong>Visibility</strong><br><select name="stagekitwp_members_event_visibility"><option value="private" <?php selected( $visibility, 'private' ); ?>>Private</option><option value="public" <?php selected( $visibility, 'public' ); ?>>Public</option></select></label></p>
						<p><label><strong>Details</strong><br><textarea name="stagekitwp_members_content" rows="8" class="large-text"><?php echo esc_textarea( $content ); ?></textarea></label></p>
						<p class="submit"><button type="submit" class="button button-primary">Save Event</button></p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
    }

    private static function render_email_templates_panel() {
		$items = get_posts( array(
			'post_type'      => 'stagekitwp_email',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		$selected_id = isset( $_GET['stagekitwp_members_item'] ) ? absint( wp_unslash( $_GET['stagekitwp_members_item'] ) ) : 0;
		$create_new  = isset( $_GET['stagekitwp_members_new'] ) && '1' === (string) wp_unslash( $_GET['stagekitwp_members_new'] );
		$selected    = $selected_id ? get_post( $selected_id ) : null;
		if ( $selected && 'stagekitwp_email' !== $selected->post_type ) {
			$selected = null;
		}
		if ( ! $selected && ! $create_new ) {
			$selected = $items ? $items[0] : null;
		}
		if ( $selected ) {
			$selected_id = (int) $selected->ID;
		}
		$subject = $selected_id ? get_post_meta( $selected_id, '_stagekitwp_members_subject', true ) : '';
		?>
		<h2>Email Templates</h2>
		<div class="stagekitwp-ma-inline-shell" style="display:grid;grid-template-columns:minmax(300px,340px) minmax(0,1fr);gap:20px;align-items:start;">
			<aside class="stagekitwp-ma-inline-list" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:16px;">
				<p><a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-ma-admin', 'stagekitwp_members_tab' => 'email_templates', 'stagekitwp_members_new' => '1' ), admin_url( 'admin.php' ) ) ); ?>">Add New Template</a></p>
				<table class="widefat striped">
					<thead><tr><th>Title</th><th>Status</th><th>Date</th><th></th></tr></thead>
					<tbody>
						<?php if ( empty( $items ) ) : ?>
							<tr><td colspan="4">No templates found.</td></tr>
						<?php else : foreach ( $items as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item->post_title ); ?></td>
								<td><?php echo esc_html( $item->post_status ); ?></td>
								<td><?php echo esc_html( get_the_date( '', $item ) ); ?></td>
								<td><a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-ma-admin', 'stagekitwp_members_tab' => 'email_templates', 'stagekitwp_members_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>">Edit</a></td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</aside>
			<div class="stagekitwp-ma-inline-editor" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:16px;">
				<?php if ( ! $selected && ! $create_new ) : ?>
					<p>No template selected yet. Choose one from the list or add a new one.</p>
				<?php else : ?>
					<?php $title = $selected ? $selected->post_title : ''; $content = $selected ? $selected->post_content : ''; $status = $selected ? $selected->post_status : 'draft'; ?>
					<h3><?php echo $selected ? esc_html( $selected->post_title ) : 'New Template'; ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'stagekitwp_members_save_email_template', 'stagekitwp_members_nonce' ); ?>
						<input type="hidden" name="action" value="stagekitwp_members_save_email_template">
						<input type="hidden" name="stagekitwp_members_item_id" value="<?php echo (int) $selected_id; ?>">
						<input type="hidden" name="stagekitwp_members_redirect_tab" value="email_templates">
						<p><label><strong>Title</strong><br><input type="text" name="stagekitwp_members_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>"></label></p>
						<p><label><strong>Status</strong><br><select name="stagekitwp_members_status"><option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option><option value="publish" <?php selected( $status, 'publish' ); ?>>Publish</option><option value="private" <?php selected( $status, 'private' ); ?>>Private</option></select></label></p>
						<p><label><strong>Subject</strong><br><input type="text" name="stagekitwp_members_subject" class="regular-text" value="<?php echo esc_attr( $subject ); ?>"></label></p>
						<p><label><strong>Body</strong><br><textarea name="stagekitwp_members_content" rows="10" class="large-text"><?php echo esc_textarea( $content ); ?></textarea></label></p>
						<p class="submit"><button type="submit" class="button button-primary">Save Template</button></p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
    }

	private static function redirect_to_tab( $tab, $item_id = 0 ) {
		$args = array(
			'page'       => 'stagekitwp-ma-admin',
			'stagekitwp_members_tab'  => $tab,
		);
		if ( $item_id ) {
			$args['stagekitwp_members_item'] = (int) $item_id;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_save_announcement() {
		if ( ! current_user_can( STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS ) ) {
			wp_die( 'Insufficient permissions' );
		}
		if ( ! isset( $_POST['stagekitwp_members_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_nonce'] ) ), 'stagekitwp_members_save_announcement' ) ) {
			wp_die( 'Nonce verification failed' );
		}
		$post_id = isset( $_POST['stagekitwp_members_item_id'] ) ? absint( $_POST['stagekitwp_members_item_id'] ) : 0;
		$title   = isset( $_POST['stagekitwp_members_title'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_title'] ) ) : '';
		$content = isset( $_POST['stagekitwp_members_content'] ) ? wp_kses_post( wp_unslash( $_POST['stagekitwp_members_content'] ) ) : '';
		$status  = isset( $_POST['stagekitwp_members_status'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_members_status'] ) ) : 'draft';
		if ( ! in_array( $status, array( 'draft', 'publish', 'private' ), true ) ) {
			$status = 'draft';
		}
		$save_id = $post_id ? wp_update_post( array( 'ID' => $post_id, 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ), true ) : wp_insert_post( array( 'post_type' => 'stagekitwp_ann', 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ), true );
		if ( is_wp_error( $save_id ) ) {
			wp_die( esc_html( $save_id->get_error_message() ) );
		}
		update_post_meta( (int) $save_id, '_stagekitwp_members_send_email', ! empty( $_POST['stagekitwp_members_send_email'] ) ? '1' : '0' );
		if ( current_user_can( STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS ) ) {
			update_post_meta( (int) $save_id, '_stagekitwp_members_important', ! empty( $_POST['stagekitwp_members_important'] ) ? '1' : '0' );
		}
		self::redirect_to_tab( 'announcements', (int) $save_id );
	}

	public static function handle_save_event() {
		if ( ! current_user_can( STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS ) ) {
			wp_die( 'Insufficient permissions' );
		}
		if ( ! isset( $_POST['stagekitwp_members_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_nonce'] ) ), 'stagekitwp_members_save_event' ) ) {
			wp_die( 'Nonce verification failed' );
		}
		$post_id = isset( $_POST['stagekitwp_members_item_id'] ) ? absint( $_POST['stagekitwp_members_item_id'] ) : 0;
		$title   = isset( $_POST['stagekitwp_members_title'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_title'] ) ) : '';
		$content = isset( $_POST['stagekitwp_members_content'] ) ? wp_kses_post( wp_unslash( $_POST['stagekitwp_members_content'] ) ) : '';
		$status  = isset( $_POST['stagekitwp_members_status'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_members_status'] ) ) : 'draft';
		if ( ! in_array( $status, array( 'draft', 'publish', 'private' ), true ) ) {
			$status = 'draft';
		}
		$save_id = $post_id ? wp_update_post( array( 'ID' => $post_id, 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ), true ) : wp_insert_post( array( 'post_type' => 'stagekitwp_event', 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ), true );
		if ( is_wp_error( $save_id ) ) {
			wp_die( esc_html( $save_id->get_error_message() ) );
		}
		update_post_meta( (int) $save_id, '_stagekitwp_members_event_date', isset( $_POST['stagekitwp_members_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_event_date'] ) ) : '' );
		update_post_meta( (int) $save_id, '_stagekitwp_members_start_time', isset( $_POST['stagekitwp_members_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_start_time'] ) ) : '' );
		update_post_meta( (int) $save_id, '_stagekitwp_members_end_time', isset( $_POST['stagekitwp_members_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_end_time'] ) ) : '' );
		$visibility = isset( $_POST['stagekitwp_members_event_visibility'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_members_event_visibility'] ) ) : 'private';
		if ( ! in_array( $visibility, array( 'public', 'private' ), true ) ) {
			$visibility = 'private';
		}
		update_post_meta( (int) $save_id, STAGEKITWP_MEMBERS_Events::META_VISIBILITY, $visibility );
		self::redirect_to_tab( 'events', (int) $save_id );
	}

	public static function handle_save_email_template() {
		if ( ! current_user_can( STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS ) ) {
			wp_die( 'Insufficient permissions' );
		}
		if ( ! isset( $_POST['stagekitwp_members_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_nonce'] ) ), 'stagekitwp_members_save_email_template' ) ) {
			wp_die( 'Nonce verification failed' );
		}
		$post_id = isset( $_POST['stagekitwp_members_item_id'] ) ? absint( $_POST['stagekitwp_members_item_id'] ) : 0;
		$title   = isset( $_POST['stagekitwp_members_title'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_title'] ) ) : '';
		$content = isset( $_POST['stagekitwp_members_content'] ) ? wp_kses_post( wp_unslash( $_POST['stagekitwp_members_content'] ) ) : '';
		$status  = isset( $_POST['stagekitwp_members_status'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_members_status'] ) ) : 'draft';
		if ( ! in_array( $status, array( 'draft', 'publish', 'private' ), true ) ) {
			$status = 'draft';
		}
		$save_id = $post_id ? wp_update_post( array( 'ID' => $post_id, 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ), true ) : wp_insert_post( array( 'post_type' => 'stagekitwp_email', 'post_title' => $title, 'post_content' => $content, 'post_status' => $status ), true );
		if ( is_wp_error( $save_id ) ) {
			wp_die( esc_html( $save_id->get_error_message() ) );
		}
		update_post_meta( (int) $save_id, '_stagekitwp_members_subject', isset( $_POST['stagekitwp_members_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_members_subject'] ) ) : '' );
		self::redirect_to_tab( 'email_templates', (int) $save_id );
	}
}
