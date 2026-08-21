<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_CSV_Importer – rapid season builder via CSV upload.
 *
 * CSV Column Spec (headers are normalised: lowercase, spaces→underscores):
 *   show_title*         post_title + _stagekitwp_show_name
 *   author              _stagekitwp_show_author
 *   sub_authors         _stagekitwp_show_sub_authors
 *   synopsis            _stagekitwp_show_synopsis
 *   genre               _stagekitwp_show_genre
 *   director            _stagekitwp_show_director
 *   associate_director  _stagekitwp_show_associate_director
 *   producer            _stagekitwp_show_producer
 *   stage_manager       _stagekitwp_show_stage_manager
 *   time_slot           _stagekitwp_show_time_slot
 *   show_dates          _stagekitwp_show_show_dates
 *   tickets_url         _stagekitwp_show_tickets_url
 *   audition_date       _stagekitwp_show_audition_date
 *   audition_details    _stagekitwp_show_audition_details
 *   season_title        resolved → _stagekitwp_show_season (post ID)
 *   venue_name          resolved → _stagekitwp_show_venue (post ID)
 *   post_status         publish|draft|pending  (default: draft)
 *  (* required)
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class STAGEKITWP_IMPORT_EXPORT_CSV_Importer {

	// ── Column → meta key map ────────────────────────────────────────────────

	private const COLUMN_MAP = [
		'show_title'           => '_stagekitwp_show_name',
		'author'               => '_stagekitwp_show_author',
		'sub_authors'          => '_stagekitwp_show_sub_authors',
		'synopsis'             => '_stagekitwp_show_synopsis',
		'genre'                => '_stagekitwp_show_genre',
		'director'             => '_stagekitwp_show_director',
		'associate_director'   => '_stagekitwp_show_associate_director',
		'producer'             => '_stagekitwp_show_producer',
		'stage_manager'        => '_stagekitwp_show_stage_manager',
		'time_slot'            => '_stagekitwp_show_time_slot',
		'show_dates'           => '_stagekitwp_show_show_dates',
		'tickets_url'          => '_stagekitwp_show_tickets_url',
		'audition_date'        => '_stagekitwp_show_audition_date',
		'audition_details'     => '_stagekitwp_show_audition_details',
		'season_title'         => '_stagekitwp_show_season',
		'venue_name'           => '_stagekitwp_show_venue',
	];

	/**
	 * Extra columns that are NOT show meta but control season creation.
	 * season_start_date / season_end_date only apply when a new season is created.
	 */
	private const CONTROL_COLS = [ 'post_status', 'season_start_date', 'season_end_date' ];

	/** @var array<string, int> */
	private array $title_cache = [];

	/** @var array<string, string> Tracks which season cache keys were newly created this run (key => title). */
	private array $new_seasons = [];

	/** @var int[] Show post IDs created during this run (for rollback). */
	private array $created_ids = [];

	/** @var int[] Season post IDs created during this run (for rollback). */
	private array $created_season_ids = [];

	/** @var array<int, array<string,mixed>> Pre-import meta snapshots for updated posts (for rollback). */
	private array $updated_snapshots = [];

	// ── Public API ────────────────────────────────────────────────────────────

	public static function template_headers(): array {
		return array_merge( array_keys( self::COLUMN_MAP ), self::CONTROL_COLS );
	}

	/**
	 * @param string              $file_path
	 * @param array<string,mixed> $options  conflict, dry_run, season_id
	 * @return array<string,mixed>|WP_Error
	 */
	public function import( string $file_path, array $options = [] ): array|\WP_Error {
		$conflict  = $options['conflict']  ?? 'skip';
		$dry_run   = ! empty( $options['dry_run'] );
		$season_id = (int) ( $options['season_id'] ?? 0 );

		$rows = $this->parse_csv( $file_path );
		if ( is_wp_error( $rows ) ) { return $rows; }
		if ( empty( $rows ) ) {
			return new \WP_Error( 'stagekitwp_import_export_csv_empty', __( 'The CSV file contains no data rows.', 'stagekitwp-io' ) );
		}

		$valid = $this->validate_headers( array_keys( $rows[0] ) );
		if ( is_wp_error( $valid ) ) { return $valid; }

		// Pre-warm the title cache with all existing show IDs in one query.
		// This replaces N per-row get_posts() calls with a single bulk lookup.
		$this->prime_show_cache();

		$summary = [
			'dry_run'            => $dry_run,
			'total'              => count( $rows ),
			'imported'           => 0,
			'updated'            => 0,
			'skipped'            => 0,
			'seasons_created'    => 0,
			'seasons_to_create'  => [], // dry-run only: names of seasons that would be created
			'errors'             => [],
			'previews'           => [],
		];

		foreach ( $rows as $idx => $row ) {
			$row_num = $idx + 2;
			$title   = trim( $row['show_title'] ?? '' );
			if ( '' === $title ) {
				$summary['errors'][] = sprintf( __( 'Row %d: show_title is empty – skipped.', 'stagekitwp-io' ), $row_num );
				$summary['skipped']++;
				continue;
			}

			$resolved_season = $this->resolve_season( $row, $season_id, $dry_run );
			$resolved_venue  = $this->resolve_venue( $row );

			if ( $dry_run ) {
				$this->preview_row( $summary, $row, $row_num, $title, $conflict, $resolved_season, $resolved_venue );
				continue;
			}

			$result = $this->upsert_show( $row, $conflict, $resolved_season, $resolved_venue );
			if ( is_wp_error( $result ) ) {
				$summary['errors'][] = sprintf( __( 'Row %d (%s): %s', 'stagekitwp-io' ), $row_num, esc_html( $title ), $result->get_error_message() );
			} elseif ( 'skipped' === $result ) {
				$summary['skipped']++;
			} elseif ( 'updated' === $result ) {
				$summary['updated']++;
			} else {
				$summary['imported']++;
			}
		}

		// $new_seasons values are always season title strings now.
		$season_names = array_values( $this->new_seasons );
		$summary['seasons_created']   = count( $season_names );
		$summary['seasons_to_create'] = $season_names; // names list (dry-run preview + real run confirmation)

		// Include rollback token if anything was written (real run only).
		if ( ! $dry_run && ( $this->created_ids || $this->created_season_ids || $this->updated_snapshots ) ) {
			$token = $this->store_rollback_snapshot();
			$summary['rollback_token'] = $token;
		}

		return $summary;
	}

	// ── CSV parsing ───────────────────────────────────────────────────────────

	private function parse_csv( string $path ): array|\WP_Error {
		if ( ! file_exists( $path ) ) {
			return new \WP_Error( 'stagekitwp_import_export_csv_missing', __( 'CSV file not found.', 'stagekitwp-io' ) );
		}

		// ── Encoding detection & normalisation ───────────────────────────────
		// Read the raw bytes, detect encoding, and convert everything to clean
		// UTF-8 before handing it to fgetcsv. This handles:
		//   • Windows-1252 / cp1252  (Excel default on Windows)
		//   • ISO-8859-1 / latin-1
		//   • UTF-8 with or without BOM
		//   • Non-breaking space (\xA0) that prefixes cell values
		$raw = file_get_contents( $path ); // phpcs:ignore
		if ( false === $raw ) {
			return new \WP_Error( 'stagekitwp_import_export_csv_open', __( 'Could not read CSV file.', 'stagekitwp-io' ) );
		}

		// Strip UTF-8 BOM if present.
		if ( str_starts_with( $raw, "\xEF\xBB\xBF" ) ) {
			$raw = substr( $raw, 3 );
		}

		// Detect whether this is valid UTF-8. If not, assume Windows-1252
		// (a superset of ISO-8859-1 that covers cp1252 curly quotes like \x92).
		if ( ! mb_check_encoding( $raw, 'UTF-8' ) ) {
			$converted = mb_convert_encoding( $raw, 'UTF-8', 'Windows-1252' );
			if ( false !== $converted && '' !== $converted ) {
				$raw = $converted;
			}
		}

		// Write the normalised content to a temp file so fgetcsv can stream it.
		$tmp = tempnam( sys_get_temp_dir(), 'tmio_csv_' );
		file_put_contents( $tmp, $raw ); // phpcs:ignore

		$handle = fopen( $tmp, 'r' ); // phpcs:ignore
		if ( ! $handle ) {
			@unlink( $tmp ); // phpcs:ignore
			return new \WP_Error( 'stagekitwp_import_export_csv_open', __( 'Could not open CSV file.', 'stagekitwp-io' ) );
		}

		// Auto-detect delimiter.
		$first_line = fgets( $handle );
		rewind( $handle );
		$delimiters = [ ',' => 0, "\t" => 0, ';' => 0 ];
		foreach ( array_keys( $delimiters ) as $d ) {
			$delimiters[ $d ] = substr_count( $first_line, $d );
		}
		arsort( $delimiters );
		$delimiter = array_key_first( $delimiters );

		$raw_headers = fgetcsv( $handle, 0, $delimiter, '"', '\\' );
		if ( ! $raw_headers ) {
			fclose( $handle ); // phpcs:ignore
			@unlink( $tmp );   // phpcs:ignore
			return new \WP_Error( 'stagekitwp_import_export_csv_header', __( 'Could not read CSV headers.', 'stagekitwp-io' ) );
		}
		$headers = array_map( fn( $h ) => strtolower( trim( str_replace( [ ' ', '-' ], '_', $h ) ) ), $raw_headers );

		$rows = [];
		while ( ( $row = fgetcsv( $handle, 0, $delimiter, '"', '\\' ) ) !== false ) {
			// Strip leading/trailing whitespace including Unicode non-breaking space
			// (U+00A0, which becomes \xC2\xA0 after UTF-8 conversion and is NOT
			// removed by PHP's trim()). Common in Excel cp1252 CSV exports.
			$row = array_map(
				fn( $v ) => preg_replace( '/^[\s\xA0\x{00A0}]+|[\s\xA0\x{00A0}]+$/u', '', (string) $v ),
				$row
			);
			if ( count( array_filter( $row, fn( $v ) => '' !== $v ) ) === 0 ) { continue; }
			while ( count( $row ) < count( $headers ) ) { $row[] = ''; }
			$rows[] = array_combine( $headers, array_slice( $row, 0, count( $headers ) ) );
		}
		fclose( $handle ); // phpcs:ignore
		@unlink( $tmp );   // phpcs:ignore — clean up normalised temp file
		return $rows;
	}

	// ── Validation ────────────────────────────────────────────────────────────

	private function validate_headers( array $headers ): true|\WP_Error {
		if ( ! in_array( 'show_title', $headers, true ) ) {
			return new \WP_Error( 'stagekitwp_import_export_csv_no_title',
				sprintf( __( 'CSV missing required column "show_title". Found: %s', 'stagekitwp-io' ), implode( ', ', $headers ) )
			);
		}
		return true;
	}

	// ── Dry-run preview row ───────────────────────────────────────────────────

	private function preview_row( array &$summary, array $row, int $row_num, string $title, string $conflict, int $season_id, int $venue_id ): void {
		$existing = $this->find_existing_show( $title );
		$action   = $existing ? ( 'skip' === $conflict ? 'skip' : 'update' ) : 'create';

		// Determine season label and whether it would need to be created.
		if ( $season_id > 0 ) {
			$season_label = get_the_title( $season_id );
		} elseif ( ! empty( $row['season_title'] ) ) {
			$exists_id    = $this->find_post_by_title( trim( $row['season_title'] ), 'season' );
			$season_label = $exists_id
				? trim( $row['season_title'] )
				: trim( $row['season_title'] ) . ' ⚠️ (will be created)';
		} else {
			$season_label = '—';
		}

		$summary['previews'][] = [
			'row'        => $row_num,
			'title'      => $title,
			'action'     => $action,
			'season'     => $season_label,
			'venue'      => $venue_id  ? get_the_title( $venue_id )  : ( $row['venue_name']   ?? '—' ),
			'time_slot'  => $row['time_slot']  ?? '',
			'show_dates' => $row['show_dates'] ?? '',
		];
		$summary[ 'would_' . $action ] = ( $summary[ 'would_' . $action ] ?? 0 ) + 1;
	}

	// ── Show upsert ───────────────────────────────────────────────────────────

	private function upsert_show( array $row, string $conflict, int $season_id, int $venue_id ): int|string|\WP_Error {
		$title    = sanitize_text_field( $row['show_title'] ?? '' );
		$existing = $this->find_existing_show( $title );

		if ( $existing && 'skip' === $conflict ) { return 'skipped'; }

		$status = sanitize_key( $row['post_status'] ?? 'draft' );
		if ( ! in_array( $status, [ 'publish', 'draft', 'pending', 'private' ], true ) ) {
			$status = 'draft';
		}

		$post_arr = [
			'post_type'    => 'show',
			'post_status'  => $status,
			'post_title'   => $title,
			'post_name'    => sanitize_title( $title ),
			'post_content' => '',
		];

		if ( $existing && 'overwrite' === $conflict ) {
			// Snapshot existing meta before overwriting so rollback can restore it.
			if ( ! isset( $this->updated_snapshots[ $existing ] ) ) {
				$this->updated_snapshots[ $existing ] = [
					'post'  => get_post( $existing, ARRAY_A ),
					'meta'  => get_post_meta( $existing ),
				];
			}
			$post_arr['ID'] = $existing;
			$post_id        = wp_update_post( $post_arr, true );
			$action         = 'updated';
		} else {
			$post_id = wp_insert_post( $post_arr, true );
			$action  = 'inserted';
		}

		if ( is_wp_error( $post_id ) ) { return $post_id; }

		// Track new posts for rollback.
		if ( 'inserted' === $action ) {
			$this->created_ids[] = $post_id;
		}

		$this->write_show_meta( $post_id, $row, $season_id, $venue_id );

		return ( 'overwrite' === $conflict && $existing ) ? 'updated' : $post_id;
	}

	private function write_show_meta( int $post_id, array $row, int $season_id, int $venue_id ): void {
		foreach ( self::COLUMN_MAP as $col => $meta_key ) {
			if ( '_stagekitwp_show_season' === $meta_key ) {
				if ( $season_id > 0 ) { update_post_meta( $post_id, '_stagekitwp_show_season', $season_id ); }
				continue;
			}
			if ( '_stagekitwp_show_venue' === $meta_key ) {
				if ( $venue_id > 0 ) { update_post_meta( $post_id, '_stagekitwp_show_venue', $venue_id ); }
				continue;
			}
			if ( isset( $row[ $col ] ) && '' !== $row[ $col ] ) {
				$value = str_ends_with( $col, '_url' )
					? esc_url_raw( $row[ $col ] )
					: sanitize_textarea_field( $row[ $col ] );
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
		// Mirror title in meta.
		update_post_meta( $post_id, '_stagekitwp_show_name', sanitize_text_field( $row['show_title'] ) );
		// Init empty fields so the show builder never sees undefined.
		foreach ( [ '_stagekitwp_show_sm_image', '_stagekitwp_show_program', '_stagekitwp_show_program_url' ] as $k ) {
			if ( '' === (string) get_post_meta( $post_id, $k, true ) ) {
				update_post_meta( $post_id, $k, '' );
			}
		}
	}

	// ── Season / venue resolution ─────────────────────────────────────────────

	/**
	 * Find or create a season post.
	 *
	 * Resolution order:
	 *  1. Forced season_id passed via the admin dropdown (overrides CSV).
	 *  2. season_title column — look for an existing season by exact title.
	 *  3. season_title column — if not found:
	 *       dry_run=true  → record the title in $new_seasons; return 0 (no DB write)
	 *       dry_run=false → create the season post and write _stagekitwp_season_start/end_date meta
	 *
	 * Created seasons are cached so a multi-row CSV only inserts once.
	 *
	 * @param array<string,string> $row
	 * @param int                  $forced_id  0 = use CSV column
	 * @param bool                 $dry_run    When true, skip the wp_insert_post
	 * @return int  Season post ID, or 0 if no season info provided / dry-run
	 */
	private function resolve_season( array $row, int $forced_id, bool $dry_run = false ): int {
		// 1. Admin dropdown overrides everything.
		if ( $forced_id > 0 ) {
			return $forced_id;
		}

		$title = trim( $row['season_title'] ?? '' );
		if ( '' === $title ) {
			return 0;
		}

		// 2. Check cache first (avoids duplicate work across rows).
		$cache_key = 'season::' . $title;
		if ( isset( $this->title_cache[ $cache_key ] ) ) {
			return $this->title_cache[ $cache_key ];
		}

		// 3. Look for existing season by title.
		$existing = $this->find_post_by_title( $title, 'season' );
		if ( $existing > 0 ) {
			return $existing; // already cached inside find_post_by_title
		}

		// 4. Not found.
		if ( $dry_run ) {
			// Dry-run: record name for preview; do NOT write to DB.
			$this->title_cache[ $cache_key ] = 0;
			// Store the title string (not `true`) so seasons_to_create can list names.
			$this->new_seasons[ $cache_key ] = $title;
			return 0;
		}

		// Real run: create the season post.
		$new_id = wp_insert_post( [
			'post_type'    => 'season',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => sanitize_title( $title ),
			'post_content' => '',
		], true );

		if ( is_wp_error( $new_id ) ) {
			// Couldn't create — fail gracefully; shows will have no season.
			$this->title_cache[ $cache_key ] = 0;
			return 0;
		}

		// Write _stagekitwp_season_start_date / _stagekitwp_season_end_date – normalised to yyyy-mm-dd.
		$start = $this->normalize_date( trim( $row['season_start_date'] ?? '' ) );
		$end   = $this->normalize_date( trim( $row['season_end_date']   ?? '' ) );
		if ( '' !== $start ) {
			update_post_meta( $new_id, '_stagekitwp_season_start_date', $start );
		}
		if ( '' !== $end ) {
			update_post_meta( $new_id, '_stagekitwp_season_end_date', $end );
		}

		// Cache so subsequent rows in the same CSV reuse the new ID.
		$this->title_cache[ $cache_key ]    = $new_id;
		$this->new_seasons[ $cache_key ]    = $title; // store title for summary
		$this->created_season_ids[]         = $new_id; // track for rollback
		return $new_id;
	}

	private function resolve_venue( array $row ): int {
		$name = trim( $row['venue_name'] ?? '' );
		return $name !== '' ? $this->find_post_by_title( $name, 'venue' ) : 0;
	}

	/**
	 * Normalise any recognisable date string to yyyy-mm-dd.
	 *
	 * Handles the most common spreadsheet output formats:
	 *   yyyy-mm-dd          2026-10-01          ← already correct, pass through
	 *   dd/mm/yyyy          01/10/2026          ← European / Australian
	 *   mm/dd/yyyy          10/01/2026          ← North American
	 *   dd-mm-yyyy          01-10-2026
	 *   mm-dd-yyyy          10-01-2026
	 *   dd.mm.yyyy          01.10.2026          ← German / French
	 *   Month d, yyyy       October 1, 2026     ← long-form English
	 *   d Month yyyy        1 October 2026
	 *   Mon d, yyyy         Oct 1, 2026         ← abbreviated English
	 *   d Mon yyyy          1 Oct 2026
	 *   yyyy/mm/dd          2026/10/01          ← ISO variant with slashes
	 *   Excel serial        46,000              ← Windows date serial (days since 1900-01-00)
	 *
	 * Ambiguous two-digit day/month slashes (e.g. 01/02/2026) are treated as
	 * dd/mm/yyyy by default – the international standard. Pass
	 * $prefer_mdy = true to treat them as mm/dd/yyyy instead.
	 *
	 * Returns the normalised yyyy-mm-dd string, or an empty string if the
	 * input is blank. Returns the original value unchanged (with a note logged
	 * to error_log) when it cannot be parsed – never silently drops data.
	 *
	 * @param string $raw        The raw date string from the CSV cell.
	 * @param bool   $prefer_mdy When true, treat ambiguous dd/mm as mm/dd.
	 * @return string  yyyy-mm-dd, the original string, or '' if blank.
	 */
	public static function normalize_date( string $raw, bool $prefer_mdy = false ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}

		// ── Already yyyy-mm-dd ────────────────────────────────────────────────
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
			// Validate the date is real (e.g. not 2026-13-01).
			[ $y, $m, $d ] = explode( '-', $raw );
			if ( checkdate( (int) $m, (int) $d, (int) $y ) ) {
				return $raw;
			}
		}

		// ── yyyy/mm/dd (ISO with slashes) ─────────────────────────────────────
		if ( preg_match( '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $raw, $m ) ) {
			if ( checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
				return sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] );
			}
		}

		// ── Excel date serial (integer, e.g. 46000) ───────────────────────────
		if ( preg_match( '/^\d{4,6}$/', $raw ) ) {
			$serial = (int) $raw;
			if ( $serial > 1000 && $serial < 100000 ) {
				// Excel epoch: 1900-01-00 (serial 1 = 1900-01-01, but Excel wrongly
				// counts 1900 as a leap year, so subtract 1 extra day for serials > 59).
				$epoch   = mktime( 0, 0, 0, 1, 1, 1900 );
				$offset  = ( $serial > 59 ) ? $serial - 2 : $serial - 1;
				$ts      = $epoch + $offset * 86400;
				$year    = (int) gmdate( 'Y', $ts );
				if ( $year >= 1900 && $year <= 2200 ) {
					return gmdate( 'Y-m-d', $ts );
				}
			}
		}

		// ── Slash / dot / dash separated with 4-digit year at end ─────────────
		// Matches: dd/mm/yyyy  mm/dd/yyyy  dd-mm-yyyy  dd.mm.yyyy  etc.
		if ( preg_match( '/^(\d{1,2})[\-\/\.](\d{1,2})[\-\/\.](\d{4})$/', $raw, $m ) ) {
			if ( $prefer_mdy ) {
				$month = (int) $m[1]; $day = (int) $m[2];
			} else {
				$day = (int) $m[1]; $month = (int) $m[2]; // default: dd/mm/yyyy
			}
			$year = (int) $m[3];
			if ( checkdate( $month, $day, $year ) ) {
				return sprintf( '%04d-%02d-%02d', $year, $month, $day );
			}
			// Try the other interpretation before giving up.
			$alt_month = (int) $m[1]; $alt_day = (int) $m[2];
			if ( $prefer_mdy ) { $alt_month = (int) $m[2]; $alt_day = (int) $m[1]; }
			if ( checkdate( $alt_month, $alt_day, $year ) ) {
				return sprintf( '%04d-%02d-%02d', $year, $alt_month, $alt_day );
			}
		}

		// ── 4-digit year at start with separators ─────────────────────────────
		// Matches: yyyy/dd/mm  yyyy-dd-mm  (less common, but seen in some locales)
		// NOTE: skipped – indistinguishable from yyyy-mm-dd without locale hint.

		// ── Long-form English: "October 1, 2026" / "1 October 2026" ──────────
		$months_long = [ 'january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,
						 'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12 ];
		$months_abbr = [ 'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,
						 'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12 ];
		$all_months  = array_merge( $months_long, $months_abbr );

		// "Month d, yyyy" or "Month d yyyy"
		if ( preg_match( '/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/', $raw, $m ) ) {
			$mn = strtolower( $m[1] );
			if ( isset( $all_months[ $mn ] ) ) {
				$month = $all_months[ $mn ]; $day = (int) $m[2]; $year = (int) $m[3];
				if ( checkdate( $month, $day, $year ) ) {
					return sprintf( '%04d-%02d-%02d', $year, $month, $day );
				}
			}
		}

		// "d Month yyyy" or "d Month, yyyy"
		if ( preg_match( '/^(\d{1,2})\s+([A-Za-z]+),?\s+(\d{4})$/', $raw, $m ) ) {
			$mn = strtolower( $m[2] );
			if ( isset( $all_months[ $mn ] ) ) {
				$day = (int) $m[1]; $month = $all_months[ $mn ]; $year = (int) $m[3];
				if ( checkdate( $month, $day, $year ) ) {
					return sprintf( '%04d-%02d-%02d', $year, $month, $day );
				}
			}
		}

		// ── PHP strtotime fallback (handles many other formats) ───────────────
		$ts = strtotime( $raw );
		if ( false !== $ts && $ts > 0 ) {
			$year = (int) gmdate( 'Y', $ts );
			if ( $year >= 1900 && $year <= 2200 ) {
				return gmdate( 'Y-m-d', $ts );
			}
		}

		// ── Unrecognised – log and return original so data is never lost ────────
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		error_log( "TM IO CSV: could not parse date '{$raw}' – storing as-is." );
		return sanitize_text_field( $raw );
	}

	private function find_post_by_title( string $title, string $post_type ): int {
		$key = $post_type . '::' . $title;
		if ( isset( $this->title_cache[ $key ] ) ) { return $this->title_cache[ $key ]; }
		$safe_title = $this->sanitise_title_for_query( $title );
		if ( '' === $safe_title ) {
			$this->title_cache[ $key ] = 0;
			return 0;
		}
		$q  = new WP_Query( [
			'post_type'              => $post_type,
			'post_status'            => 'any',
			'title'                  => $safe_title,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );
		$id = ! empty( $q->posts ) ? (int) $q->posts[0] : 0;
		$this->title_cache[ $key ] = $id;
		return $id;
	}

	private function find_existing_show( string $title ): int {
		// Cache key uses the normalised title so mangled characters don't bypass it.
		$key = 'show::' . $this->normalise_title_key( $title );
		if ( array_key_exists( $key, $this->title_cache ) ) {
			return $this->title_cache[ $key ];
		}
		// Cache miss (title not seen during prime_show_cache) — query individually.
		// Sanitise to avoid SQLite tokeniser crash on non-ASCII / replacement chars.
		$safe_title = $this->sanitise_title_for_query( $title );
		if ( '' === $safe_title ) {
			$this->title_cache[ $key ] = 0;
			return 0;
		}
		$existing = get_posts( [
			'post_type'              => 'show',
			'post_status'            => 'any',
			'title'                  => $safe_title,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		] );
		$id = ! empty( $existing ) ? (int) $existing[0] : 0;
		$this->title_cache[ $key ] = $id;
		return $id;
	}

	/**
	 * Load all existing show post IDs and titles into $title_cache in one query.
	 * Uses a direct $wpdb->get_results() (SELECT ID, post_title only) instead of
	 * WP_Query to avoid the SQLite driver timeout on large result sets with
	 * posts_per_page=-1 and fields='all'.
	 */
	private function prime_show_cache(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title FROM {$wpdb->posts}
				WHERE post_type = %s
				  AND post_status NOT IN ('trash','auto-draft')",
				'show'
			)
		);
		foreach ( (array) $rows as $row ) {
			$title = (string) ( $row->post_title ?? '' );
			if ( '' === $title ) { continue; }
			$key = 'show::' . $this->normalise_title_key( $title );
			$this->title_cache[ $key ] = (int) $row->ID;
		}
	}

	/**
	 * Normalise a title to a cache-key string:
	 * lowercase, collapse whitespace, convert smart quotes / curly apostrophes
	 * to their ASCII equivalents so mangled CSV input still matches DB titles.
	 */
	/**
	 * Returns the byte sequences for characters we want to normalise.
	 * Using explicit UTF-8 byte strings avoids PHP single-quote \u{} escape issues.
	 *
	 * @return array{0:string[],1:string[]}
	 */
	private static function unicode_map(): array {
		return [
			// search strings (UTF-8 byte sequences)
			[
				"\xE2\x80\x98", // U+2018 LEFT SINGLE QUOTATION MARK
				"\xE2\x80\x99", // U+2019 RIGHT SINGLE QUOTATION MARK / apostrophe
				"\xE2\x80\x9A", // U+201A SINGLE LOW-9 QUOTATION MARK
				"\xE2\x80\x9C", // U+201C LEFT DOUBLE QUOTATION MARK
				"\xE2\x80\x9D", // U+201D RIGHT DOUBLE QUOTATION MARK
				"\xE2\x80\x9E", // U+201E DOUBLE LOW-9 QUOTATION MARK
				"\xE2\x80\x93", // U+2013 EN DASH
				"\xE2\x80\x94", // U+2014 EM DASH
				"\xEF\xBF\xBD", // U+FFFD REPLACEMENT CHARACTER (mangled apostrophe from cp1252)
				"\xC3\xA2\xC2\x80\xC2\x99", // UTF-8 mis-decoded cp1252 apostrophe (‘’)
			],
			// replacements
			[ "'", "'", "'", '"', '"', '"', '-', '-', "'", "'" ],
		];
	}

	private function normalise_title_key( ?string $title ): string {
		if ( null === $title || '' === $title ) { return ''; }
		[ $search, $replace ] = self::unicode_map();
		$title = str_replace( $search, $replace, $title );
		return strtolower( preg_replace( '/\s+/', ' ', trim( $title ) ) );
	}

	/**
	 * Sanitise a title for direct use in a WP_Query / get_posts() call.
	 * Replaces smart quotes / curly apostrophes and strips the Unicode replacement
	 * character (U+FFFD) so the SQLite driver's SQL tokeniser never sees it.
	 */
	private function sanitise_title_for_query( string $title ): string {
		[ $search, $replace ] = self::unicode_map();
		$title = str_replace( $search, $replace, $title );
		// Remove any remaining control characters that could crash the tokeniser.
		$title = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $title );
		return trim( $title );
	}

	// ── Rollback ──────────────────────────────────────────────────────────────

	/**
	 * Persist rollback state as a transient and return a retrieval token.
	 * Token expires in 1 hour — enough time for the user to decide.
	 */
	private function store_rollback_snapshot(): string {
		$token = 'tmio_rb_' . wp_generate_password( 16, false );
		set_transient( $token, [
			'created_ids'        => $this->created_ids,
			'created_season_ids' => $this->created_season_ids,
			'updated_snapshots'  => $this->updated_snapshots,
			'timestamp'          => time(),
		], HOUR_IN_SECONDS );
		return $token;
	}

	/**
	 * Execute a rollback from a previously stored token.
	 *
	 * - Permanently deletes every show post created in the import.
	 * - Permanently deletes every season post created in the import.
	 * - Restores the post row + all meta for every show that was updated.
	 *
	 * @param string $token  The rollback token returned in the import summary.
	 * @return array{deleted_shows:int, deleted_seasons:int, restored_shows:int, errors:string[]}|\WP_Error
	 */
	public function rollback( string $token ): array|\WP_Error {
		$snapshot = get_transient( $token );
		if ( false === $snapshot ) {
			return new \WP_Error( 'stagekitwp_import_export_rb_expired',
				__( 'Rollback token not found or expired (1-hour window).', 'stagekitwp-io' ) );
		}
		delete_transient( $token ); // one-shot

		$deleted_shows   = 0;
		$deleted_seasons = 0;
		$restored_shows  = 0;
		$errors          = [];

		// 1. Delete newly created show posts.
		foreach ( (array) ( $snapshot['created_ids'] ?? [] ) as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				$deleted_shows++;
			} else {
				$errors[] = sprintf( __( 'Could not delete show post ID %d.', 'stagekitwp-io' ), $id );
			}
		}

		// 2. Delete newly created season posts.
		foreach ( (array) ( $snapshot['created_season_ids'] ?? [] ) as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				$deleted_seasons++;
			} else {
				$errors[] = sprintf( __( 'Could not delete season post ID %d.', 'stagekitwp-io' ), $id );
			}
		}

		// 3. Restore updated posts from their pre-import snapshot.
		foreach ( (array) ( $snapshot['updated_snapshots'] ?? [] ) as $id => $snap ) {
			$id = (int) $id;
			// Restore the post row.
			if ( ! empty( $snap['post'] ) ) {
				$r = wp_update_post( $snap['post'], true );
				if ( is_wp_error( $r ) ) {
					$errors[] = sprintf( __( 'Could not restore post ID %d: %s', 'stagekitwp-io' ), $id, $r->get_error_message() );
					continue;
				}
			}
			// Restore meta: delete all current meta, then re-apply snapshot.
			if ( ! empty( $snap['meta'] ) ) {
				// Remove all meta keys that exist now.
				foreach ( array_keys( get_post_meta( $id ) ) as $key ) {
					delete_post_meta( $id, $key );
				}
				// Replay snapshot (get_post_meta returns arrays of values per key).
				foreach ( $snap['meta'] as $key => $values ) {
					foreach ( (array) $values as $value ) {
						add_post_meta( $id, $key, maybe_unserialize( $value ) );
					}
				}
			}
			$restored_shows++;
		}

		return compact( 'deleted_shows', 'deleted_seasons', 'restored_shows', 'errors' );
	}

	// ── Template download ─────────────────────────────────────────────────────

	public static function stream_template(): void {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="stagekitwp-season-import-template.csv"' );
		header( 'Cache-Control: no-cache' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore
		fputcsv( $out, self::template_headers() );
		fputcsv( $out, [
			'My Show Title',              // show_title
			'Arthur Miller',              // author
			'',                           // sub_authors
			'A short synopsis.',          // synopsis
			'Drama',                      // genre
			'Jane Smith',                 // director
			'',                           // associate_director
			'',                           // producer
			'Bob Jones',                  // stage_manager
			'Fall',                       // time_slot
			'Oct 3, 4, 5, 2026',          // show_dates
			'https://tickets.example.com/', // tickets_url
			'',                           // audition_date
			'',                           // audition_details
			'2026-2027 Season',           // season_title – matched or auto-created
			'2026-10-01',                 // season_start_date – only used if season is new
			'2027-05-31',                 // season_end_date   – only used if season is new
			'Main Stage Theatre',         // venue_name – must match an existing venue
			'draft',                      // post_status
		] );
		fclose( $out ); // phpcs:ignore
		exit;
	}
}
