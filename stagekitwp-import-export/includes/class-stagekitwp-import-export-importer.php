<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Importer – orchestrates import from a ZIP bundle, a single JSON file,
 * or a remote URL.
 *
 * Processing strategy:
 *   < STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD records → synchronous (direct PHP)
 *   ≥ STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD records → async via STAGEKITWP_IMPORT_EXPORT_Queue (Action Scheduler
 *     if available, otherwise WP-Cron batching)
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Importer {

	/** @var STAGEKITWP_IMPORT_EXPORT_Module[] */
	private array $modules;

	/** @var array<int,int>  old attachment ID => new attachment ID */
	private array $media_id_map = [];

	/** @var array<string,string>  old upload URL => new upload URL */
	private array $media_url_map = [];

	/** @var array<string,true>|null  cache: relationship meta keys across every registered module */
	private ?array $relationship_meta_key_set = null;

	/**
	 * @param STAGEKITWP_IMPORT_EXPORT_Module[] $modules
	 */
	public function __construct( array $modules ) {
		$this->modules = $modules;
	}

	/**
	 * Store the old→new media maps produced by import_media() so subsequent
	 * post imports can rewrite stale attachment references. Public so the queue
	 * (async path) can seed the maps before importing each module batch.
	 *
	 * @param array<int,int>       $id_map
	 * @param array<string,string> $url_map
	 */
	public function set_media_maps( array $id_map, array $url_map ): void {
		$this->media_id_map  = $id_map;
		$this->media_url_map = $url_map;
	}

	/**
	 * Meta keys that store a related POST ID (show→season, cast→show, etc.),
	 * gathered from every registered module. These must never be rewritten by
	 * the media (attachment) ID map — a post ID and an attachment ID are drawn
	 * from the same numeric ID space, so a small season/show/venue ID can
	 * coincidentally collide with an old attachment ID and get corrupted if it
	 * isn't explicitly excluded here. Relationship values are remapped later,
	 * correctly, by STAGEKITWP_IMPORT_EXPORT_Module::remap_relationships().
	 *
	 * @return array<string,true>
	 */
	private function relationship_meta_key_set(): array {
		if ( null === $this->relationship_meta_key_set ) {
			$this->relationship_meta_key_set = [];
			foreach ( $this->modules as $module ) {
				foreach ( $module->relationship_meta_keys() as $key ) {
					$this->relationship_meta_key_set[ $key ] = true;
				}
			}
		}
		return $this->relationship_meta_key_set;
	}

	/**
	 * Import media for the async queue and persist the resulting old→new maps
	 * onto the job transient so later post batches can remap references.
	 *
	 * @param array<string,mixed> $media_data  { index: [], dir: string }
	 * @param string              $job_id
	 * @return array<string,mixed>  media import summary (imported/skipped/errors)
	 */
	public function import_media_public( array $media_data, string $job_id ): array {
		$result = $this->import_media( $media_data );

		$job = get_transient( 'stagekitwp_import_export_job_' . $job_id );
		if ( is_array( $job ) ) {
			$job['media_id_map']  = $result['id_map'];
			$job['media_url_map'] = $result['url_map'];
			set_transient( 'stagekitwp_import_export_job_' . $job_id, $job, 10 * MINUTE_IN_SECONDS );
		}

		return [
			'imported' => $result['imported'],
			'skipped'  => $result['skipped'],
			'errors'   => $result['errors'],
		];
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Import from a local file path or a remote URL.
	 *
	 * Accepted sources:
	 *   - Absolute path to a .zip  (bundle)
	 *   - Absolute path to a .json (single-module or raw options)
	 *   - HTTPS URL to a .zip or .json (fetched via wp_remote_get)
	 *
	 * Options:
	 *   conflict       string  skip|overwrite|duplicate   default: skip
	 *   modules        string  comma-sep IDs or 'all'     default: all
	 *   original_name  string  the uploaded file's real name (so a PHP temp
	 *                          upload named foo.tmp is still detected correctly)
	 *
	 * @param string              $source   File path or URL.
	 * @param array<string,mixed> $options
	 * @return array<string,mixed>|WP_Error
	 */
	public function import( string $source, array $options = [] ): array|\WP_Error {
			$conflict   = $options['conflict'] ?? 'skip';
		$module_ids = $options['modules']  ?? 'all';
		$dry_run    = ! empty( $options['dry_run'] );
		$orig_name  = isset( $options['original_name'] ) ? (string) $options['original_name'] : '';

		// ── 1. Resolve source to a local path ────────────────────────────────
		$local_path = $this->resolve_source( $source );
		if ( is_wp_error( $local_path ) ) {
			return $local_path;
		}

		// ── 2. Detect file type and extract payloads ──────────────────────────
		// Prefer the original upload name for the extension (PHP stores uploads
		// at a temp path ending in .tmp, so the local path is unreliable). Fall
		// back to the local path, then to sniffing the file's magic bytes so an
		// unknown/blank extension still works.
		$name_for_ext = $orig_name !== '' ? $orig_name : $local_path;
		$ext          = strtolower( pathinfo( $name_for_ext, PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, [ 'zip', 'json' ], true ) ) {
			$sniffed = $this->sniff_type( $local_path );
			if ( $sniffed ) {
				$ext = $sniffed;
			}
		}

		if ( 'zip' === $ext ) {
			$result = $this->extract_zip( $local_path );
		} elseif ( 'json' === $ext ) {
			$result = $this->load_json_file( $local_path );
		} else {
			return new \WP_Error(
				'stagekitwp_import_export_unsupported_format',
				sprintf(
					/* translators: %s: file extension */
					__( 'Unsupported import file type: .%s. Please upload a .zip or .json file.', 'stagekitwp-io' ),
					esc_html( $ext !== '' ? $ext : 'unknown' )
				)
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		[ $manifest, $payloads ] = $result;
		[ $manifest, $payloads ] = $this->normalize_legacy_import( $manifest, $payloads );

		// ── 3. Filter to requested modules ────────────────────────────────────
		if ( 'all' !== $module_ids ) {
			$requested = array_map( 'sanitize_key', explode( ',', $module_ids ) );
			$payloads  = array_intersect_key( $payloads, array_flip( $requested ) );
		}

		// ── 4. Dry-run: count what would change without writing anything ──────
		if ( $dry_run ) {
			return $this->dry_run_preview( $payloads, $conflict, $manifest );
		}

		// ── 5. Decide sync vs async ───────────────────────────────────────────
		$total = STAGEKITWP_IMPORT_EXPORT_Manifest::total_records( $manifest );

		if ( $total >= STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD ) {
			return $this->queue_import( $payloads, $conflict, $manifest );
		}

		// ── 6. Synchronous import ─────────────────────────────────────────────
		return $this->run_import( $payloads, $conflict );
	}

	// ── Synchronous import ────────────────────────────────────────────────────

	/**
	 * @param array<string, array<string,mixed>> $payloads
	 * @param string                             $conflict
	 * @return array<string,mixed>
	 */
	public function run_import( array $payloads, string $conflict ): array {
		$summary = [
			'modules'  => [],
			'imported' => 0,
			'skipped'  => 0,
			'errors'   => [],
		];

		// Import media FIRST so we can remap old→new attachment references while
		// importing posts (featured images, image meta, inline content URLs).
		if ( ! empty( $payloads['_media'] ) ) {
			$media_result     = $this->import_media( $payloads['_media'] );
			$summary['media'] = [
				'imported' => $media_result['imported'],
				'skipped'  => $media_result['skipped'],
				'errors'   => $media_result['errors'],
			];
			$this->set_media_maps( $media_result['id_map'], $media_result['url_map'] );
		}

		// old post ID => new local ID, accumulated across all modules so
		// cross-post relationships (show→season, etc.) can be remapped after all
		// posts have landed.
		$post_id_map = [];

		foreach ( $payloads as $id => $payload ) {
			if ( '_media' === $id ) {
				continue;
			}
			$module = $this->modules[ $id ] ?? null;
			if ( ! $module ) {
				$summary['errors'][] = sprintf(
					__( 'Module "%s" is not registered on this site. Its data was skipped.', 'stagekitwp-io' ),
					esc_html( $id )
				);
				continue;
			}

			$payload                 = $this->remap_payload_media( $payload );
			$result                  = $module->import( $payload, [ 'conflict' => $conflict ] );
			$summary['modules'][$id] = $result;
			$summary['imported']    += $result['imported'] ?? 0;
			$summary['skipped']     += $result['skipped']  ?? 0;
			$summary['errors']       = array_merge( $summary['errors'], $result['errors'] ?? [] );
			if ( ! empty( $result['id_map'] ) ) {
				$post_id_map += $result['id_map'];
			}
		}

		// Finalize: remap cross-post relationship meta now that every post exists.
		$remapped = 0;
		if ( $post_id_map ) {
			foreach ( $payloads as $id => $payload ) {
				if ( '_media' === $id ) {
					continue;
				}
				$module = $this->modules[ $id ] ?? null;
				if ( $module ) {
					$remapped += $module->remap_relationships( $post_id_map );
				}
			}
		}
		$summary['relationships_remapped'] = $remapped;
		$summary['page_remaps_resolved']   = $this->auto_resolve_page_remaps( $post_id_map );

		return $summary;
	}

	/**
	 * Known "staging option" → "live option" pairs for page-ID references. A
	 * module's import_options() stores the raw old page ID under a
	 * stagekitwp_import_export_remap_* staging key (see admin/views/remap.php)
	 * instead of writing straight to the live option, since that ID is only
	 * valid on the source site. Filterable so other modules can register pairs.
	 *
	 * @return array<string,string>  staging_option_key => live_option_key
	 */
	private function page_remap_option_pairs(): array {
		return apply_filters( 'stagekitwp_import_export_page_remap_pairs', [
			'stagekitwp_import_export_remap_auditions_page_id'    => 'stagekitwp_auditions_page_id',
			'stagekitwp_import_export_remap_ma_directory_page_id' => 'stagekitwp_members_directory_page_id',
		] );
	}

	/**
	 * Auto-resolve staged page-ID remaps when the referenced page was included in
	 * this same import (its old ID is present in $post_id_map, e.g. the Pages
	 * module was selected). Anything that can't be resolved this way is left for
	 * the admin to resolve manually on the Page ID Remap screen.
	 *
	 * @param array<int,int> $post_id_map  old post ID => new post ID (this run).
	 * @return int  number of options auto-resolved.
	 */
	private function auto_resolve_page_remaps( array $post_id_map ): int {
		if ( empty( $post_id_map ) ) {
			return 0;
		}

		$resolved = 0;
		foreach ( $this->page_remap_option_pairs() as $staging_key => $live_key ) {
			$old_page_id = (int) get_option( $staging_key, 0 );
			if ( ! $old_page_id || ! isset( $post_id_map[ $old_page_id ] ) ) {
				continue;
			}
			update_option( $live_key, $post_id_map[ $old_page_id ] );
			delete_option( $staging_key );
			$resolved++;
		}

		return $resolved;
	}

	// ── Dry-run ───────────────────────────────────────────────────────────────

	/**
	 * Simulate the import and return what would happen without writing any data.
	 *
	 * @param array<string, array<string,mixed>> $payloads
	 * @param string                             $conflict
	 * @param array<string,mixed>                $manifest
	 * @return array<string,mixed>
	 */
	private function dry_run_preview( array $payloads, string $conflict, array $manifest ): array {
		$summary = [
			'dry_run'  => true,
			'modules'  => [],
			'would_import' => 0,
			'would_skip'   => 0,
			'would_update' => 0,
			'errors'       => [],
		];

		foreach ( $payloads as $id => $payload ) {
			if ( str_starts_with( $id, '_' ) ) {
				continue; // skip _media etc.
			}
			$module = $this->modules[ $id ] ?? null;
			if ( ! $module ) {
				$summary['errors'][] = sprintf( 'Module "%s" not registered on this site.', $id );
				continue;
			}

			$mod_summary = [ 'would_import' => 0, 'would_skip' => 0, 'would_update' => 0 ];
			foreach ( $payload['posts'] ?? [] as $post_data ) {
				$existing = $module->find_existing_post_public( $post_data );
				if ( $existing ) {
					if ( 'skip' === $conflict ) {
						$mod_summary['would_skip']++;
					} elseif ( 'overwrite' === $conflict ) {
						$mod_summary['would_update']++;
					} else {
						$mod_summary['would_import']++; // duplicate
					}
				} else {
					$mod_summary['would_import']++;
				}
			}
			$mod_summary['options_count'] = count( $payload['options'] ?? [] );
			$summary['modules'][ $id ]     = $mod_summary;
			$summary['would_import']      += $mod_summary['would_import'];
			$summary['would_skip']        += $mod_summary['would_skip'];
			$summary['would_update']      += $mod_summary['would_update'];
		}

		$summary['manifest'] = STAGEKITWP_IMPORT_EXPORT_Manifest::summary( $manifest );
		return $summary;
	}

	// ── Async / queued import ─────────────────────────────────────────────────

	/**
	 * Store the payloads in a transient and hand off to STAGEKITWP_IMPORT_EXPORT_Queue.
	 *
	 * @param array<string, array<string,mixed>> $payloads
	 * @param string                             $conflict
	 * @param array<string,mixed>                $manifest
	 * @return array<string,mixed>
	 */
	private function queue_import( array $payloads, string $conflict, array $manifest ): array {
		$job_id = wp_generate_uuid4();

		// Persist payload for the background job (10 min TTL).
		set_transient( 'stagekitwp_import_export_job_' . $job_id, [
			'payloads' => $payloads,
			'conflict' => $conflict,
			'manifest' => $manifest,
		], 10 * MINUTE_IN_SECONDS );

		stagekitwp_io()->queue()->enqueue( $job_id );

		return [
			'queued'  => true,
			'job_id'  => $job_id,
			'total'   => STAGEKITWP_IMPORT_EXPORT_Manifest::total_records( $manifest ),
		];
	}

	// ── Source resolution ─────────────────────────────────────────────────────

	/**
	 * Ensures the source is a local, readable file path.
	 * Downloads the file if a URL is given.
	 *
	 * @param string $source  File path or URL.
	 * @return string|WP_Error  Local file path on success.
	 */
	/**
	 * Detect the file type by inspecting the first bytes of the file, so an
	 * upload with a missing or misleading extension (e.g. a PHP ".tmp") is
	 * still handled. Returns 'zip', 'json', or '' if unrecognised.
	 *
	 * @param string $path
	 * @return string
	 */
	private function sniff_type( string $path ): string {
		$fh = @fopen( $path, 'rb' );
		if ( ! $fh ) {
			return '';
		}
		$head = fread( $fh, 8 );
		fclose( $fh );
		if ( '' === $head || false === $head ) {
			return '';
		}

		// ZIP local-file-header magic: PK\x03\x04 (also PK\x05\x06 empty archive).
		if ( str_starts_with( $head, "PK\x03\x04" ) || str_starts_with( $head, "PK\x05\x06" ) ) {
			return 'zip';
		}

		// JSON: first non-whitespace byte is { or [ (allow a UTF-8 BOM).
		$trimmed = ltrim( $head, "\xEF\xBB\xBF \t\r\n" );
		if ( '' !== $trimmed && ( '{' === $trimmed[0] || '[' === $trimmed[0] ) ) {
			return 'json';
		}

		return '';
	}

	private function resolve_source( string $source ): string|\WP_Error {
		if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
			return $this->download_remote( $source );
		}

		if ( ! file_exists( $source ) ) {
			return new \WP_Error(
				'stagekitwp_import_export_file_missing',
				sprintf( __( 'Import file not found: %s', 'stagekitwp-io' ), esc_html( $source ) )
			);
		}

		return $source;
	}

	/**
	 * Download a remote ZIP/JSON to a temporary file and return the path.
	 *
	 * @param string $url
	 * @return string|WP_Error
	 */
	private function download_remote( string $url ): string|\WP_Error {
		$tmp = download_url( $url, 120 ); // 120-second timeout

		if ( is_wp_error( $tmp ) ) {
			return new \WP_Error(
				'stagekitwp_import_export_download_failed',
				sprintf( __( 'Could not download remote file: %s', 'stagekitwp-io' ), $tmp->get_error_message() )
			);
		}

		// Rename with a proper extension so extraction code works.
		$ext      = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'zip';
		$renamed  = $tmp . '.' . $ext;
		rename( $tmp, $renamed );

		return $renamed;
	}

	// ── ZIP extraction ────────────────────────────────────────────────────────

	/**
	 * Extract a ZIP bundle and return [ manifest, payloads ].
	 *
	 * @param string $zip_path
	 * @return array{array<string,mixed>, array<string,array<string,mixed>>}|WP_Error
	 */
	private function extract_zip( string $zip_path ): array|\WP_Error {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'stagekitwp_import_export_no_zip', __( 'PHP ZipArchive extension is required.', 'stagekitwp-io' ) );
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return new \WP_Error( 'stagekitwp_import_export_zip_open', __( 'Could not open ZIP archive.', 'stagekitwp-io' ) );
		}

		// Read manifest.
		$manifest_json = $zip->getFromName( STAGEKITWP_IMPORT_EXPORT_Manifest::FILENAME );
		if ( false === $manifest_json ) {
			$zip->close();
			return new \WP_Error( 'stagekitwp_import_export_no_manifest', __( 'The ZIP does not contain a manifest.json. It may not be a valid TM IO bundle.', 'stagekitwp-io' ) );
		}

		$manifest = STAGEKITWP_IMPORT_EXPORT_Manifest::parse( $manifest_json );
		if ( is_wp_error( $manifest ) ) {
			$zip->close();
			return $manifest;
		}

		// Read module payloads.
		$payloads = [];
		foreach ( array_keys( $manifest['modules'] ?? [] ) as $module_id ) {
			$payload = [];

			$posts_json   = $zip->getFromName( $module_id . '/posts.json' );
			$options_json = $zip->getFromName( $module_id . '/options.json' );
			$extra_json   = $zip->getFromName( $module_id . '/extra.json' );

			$payload['posts']   = $posts_json   ? json_decode( $posts_json,   true ) : [];
			$payload['options'] = $options_json ? json_decode( $options_json, true ) : [];
			$payload['extra']   = $extra_json   ? json_decode( $extra_json,   true ) : [];

			$payloads[ $module_id ] = $payload;
		}

		// Extract media to uploads/stagekitwp-io/import-{timestamp}/ for later processing.
		$media_index_json = $zip->getFromName( 'media/index.json' );
		if ( $media_index_json ) {
			$media_dir = $this->prepare_media_dir();
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$name = $zip->getNameIndex( $i );
				if ( str_starts_with( $name, 'media/' ) && 'media/index.json' !== $name ) {
					$zip->extractTo( $media_dir, $name );
				}
			}
			$payloads['_media'] = [
				'index' => json_decode( $media_index_json, true ),
				'dir'   => trailingslashit( $media_dir ) . 'media/',
			];
		}

		$zip->close();

		return [ $manifest, $payloads ];
	}

	// ── JSON file import (single module) ──────────────────────────────────────

	/**
	 * Load a single JSON file as a module payload.
	 * Handles two formats:
	 *   a) A full module export (has 'module' key at root).
	 *   b) A raw options dump (plain key→value object).
	 *
	 * @param string $path
	 * @return array{array<string,mixed>, array<string,array<string,mixed>>}|WP_Error
	 */
	private function load_json_file( string $path ): array|\WP_Error {
		$json = file_get_contents( $path );
		if ( false === $json ) {
			return new \WP_Error( 'stagekitwp_import_export_read_error', __( 'Could not read import file.', 'stagekitwp-io' ) );
		}

		$data = json_decode( $json, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'stagekitwp_import_export_json_error', sprintf( __( 'Invalid JSON: %s', 'stagekitwp-io' ), json_last_error_msg() ) );
		}

		// Full module export.
		if ( isset( $data['module'] ) ) {
			$id       = $data['module'];
			$manifest = STAGEKITWP_IMPORT_EXPORT_Manifest::build( [ $id => $data ] );
			return [ $manifest, [ $id => $data ] ];
		}

		// Raw options dump — we don't know which module; present as orphan.
		$manifest = [ 'schema_version' => STAGEKITWP_IMPORT_EXPORT_Manifest::SCHEMA_VER, 'total_posts' => 0, 'modules' => [ '_raw' => [] ] ];
		return [ $manifest, [ '_raw' => [ 'posts' => [], 'options' => $data, 'extra' => [] ] ] ];
	}

	/**
	 * Convert a legacy Theatre Manager export into the current StageKitWP shape.
	 *
	 * This adapter is intentionally limited to imports. The new ecosystem does
	 * not register legacy aliases, but old bundles can still be brought forward
	 * once by translating their module IDs and stored identifiers here.
	 *
	 * @param array<string,mixed>                 $manifest Parsed manifest.
	 * @param array<string,array<string,mixed>>   $payloads Module payloads.
	 * @return array{0:array<string,mixed>,1:array<string,array<string,mixed>>}
	 */
	private function normalize_legacy_import( array $manifest, array $payloads ): array {
		$normalized_modules = [];
		foreach ( (array) ( $manifest['modules'] ?? [] ) as $module_id => $module_meta ) {
			$normalized_id = $this->legacy_module_id( (string) $module_id );
			$normalized_modules[ $normalized_id ] = is_array( $module_meta ) ? $module_meta : [];
		}

		$normalized_payloads = [];
		foreach ( $payloads as $module_id => $payload ) {
			if ( '_media' === $module_id ) {
				$normalized_payloads[ $module_id ] = $payload;
				continue;
			}

			$normalized_id = $this->legacy_module_id( (string) $module_id );
			$payload       = $this->normalize_legacy_value( $payload );
			if ( isset( $normalized_payloads[ $normalized_id ] ) ) {
				$normalized_payloads[ $normalized_id ] = $this->merge_legacy_payloads( $normalized_payloads[ $normalized_id ], $payload );
			} else {
				$normalized_payloads[ $normalized_id ] = $payload;
			}
		}

		$manifest['modules'] = $normalized_modules;
		if ( ! isset( $manifest['total_posts'] ) ) {
			$manifest['total_posts'] = array_reduce(
				$normalized_payloads,
				static function ( int $total, $payload ): int {
					return $total + (int) count( $payload['posts'] ?? [] );
				},
				0
			);
		}

		return [ $manifest, $normalized_payloads ];
	}

	/**
	 * Map known legacy module IDs to the current ecosystem IDs.
	 *
	 * @param string $module_id Legacy or current module ID.
	 * @return string Current module ID.
	 */
	private function legacy_module_id( string $module_id ): string {
		$module_id = strtolower( trim( $module_id ) );
		$map       = [
			'theatre-manager'     => 'stagekitwp-core',
			'theatre_manager'     => 'stagekitwp-core',
			'tm-core'             => 'stagekitwp-core',
			'tm-members-area'     => 'stagekitwp-members',
			'tm-members'          => 'stagekitwp-members',
			'theatre-manager-members' => 'stagekitwp-members',
			'tm-rc-library'       => 'stagekitwp-rc-library',
			'tm-sync'             => 'stagekitwp-sync',
			'theatre-manager-sync'=> 'stagekitwp-sync',
			'tm-theme'            => 'stagekitwp-theme',
			'theatre-manager-theme' => 'stagekitwp-theme',
			'tm-io'               => 'stagekitwp-import-export',
		];

		return $map[ $module_id ] ?? $module_id;
	}

	/**
	 * Recursively translate legacy identifiers in payload keys and values.
	 *
	 * @param mixed $value Payload value.
	 * @return mixed Normalized value.
	 */
	private function normalize_legacy_value( $value ) {
		if ( is_array( $value ) ) {
			$normalized = [];
			foreach ( $value as $key => $item ) {
				$normalized_key                  = is_string( $key ) ? $this->normalize_legacy_string( $key ) : $key;
				$normalized[ $normalized_key ] = $this->normalize_legacy_value( $item );
			}
			return $normalized;
		}

		if ( is_object( $value ) ) {
			return $value;
		}

		return is_string( $value ) ? $this->normalize_legacy_string( $value ) : $value;
	}

	/**
	 * Translate only namespace-shaped legacy strings.
	 *
	 * @param string $value String to normalize.
	 * @return string Normalized string.
	 */
	private function normalize_legacy_string( string $value ): string {
		return str_replace(
			[ 'THEATRE_MANAGER_', 'TM_MA_', 'TM_RCL_', 'TM_IO_', 'TM_SYNC_', 'TMS_', '_tm_', 'tm_', 'tm-', 'theatre-manager' ],
			[ 'STAGEKITWP_CORE_', 'STAGEKITWP_MEMBERS_', 'STAGEKITWP_RC_LIBRARY_', 'STAGEKITWP_IMPORT_EXPORT_', 'STAGEKITWP_SYNC_', 'STAGEKITWP_SYNC_', '_stagekitwp_', 'stagekitwp_', 'stagekitwp-', 'stagekitwp' ],
			$value
		);
	}

	/**
	 * Merge payloads when multiple legacy IDs map to one current module.
	 *
	 * @param array<string,mixed> $first  Existing payload.
	 * @param array<string,mixed> $second Incoming payload.
	 * @return array<string,mixed> Merged payload.
	 */
	private function merge_legacy_payloads( array $first, array $second ): array {
		foreach ( [ 'posts', 'options', 'extra' ] as $key ) {
			if ( empty( $second[ $key ] ) ) {
				continue;
			}
			if ( 'posts' === $key ) {
				$first[ $key ] = array_merge( (array) ( $first[ $key ] ?? [] ), (array) $second[ $key ] );
			} else {
				$first[ $key ] = array_merge( (array) ( $first[ $key ] ?? [] ), (array) $second[ $key ] );
			}
		}

		return $first;
	}

	// ── Media import ──────────────────────────────────────────────────────────

	/**
	 * Import extracted media files into the WordPress media library.
	 *
	 * @param array<string,mixed> $media_data  { index: [], dir: string }
	 * @return array{imported:int,skipped:int,errors:string[],id_map:array<int,int>,url_map:array<string,string>}
	 */
	private function import_media( array $media_data ): array {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$summary = [ 'imported' => 0, 'skipped' => 0, 'errors' => [], 'id_map' => [], 'url_map' => [] ];
		$dir     = trailingslashit( $media_data['dir'] );

		foreach ( $media_data['index'] as $old_id => $info ) {
			$old_id = (int) $old_id;
			$file   = $dir . $info['filename'];
			if ( ! file_exists( $file ) ) {
				$summary['errors'][] = sprintf( __( 'Media file not found: %s', 'stagekitwp-io' ), $info['filename'] );
				continue;
			}

			// Check if already imported (by original URL stored in postmeta).
			$existing = get_posts( [
				'post_type'  => 'attachment',
				'meta_key'   => '_stagekitwp_import_export_original_url',
				'meta_value' => $info['original_url'],
				'fields'     => 'ids',
				'numberposts'=> 1,
			] );

			// Same-site re-import (or a URL collision): the exact original upload
			// already exists here even though it was never tagged by a previous
			// TM IO import. Reuse it instead of sideloading a duplicate file.
			if ( ! $existing && ! empty( $info['original_url'] ) ) {
				$local_id = attachment_url_to_postid( $info['original_url'] );
				if ( $local_id ) {
					$existing = [ $local_id ];
				}
			}

			if ( $existing ) {
				$new_id = (int) $existing[0];
				$summary['skipped']++;
				$this->record_media_map( $summary, $old_id, $new_id, $info );
				continue;
			}

			$attachment_id = media_handle_sideload(
				[
					'name'     => $info['filename'],
					'type'     => $info['mime_type'],
					'tmp_name' => $file,
					'error'    => 0,
					'size'     => filesize( $file ),
				],
				0,
				$info['post_title'] ?? $info['filename']
			);

			if ( is_wp_error( $attachment_id ) ) {
				$summary['errors'][] = $attachment_id->get_error_message();
			} else {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $info['alt_text'] ?? '' );
				update_post_meta( $attachment_id, '_stagekitwp_import_export_original_url',      $info['original_url'] ?? '' );
				update_post_meta( $attachment_id, '_stagekitwp_import_export_original_id',       $old_id );
				$summary['imported']++;
				$this->record_media_map( $summary, $old_id, (int) $attachment_id, $info );
			}
		}

		return $summary;
	}

	/**
	 * Record old→new mappings for one imported/skipped attachment, covering the
	 * attachment ID, its full-size URL, and every registered size-variant URL.
	 *
	 * @param array<string,mixed> $summary  (by reference)
	 * @param int                 $old_id
	 * @param int                 $new_id
	 * @param array<string,mixed> $info
	 */
	private function record_media_map( array &$summary, int $old_id, int $new_id, array $info ): void {
		if ( $old_id > 0 && $new_id > 0 ) {
			$summary['id_map'][ $old_id ] = $new_id;
		}

		$old_url = $info['original_url'] ?? '';
		$new_url = wp_get_attachment_url( $new_id ) ?: '';
		if ( $old_url && $new_url ) {
			$summary['url_map'][ $old_url ] = $new_url;

			// Map size-variant URLs too (e.g. image-300x200.jpg).
			$old_base = trailingslashit( dirname( $old_url ) );
			$new_meta = wp_get_attachment_metadata( $new_id );
			if ( ! empty( $new_meta['sizes'] ) ) {
				foreach ( $new_meta['sizes'] as $size => $data ) {
					$variant_new = wp_get_attachment_image_url( $new_id, $size );
					if ( $variant_new && ! empty( $data['file'] ) ) {
						$summary['url_map'][ $old_base . $data['file'] ] = $variant_new;
					}
				}
			}
		}
	}

	// ── Media remapping ───────────────────────────────────────────────────────

	/**
	 * Rewrite stale attachment references in a module payload so imported posts
	 * point at the newly-imported media on this site. Public so the async queue
	 * can remap each batch after seeding the maps via set_media_maps().
	 *
	 * Rewrites, using the old→new maps:
	 *   - thumbnail_id (featured image)
	 *   - meta values that are attachment IDs or upload URLs (scalars & arrays)
	 *   - upload URLs embedded in post_content
	 *
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	public function remap_payload_media( array $payload ): array {
		if ( empty( $this->media_id_map ) && empty( $this->media_url_map ) ) {
			return $payload; // nothing to remap
		}
		if ( empty( $payload['posts'] ) || ! is_array( $payload['posts'] ) ) {
			return $payload;
		}

		foreach ( $payload['posts'] as &$post ) {
			// Featured image.
			if ( ! empty( $post['thumbnail_id'] ) ) {
				$old = (int) $post['thumbnail_id'];
				if ( isset( $this->media_id_map[ $old ] ) ) {
					$post['thumbnail_id'] = $this->media_id_map[ $old ];
				}
			}

			// Meta values (IDs and URLs, scalar or nested) — except relationship keys,
			// which point at OTHER exported posts, not attachments, and are remapped
			// separately (correctly) once every post has landed.
			if ( ! empty( $post['meta'] ) && is_array( $post['meta'] ) ) {
				$relationship_keys = $this->relationship_meta_key_set();
				foreach ( $post['meta'] as $key => $value ) {
					if ( isset( $relationship_keys[ $key ] ) ) {
						continue;
					}
					$post['meta'][ $key ] = $this->remap_value( $value );
				}
			}

			// Inline content URLs.
			if ( ! empty( $post['post_content'] ) && is_string( $post['post_content'] ) && $this->media_url_map ) {
				$post['post_content'] = strtr( $post['post_content'], $this->media_url_map );
			}
		}
		unset( $post );

		return $payload;
	}

	/**
	 * Remap a single meta value (recursively): attachment IDs via the ID map,
	 * upload URLs via the URL map. Leaves unrelated values untouched.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function remap_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = $this->remap_value( $v );
			}
			return $value;
		}

		// Numeric attachment ID reference.
		if ( is_numeric( $value ) ) {
			$old = (int) $value;
			if ( isset( $this->media_id_map[ $old ] ) ) {
				// Preserve the original scalar type (string vs int) as stored.
				return is_string( $value ) ? (string) $this->media_id_map[ $old ] : $this->media_id_map[ $old ];
			}
			return $value;
		}

		// URL reference (exact match, then size-variant-tolerant via strtr).
		if ( is_string( $value ) && false !== strpos( $value, '/wp-content/uploads/' ) ) {
			if ( isset( $this->media_url_map[ $value ] ) ) {
				return $this->media_url_map[ $value ];
			}
			return strtr( $value, $this->media_url_map );
		}

		return $value;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function prepare_media_dir(): string {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . 'stagekitwp-io/import-' . gmdate( 'YmdHis' );
		wp_mkdir_p( $dir );
		return $dir;
	}
}
