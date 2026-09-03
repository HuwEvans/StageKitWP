<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Controller for StageKit WP Media Folders.
 * Endpoint: /wp-json/stagekit-media/v1/folders
 */
class RESTFolders {

	public const NAMESPACE = 'stagekit-media/v1';
	public const REST_BASE  = 'folders';

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_folders' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'tree'      => [ 'type' => 'boolean', 'default' => true ],
						'parent_id' => [ 'type' => 'integer', 'default' => 0 ],
						'provider'  => [ 'type' => 'string' ],
					],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'create_folder' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'name'      => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
						'parent_id' => [ 'type' => 'integer', 'default' => 0 ],
						'color'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_hex_color' ],
						'provider'  => [ 'type' => 'string', 'default' => 'local', 'sanitize_callback' => 'sanitize_key' ],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_folder' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ __CLASS__, 'update_folder' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'name'      => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
						'parent_id' => [ 'type' => 'integer' ],
						'color'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_hex_color' ],
						'slug'      => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_title' ],
					],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ __CLASS__, 'delete_folder' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'reassign_to_parent' => [ 'type' => 'boolean', 'default' => true ],
					],
				],
			]
		);
	}

	public static function check_permission(): bool {
		return current_user_can( 'upload_files' ) || current_user_can( 'edit_posts' );
	}

	public static function get_folders( \WP_REST_Request $request ): \WP_REST_Response {
		$as_tree   = (bool) $request->get_param( 'tree' );
		$parent_id = (int) $request->get_param( 'parent_id' );
		$provider  = $request->get_param( 'provider' );

		if ( $as_tree ) {
			$folders = FolderRepository::get_tree( $parent_id );
		} else {
			$args = [];
			if ( ! empty( $provider ) ) {
				$args['provider'] = $provider;
			}
			$folders = FolderRepository::get_all( $args );
			foreach ( $folders as &$f ) {
				$f['id']         = (int) $f['id'];
				$f['parent_id']  = (int) $f['parent_id'];
				$f['item_count'] = FolderRepository::get_item_count( $f['id'], false );
			}
		}

		return rest_ensure_response( [
			'success' => true,
			'folders' => $folders,
		] );
	}

	public static function get_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$id     = (int) $request->get_param( 'id' );
		$folder = FolderRepository::get( $id );

		if ( ! $folder ) {
			return new \WP_REST_Response( [ 'code' => 'not_found', 'message' => __( 'Folder not found.', 'stagekitwp-media' ) ], 404 );
		}

		$folder['id']          = (int) $folder['id'];
		$folder['parent_id']   = (int) $folder['parent_id'];
		$folder['item_count']  = FolderRepository::get_item_count( $id, false );
		$folder['breadcrumbs'] = FolderRepository::get_breadcrumbs( $id );

		return rest_ensure_response( [
			'success' => true,
			'folder'  => $folder,
		] );
	}

	public static function create_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$name      = $request->get_param( 'name' );
		$parent_id = (int) ( $request->get_param( 'parent_id' ) ?? 0 );
		$color     = $request->get_param( 'color' );
		$provider  = $request->get_param( 'provider' ) ?: 'local';

		if ( empty( $name ) ) {
			return new \WP_REST_Response( [ 'code' => 'invalid_name', 'message' => __( 'Folder name is required.', 'stagekitwp-media' ) ], 400 );
		}

		$id = FolderRepository::create( [
			'name'      => $name,
			'parent_id' => $parent_id,
			'color'     => $color,
			'provider'  => $provider,
		] );

		if ( ! $id ) {
			return new \WP_REST_Response( [ 'code' => 'create_failed', 'message' => __( 'Could not create folder.', 'stagekitwp-media' ) ], 500 );
		}

		$folder = FolderRepository::get( $id );
		$folder['id']         = (int) $folder['id'];
		$folder['parent_id']  = (int) $folder['parent_id'];
		$folder['item_count'] = 0;

		return rest_ensure_response( [
			'success' => true,
			'folder'  => $folder,
		] );
	}

	public static function update_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$id     = (int) $request->get_param( 'id' );
		$folder = FolderRepository::get( $id );

		if ( ! $folder ) {
			return new \WP_REST_Response( [ 'code' => 'not_found', 'message' => __( 'Folder not found.', 'stagekitwp-media' ) ], 404 );
		}

		$data = [];
		if ( null !== $request->get_param( 'name' ) ) {
			$data['name'] = $request->get_param( 'name' );
		}
		if ( null !== $request->get_param( 'parent_id' ) ) {
			$data['parent_id'] = (int) $request->get_param( 'parent_id' );
		}
		if ( null !== $request->get_param( 'color' ) ) {
			$data['color'] = $request->get_param( 'color' );
		}
		if ( null !== $request->get_param( 'slug' ) ) {
			$data['slug'] = $request->get_param( 'slug' );
		}

		$updated = FolderRepository::update( $id, $data );
		if ( ! $updated ) {
			return new \WP_REST_Response( [ 'code' => 'update_failed', 'message' => __( 'Could not update folder.', 'stagekitwp-media' ) ], 500 );
		}

		$fresh = FolderRepository::get( $id );
		$fresh['id']         = (int) $fresh['id'];
		$fresh['parent_id']  = (int) $fresh['parent_id'];
		$fresh['item_count'] = FolderRepository::get_item_count( $id, false );

		return rest_ensure_response( [
			'success' => true,
			'folder'  => $fresh,
		] );
	}

	public static function delete_folder( \WP_REST_Request $request ): \WP_REST_Response {
		$id                 = (int) $request->get_param( 'id' );
		$reassign_to_parent = (bool) $request->get_param( 'reassign_to_parent' );

		$folder = FolderRepository::get( $id );
		if ( ! $folder ) {
			return new \WP_REST_Response( [ 'code' => 'not_found', 'message' => __( 'Folder not found.', 'stagekitwp-media' ) ], 404 );
		}

		$deleted = FolderRepository::delete( $id, $reassign_to_parent );
		if ( ! $deleted ) {
			return new \WP_REST_Response( [ 'code' => 'delete_failed', 'message' => __( 'Could not delete folder.', 'stagekitwp-media' ) ], 500 );
		}

		return rest_ensure_response( [
			'success'    => true,
			'deleted_id' => $id,
		] );
	}
}
