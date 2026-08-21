<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Mod_Members_Area – module definition for stagekitwp-members-area.
 *
 * This module stores its data primarily as post-meta on stagekitwp_event posts
 * (RSVP records, member roles) and its own wp_options rows.
 * The CPT list is intentionally empty here because stagekitwp_event is owned by
 * the core StageKitWP module; we only claim the options namespace.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Mod_Members_Area extends STAGEKITWP_IMPORT_EXPORT_Module {

	public function id(): string {
		return 'stagekitwp-members';
	}

	public function label(): string {
		return __( 'StageKitWP Members', 'stagekitwp-io' );
	}

	public function description(): string {
		return __( 'Member settings, email configuration, RSVP status flags, and directory page mapping.', 'stagekitwp-io' );
	}

	/** No standalone CPTs – data lives in meta on stagekitwp_event. */
	public function post_types(): array {
		return [];
	}

	public function option_keys(): array {
		return [
			'stagekitwp_members_db_version',
			'stagekitwp_members_directory_page_id',
			'stagekitwp_members_email_enabled',
			'stagekitwp_members_email_status',
			'stagekitwp_members_roles_version',
			'stagekitwp_members_rsvp_meta_migrated',
			// Intentionally exclude: stagekitwp_members_email_queue, stagekitwp_members_last_email_attempt
			// (runtime state – not portable).
		];
	}

	public function is_available(): bool {
		return is_plugin_active( 'stagekitwp-members/stagekitwp-members.php' )
			|| is_plugin_active( 'stagekitwp-members/stagekitwp-members.php' );
	}

	// ── Extra: export user-role assignments for TM member roles ──────────────

	public function export_extra(): array {
		$members = [];

		// Grab all users who have a TM member role.
		$users = get_users( [
			'meta_key'   => 'stagekitwp_members_member_role',
			'meta_compare' => 'EXISTS',
		] );

		foreach ( $users as $user ) {
			$members[] = [
				'user_email' => $user->user_email,
				'user_login' => $user->user_login,
				'roles'      => $user->roles,
				'stagekitwp_meta'    => [
					'stagekitwp_members_member_role'   => get_user_meta( $user->ID, 'stagekitwp_members_member_role', true ),
					'stagekitwp_members_member_status' => get_user_meta( $user->ID, 'stagekitwp_members_member_status', true ),
				],
			];
		}

		return [ 'member_users' => $members ];
	}

	/**
	 * Re-apply TM member meta to existing users (matched by email).
	 * Does NOT create new users – that would be a security concern.
	 */
	public function import_extra( array $extra ): void {
		foreach ( $extra['member_users'] ?? [] as $member ) {
			$user = get_user_by( 'email', $member['user_email'] ?? '' );
			if ( ! $user ) {
				continue; // Skip – user doesn't exist on target site.
			}

			foreach ( $member['stagekitwp_meta'] ?? [] as $meta_key => $meta_value ) {
				update_user_meta( $user->ID, sanitize_key( $meta_key ), $meta_value );
			}
		}
	}

	public function import_options( array $options ): void {
		// Page ID remap: store staging key, same pattern as core module.
		if ( isset( $options['stagekitwp_members_directory_page_id'] ) ) {
			update_option( 'stagekitwp_import_export_remap_ma_directory_page_id', (int) $options['stagekitwp_members_directory_page_id'] );
			unset( $options['stagekitwp_members_directory_page_id'] );
		}

		parent::import_options( $options );
	}
}
