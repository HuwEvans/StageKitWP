<?php
/**
 * Tests for STAGEKITWP_IMPORT_EXPORT_Module conflict resolution (skip / overwrite / duplicate).
 *
 * Uses STAGEKITWP_IMPORT_EXPORT_Mod_StageKitWP_Core as the concrete implementation under test
 * since it owns the `show` CPT which is available on the site.
 *
 * @package STAGEKITWP_IO
 */

class Test_STAGEKITWP_IMPORT_EXPORT_Conflict_Resolution extends WP_UnitTestCase {

	private STAGEKITWP_IMPORT_EXPORT_Mod_StageKitWP_Core $module;

	public function set_up(): void {
		parent::set_up();
		$this->module = new STAGEKITWP_IMPORT_EXPORT_Mod_StageKitWP_Core();
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function make_show_payload( string $title, string $genre = 'Drama' ): array {
		$slug = sanitize_title( $title );
		return [
			'guid'          => 'http://source-site.test/?post_type=show&p=' . rand( 1000, 9999 ),
			'post_type'     => 'show',
			'post_status'   => 'publish',
			'post_title'    => $title,
			'post_name'     => $slug,
			'post_content'  => '',
			'post_excerpt'  => '',
			'post_date'     => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', 1 ),
			'menu_order'    => 0,
			'terms'         => [],
			'meta'          => [
				'_stagekitwp_show_genre'    => $genre,
				'_stagekitwp_show_director' => 'Test Director',
			],
		];
	}

	private function get_show_by_title( string $title ): ?WP_Post {
		$posts = get_posts( [
			'post_type'   => 'show',
			'post_status' => 'any',
			'title'       => $title,
			'numberposts' => 1,
		] );
		return $posts[0] ?? null;
	}

	// ── Skip ──────────────────────────────────────────────────────────────────

	public function test_skip_does_not_import_existing_post(): void {
		// Pre-create.
		wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Skip Show', 'post_name' => 'skip-show', 'post_status' => 'publish' ] );

		$result = $this->module->import(
			[ 'posts' => [ $this->make_show_payload( 'Skip Show' ) ], 'options' => [], 'extra' => [] ],
			[ 'conflict' => 'skip' ]
		);

		$this->assertSame( 0, $result['imported'] );
		$this->assertSame( 1, $result['skipped'] );

		// Only one show with this title should exist.
		$posts = get_posts( [ 'post_type' => 'show', 'post_status' => 'any', 'title' => 'Skip Show', 'fields' => 'ids' ] );
		$this->assertCount( 1, $posts );
	}

	// ── Overwrite ─────────────────────────────────────────────────────────────

	public function test_overwrite_updates_existing_post_meta(): void {
		$id = wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Overwrite Show', 'post_name' => 'overwrite-show', 'post_status' => 'publish' ] );
		update_post_meta( $id, '_stagekitwp_show_genre', 'OldGenre' );

		$payload = $this->make_show_payload( 'Overwrite Show', 'NewGenre' );

		$result = $this->module->import(
			[ 'posts' => [ $payload ], 'options' => [], 'extra' => [] ],
			[ 'conflict' => 'overwrite' ]
		);

		$this->assertSame( 1, $result['imported'] );
		$this->assertSame( 0, $result['skipped'] );
		$this->assertSame( 'NewGenre', get_post_meta( $id, '_stagekitwp_show_genre', true ) );
	}

	public function test_overwrite_does_not_create_duplicate(): void {
		wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Ow Dedup', 'post_name' => 'ow-dedup', 'post_status' => 'publish' ] );

		$this->module->import(
			[ 'posts' => [ $this->make_show_payload( 'Ow Dedup' ) ], 'options' => [], 'extra' => [] ],
			[ 'conflict' => 'overwrite' ]
		);

		$posts = get_posts( [ 'post_type' => 'show', 'post_status' => 'any', 'title' => 'Ow Dedup', 'fields' => 'ids' ] );
		$this->assertCount( 1, $posts, 'overwrite should not create a second post' );
	}

	// ── Duplicate ─────────────────────────────────────────────────────────────

	public function test_duplicate_creates_new_post(): void {
		wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Dup Show', 'post_name' => 'dup-show', 'post_status' => 'publish' ] );

		$this->module->import(
			[ 'posts' => [ $this->make_show_payload( 'Dup Show' ) ], 'options' => [], 'extra' => [] ],
			[ 'conflict' => 'duplicate' ]
		);

		$posts = get_posts( [ 'post_type' => 'show', 'post_status' => 'any', 'title' => 'Dup Show', 'fields' => 'ids' ] );
		$this->assertCount( 2, $posts, 'duplicate should create a second post' );
	}

	// ── New post (no existing) ────────────────────────────────────────────────

	public function test_import_new_post_all_strategies(): void {
		foreach ( [ 'skip', 'overwrite', 'duplicate' ] as $strategy ) {
			$title = "Brand New Show ({$strategy})";
			$result = $this->module->import(
				[ 'posts' => [ $this->make_show_payload( $title ) ], 'options' => [], 'extra' => [] ],
				[ 'conflict' => $strategy ]
			);

			$this->assertSame( 1, $result['imported'], "Strategy '{$strategy}' should import a brand-new post." );

			$show = $this->get_show_by_title( $title );
			$this->assertNotNull( $show );
			$this->assertSame( 'Drama', get_post_meta( $show->ID, '_stagekitwp_show_genre', true ) );
		}
	}

	// ── Options import ────────────────────────────────────────────────────────

	public function test_import_options_writes_to_wp_options(): void {
		$this->module->import(
			[ 'posts' => [], 'options' => [ 'stagekitwp_color_mode' => 'dark' ], 'extra' => [] ],
			[ 'conflict' => 'skip' ]
		);

		$this->assertSame( 'dark', get_option( 'stagekitwp_color_mode' ) );
	}

	// ── find_existing_post_public ─────────────────────────────────────────────

	public function test_find_existing_post_public_by_slug(): void {
		$id = wp_insert_post( [ 'post_type' => 'show', 'post_title' => 'Findable Show', 'post_name' => 'findable-show', 'post_status' => 'publish' ] );

		$found = $this->module->find_existing_post_public( [
			'post_name' => 'findable-show',
			'post_type' => 'show',
		] );

		$this->assertSame( $id, $found );
	}

	public function test_find_existing_post_public_returns_zero_for_missing(): void {
		$found = $this->module->find_existing_post_public( [
			'post_name' => 'does-not-exist-xyz',
			'post_type' => 'show',
		] );
		$this->assertSame( 0, $found );
	}
}
