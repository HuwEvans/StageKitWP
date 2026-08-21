<?php
/**
 * Tests for STAGEKITWP_IMPORT_EXPORT_CSV_Importer.
 *
 * @package STAGEKITWP_IO
 */

class Test_STAGEKITWP_IMPORT_EXPORT_CSV_Importer extends WP_UnitTestCase {

	private STAGEKITWP_IMPORT_EXPORT_CSV_Importer $importer;

	public function set_up(): void {
		parent::set_up();
		$this->importer = new STAGEKITWP_IMPORT_EXPORT_CSV_Importer();
	}

	// ── template_headers() ───────────────────────────────────────────────────

	public function test_template_headers_contains_show_title(): void {
		$this->assertContains( 'show_title', STAGEKITWP_IMPORT_EXPORT_CSV_Importer::template_headers() );
	}

	public function test_template_headers_contains_season_title(): void {
		$this->assertContains( 'season_title', STAGEKITWP_IMPORT_EXPORT_CSV_Importer::template_headers() );
	}

	// ── Missing file ──────────────────────────────────────────────────────────

	public function test_import_missing_file_returns_wp_error(): void {
		$result = $this->importer->import( '/nonexistent/path/file.csv' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stagekitwp_import_export_csv_missing', $result->get_error_code() );
	}

	// ── Missing required column ───────────────────────────────────────────────

	public function test_import_missing_show_title_column_returns_wp_error(): void {
		$csv  = $this->write_temp_csv( "author,genre\nArthur Miller,Drama\n" );
		$result = $this->importer->import( $csv );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stagekitwp_import_export_csv_no_title', $result->get_error_code() );
		unlink( $csv );
	}

	// ── Empty file ────────────────────────────────────────────────────────────

	public function test_import_empty_csv_returns_wp_error(): void {
		$csv    = $this->write_temp_csv( "show_title,author\n" );
		$result = $this->importer->import( $csv );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stagekitwp_import_export_csv_empty', $result->get_error_code() );
		unlink( $csv );
	}

	// ── Dry run ───────────────────────────────────────────────────────────────

	public function test_dry_run_does_not_create_posts(): void {
		$csv = $this->write_temp_csv(
			"show_title,genre\n" .
			"\"Dry Run Show\",Drama\n"
		);
		$result = $this->importer->import( $csv, [ 'dry_run' => true ] );
		unlink( $csv );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['dry_run'] );

		// No post should have been created.
		$posts = get_posts( [ 'post_type' => 'show', 'title' => 'Dry Run Show', 'fields' => 'ids' ] );
		$this->assertEmpty( $posts, 'dry_run should not create any posts' );
	}

	public function test_dry_run_returns_preview_rows(): void {
		$csv = $this->write_temp_csv(
			"show_title,genre\n" .
			"\"Preview Show A\",Comedy\n" .
			"\"Preview Show B\",Drama\n"
		);
		$result = $this->importer->import( $csv, [ 'dry_run' => true ] );
		unlink( $csv );

		$this->assertCount( 2, $result['previews'] );
		$this->assertSame( 'create', $result['previews'][0]['action'] );
	}

	// ── Live import ───────────────────────────────────────────────────────────

	public function test_import_creates_show_post(): void {
		$csv = $this->write_temp_csv(
			"show_title,author,genre,director,time_slot\n" .
			"\"Test Season Show\",\"John Doe\",Drama,\"Jane Smith\",Fall\n"
		);
		$result = $this->importer->import( $csv, [ 'conflict' => 'skip' ] );
		unlink( $csv );

		$this->assertSame( 0, count( $result['errors'] ), implode( ', ', $result['errors'] ) );
		$this->assertSame( 1, $result['imported'] );

		$posts = get_posts( [
			'post_type'   => 'show',
			'post_status' => 'any',
			'title'       => 'Test Season Show',
			'fields'      => 'ids',
		] );
		$this->assertCount( 1, $posts );
		$post_id = $posts[0];
		$this->assertSame( 'John Doe',   get_post_meta( $post_id, '_stagekitwp_show_author',   true ) );
		$this->assertSame( 'Drama',      get_post_meta( $post_id, '_stagekitwp_show_genre',    true ) );
		$this->assertSame( 'Jane Smith', get_post_meta( $post_id, '_stagekitwp_show_director', true ) );
		$this->assertSame( 'Fall',       get_post_meta( $post_id, '_stagekitwp_show_time_slot', true ) );
	}

	public function test_conflict_skip_does_not_update(): void {
		// Pre-create the show.
		$existing_id = wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Conflict Show', 'post_status' => 'draft' ] );
		update_post_meta( $existing_id, '_stagekitwp_show_genre', 'OriginalGenre' );

		$csv = $this->write_temp_csv( "show_title,genre\n\"Conflict Show\",NewGenre\n" );
		$result = $this->importer->import( $csv, [ 'conflict' => 'skip' ] );
		unlink( $csv );

		$this->assertSame( 1, $result['skipped'] );
		$this->assertSame( 'OriginalGenre', get_post_meta( $existing_id, '_stagekitwp_show_genre', true ) );
	}

	public function test_conflict_overwrite_updates_meta(): void {
		$existing_id = wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Overwrite Show', 'post_status' => 'draft' ] );
		update_post_meta( $existing_id, '_stagekitwp_show_genre', 'OldGenre' );

		$csv = $this->write_temp_csv( "show_title,genre\n\"Overwrite Show\",NewGenre\n" );
		$result = $this->importer->import( $csv, [ 'conflict' => 'overwrite' ] );
		unlink( $csv );

		$this->assertSame( 1, $result['updated'] );
		$this->assertSame( 'NewGenre', get_post_meta( $existing_id, '_stagekitwp_show_genre', true ) );
	}

	public function test_forced_season_id_applied_to_all_rows(): void {
		// Create a season.
		$season_id = wp_insert_post( [ 'post_type' => 'season', 'post_title' => 'Test Season', 'post_status' => 'publish' ] );

		$csv = $this->write_temp_csv( "show_title\n\"Season Show 1\"\n\"Season Show 2\"\n" );
		$result = $this->importer->import( $csv, [ 'conflict' => 'skip', 'season_id' => $season_id ] );
		unlink( $csv );

		$this->assertSame( 2, $result['imported'] );

		$posts = get_posts( [ 'post_type' => 'show', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ] );
		foreach ( $posts as $pid ) {
			if ( in_array( get_the_title( $pid ), [ 'Season Show 1', 'Season Show 2' ], true ) ) {
				$this->assertSame( $season_id, (int) get_post_meta( $pid, '_stagekitwp_show_season', true ) );
			}
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function write_temp_csv( string $content ): string {
		$path = tempnam( sys_get_temp_dir(), 'stagekitwp_import_export_test_' ) . '.csv';
		file_put_contents( $path, $content );
		return $path;
	}
}
