<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Controller for StageKit WP Media Items.
 * Endpoint: /wp-json/stagekit-media/v1/media
 */
class RESTMedia {

	public const NAMESPACE = 'stagekit-media/v1';
	public const REST_BASE  = 'media';

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
					'callback'            => [ __CLASS__, 'get_media_items' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'folder_id'        => [ 'type' => 'string', 'default' => 'all' ],
						'recursive_folder' => [ 'type' => 'boolean', 'default' => false ],
						'search'           => [ 'type' => 'string' ],
						'type'             => [ 'type' => 'string' ],
						'mime_type'        => [ 'type' => 'string' ],
						'provider'         => [ 'type' => 'string' ],
						'page'             => [ 'type' => 'integer', 'default' => 1 ],
						'per_page'         => [ 'type' => 'integer', 'default' => 24 ],
						'orderby'          => [ 'type' => 'string', 'default' => 'created_at' ],
						'order'            => [ 'type' => 'string', 'default' => 'DESC' ],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/upload',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'upload_media' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'folder_id' => [ 'type' => 'integer', 'default' => 0 ],
						'title'     => [ 'type' => 'string' ],
						'caption'   => [ 'type' => 'string' ],
						'alt_text'  => [ 'type' => 'string' ],
						'author'    => [ 'type' => 'string' ],
						'license'   => [ 'type' => 'string' ],
						'provider'  => [ 'type' => 'string', 'default' => 'local' ],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/batch',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'batch_action' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'action'    => [ 'type' => 'string', 'required' => true, 'enum' => [ 'move', 'delete' ] ],
						'ids'       => [ 'type' => 'array', 'required' => true, 'items' => [ 'type' => 'integer' ] ],
						'folder_id' => [ 'type' => 'integer', 'default' => 0 ],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics/stats',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_storage_stats' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics/scan-orphans',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'scan_orphans' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics/cleanup-orphans',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'cleanup_orphans' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics/scan-missing',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'scan_missing' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics/cleanup-missing',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'cleanup_missing' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics/regenerate-thumbs',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'regenerate_thumbs' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>\d+)/export-wp',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'export_to_wp' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_media_item' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ __CLASS__, 'update_media_item' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
					'args'                => [
						'title'     => [ 'type' => 'string' ],
						'caption'   => [ 'type' => 'string' ],
						'alt_text'  => [ 'type' => 'string' ],
						'folder_id' => [ 'type' => 'integer' ],
						'author'    => [ 'type' => 'string' ],
						'license'   => [ 'type' => 'string' ],
					],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ __CLASS__, 'delete_media_item' ],
					'permission_callback' => [ __CLASS__, 'check_permission' ],
				],
			]
		);
	}

	public static function get_storage_stats(): \WP_REST_Response {
		return rest_ensure_response( [
			'success' => true,
			'stats'   => Diagnostics::get_storage_stats(),
		] );
	}

	public static function scan_orphans(): \WP_REST_Response {
		return rest_ensure_response( [
			'success' => true,
			'orphans' => Diagnostics::scan_orphaned_files(),
		] );
	}

	public static function cleanup_orphans(): \WP_REST_Response {
		$deleted = Diagnostics::cleanup_orphaned_files();
		return rest_ensure_response( [
			'success' => true,
			'deleted' => $deleted,
		] );
	}

	public static function scan_missing(): \WP_REST_Response {
		return rest_ensure_response( [
			'success' => true,
			'missing' => Diagnostics::scan_missing_files(),
		] );
	}

	public static function cleanup_missing(): \WP_REST_Response {
		$cleaned = Diagnostics::cleanup_missing_records();
		return rest_ensure_response( [
			'success' => true,
			'cleaned' => $cleaned,
		] );
	}

	public static function regenerate_thumbs(): \WP_REST_Response {
		$result = Diagnostics::regenerate_all_thumbnails();
		return rest_ensure_response( [
			'success' => true,
			'result'  => $result,
		] );
	}

	public static function export_to_wp( \WP_REST_Request $request ): \WP_REST_Response {
		$id            = (int) $request->get_param( 'id' );
		$attachment_id = Diagnostics::export_to_wp_attachment( $id );

		if ( ! $attachment_id ) {
			return new \WP_REST_Response( [
				'code'    => 'export_failed',
				'message' => __( 'Could not export this item to the WordPress Media Library.', 'stagekitwp-media' ),
			], 500 );
		}

		return rest_ensure_response( [
			'success'       => true,
			'media_id'      => $id,
			'attachment_id' => $attachment_id,
			'edit_url'      => admin_url( 'post.php?post=' . $attachment_id . '&action=edit' ),
		] );
	}

	public static function check_permission(): bool {
		return current_user_can( 'upload_files' ) || current_user_can( 'edit_posts' );
	}

	public static function get_media_items( \WP_REST_Request $request ): \WP_REST_Response {
		$args = [
			'folder_id'        => $request->get_param( 'folder_id' ) ?? 'all',
			'recursive_folder' => (bool) $request->get_param( 'recursive_folder' ),
			'search'           => $request->get_param( 'search' ),
			'type'             => $request->get_param( 'type' ),
			'mime_type'        => $request->get_param( 'mime_type' ),
			'provider'         => $request->get_param( 'provider' ),
			'page'             => max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) ),
			'per_page'         => max( 1, min( 100, (int) ( $request->get_param( 'per_page' ) ?? 24 ) ) ),
			'orderby'          => $request->get_param( 'orderby' ) ?? 'created_at',
			'order'            => $request->get_param( 'order' ) ?? 'DESC',
		];

		$result = MediaRepository::query( $args );
		return rest_ensure_response( array_merge( [ 'success' => true ], $result ) );
	}

	public static function get_media_item( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$item = MediaRepository::get( $id );

		if ( ! $item ) {
			return new \WP_REST_Response( [ 'code' => 'not_found', 'message' => __( 'Media item not found.', 'stagekitwp-media' ) ], 404 );
		}

		return rest_ensure_response( [
			'success' => true,
			'item'    => $item,
		] );
	}

	public static function upload_media( \WP_REST_Request $request ): \WP_REST_Response {
		$files = $request->get_file_params();
		if ( empty( $files ) ) {
			return new \WP_REST_Response( [ 'code' => 'no_files', 'message' => __( 'No files uploaded.', 'stagekitwp-media' ) ], 400 );
		}

		$folder_id = max( 0, (int) ( $request->get_param( 'folder_id' ) ?? 0 ) );
		$provider  = sanitize_key( $request->get_param( 'provider' ) ?? 'local' );
		$author    = sanitize_text_field( $request->get_param( 'author' ) ?? '' );
		$license   = sanitize_text_field( $request->get_param( 'license' ) ?? '' );

		$uploaded_items = [];
		$errors         = [];

		// Normalize files into a consistent array list
		$file_list = [];
		foreach ( $files as $key => $file_data ) {
			if ( is_array( $file_data['name'] ?? null ) ) {
				// Multiple files uploaded under same name array
				$count = count( $file_data['name'] );
				for ( $i = 0; $i < $count; $i++ ) {
					$file_list[] = [
						'name'     => $file_data['name'][ $i ],
						'type'     => $file_data['type'][ $i ] ?? '',
						'tmp_name' => $file_data['tmp_name'][ $i ],
						'error'    => $file_data['error'][ $i ],
						'size'     => $file_data['size'][ $i ],
					];
				}
			} else {
				$file_list[] = $file_data;
			}
		}

		foreach ( $file_list as $file ) {
			if ( ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
				$errors[] = sprintf( __( 'Upload error for file %s.', 'stagekitwp-media' ), esc_html( $file['name'] ?? 'unknown' ) );
				continue;
			}

			$saved = Storage::save_file( $file['tmp_name'], $file['name'], false );
			if ( ! $saved ) {
				$errors[] = sprintf( __( 'Failed to store or process file %s.', 'stagekitwp-media' ), esc_html( $file['name'] ) );
				continue;
			}

			$title = sanitize_text_field( $request->get_param( 'title' ) ?? pathinfo( $file['name'], PATHINFO_FILENAME ) );

			$record_data = array_merge( $saved, [
				'folder_id' => $folder_id,
				'provider'  => $provider ?: 'local',
				'title'     => $title,
				'caption'   => $request->get_param( 'caption' ) ?? null,
				'alt_text'  => $request->get_param( 'alt_text' ) ?? '',
				'author'    => $author,
				'license'   => $license,
			] );

			$id = MediaRepository::create( $record_data );
			if ( $id ) {
				$uploaded_items[] = MediaRepository::get( $id );
			} else {
				// Cleanup storage if DB insertion failed
				Storage::delete_file( $saved['file_path'], $saved['thumbnails'] );
				$errors[] = sprintf( __( 'Failed to create database record for %s.', 'stagekitwp-media' ), esc_html( $file['name'] ) );
			}
		}

		if ( empty( $uploaded_items ) && ! empty( $errors ) ) {
			return new \WP_REST_Response( [
				'code'    => 'upload_failed',
				'message' => implode( ' ', $errors ),
			], 400 );
		}

		return rest_ensure_response( [
			'success' => true,
			'items'   => $uploaded_items,
			'errors'  => $errors,
		] );
	}

	public static function update_media_item( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$item = MediaRepository::get( $id );

		if ( ! $item ) {
			return new \WP_REST_Response( [ 'code' => 'not_found', 'message' => __( 'Media item not found.', 'stagekitwp-media' ) ], 404 );
		}

		$data = [];
		$fields = [ 'title', 'caption', 'alt_text', 'folder_id', 'author', 'license' ];
		foreach ( $fields as $field ) {
			if ( null !== $request->get_param( $field ) ) {
				$data[ $field ] = $request->get_param( $field );
			}
		}

		$updated = MediaRepository::update( $id, $data );
		if ( ! $updated ) {
			return new \WP_REST_Response( [ 'code' => 'update_failed', 'message' => __( 'Could not update media item.', 'stagekitwp-media' ) ], 500 );
		}

		return rest_ensure_response( [
			'success' => true,
			'item'    => MediaRepository::get( $id ),
		] );
	}

	public static function delete_media_item( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$item = MediaRepository::get( $id );

		if ( ! $item ) {
			return new \WP_REST_Response( [ 'code' => 'not_found', 'message' => __( 'Media item not found.', 'stagekitwp-media' ) ], 404 );
		}

		$deleted = MediaRepository::delete( $id, true );
		if ( ! $deleted ) {
			return new \WP_REST_Response( [ 'code' => 'delete_failed', 'message' => __( 'Could not delete media item.', 'stagekitwp-media' ) ], 500 );
		}

		return rest_ensure_response( [
			'success'    => true,
			'deleted_id' => $id,
		] );
	}

	public static function batch_action( \WP_REST_Request $request ): \WP_REST_Response {
		$action = sanitize_key( $request->get_param( 'action' ) );
		$ids    = (array) $request->get_param( 'ids' );

		if ( empty( $ids ) ) {
			return new \WP_REST_Response( [ 'code' => 'invalid_ids', 'message' => __( 'No items selected.', 'stagekitwp-media' ) ], 400 );
		}

		if ( 'move' === $action ) {
			$folder_id = max( 0, (int) $request->get_param( 'folder_id' ) );
			$count     = MediaRepository::move_to_folder( $ids, $folder_id );

			return rest_ensure_response( [
				'success' => true,
				'action'  => 'move',
				'count'   => $count,
			] );
		}

		if ( 'delete' === $action ) {
			$count = MediaRepository::bulk_delete( $ids, true );

			return rest_ensure_response( [
				'success' => true,
				'action'  => 'delete',
				'count'   => $count,
			] );
		}

		return new \WP_REST_Response( [ 'code' => 'invalid_action', 'message' => __( 'Invalid batch action.', 'stagekitwp-media' ) ], 400 );
	}
}
