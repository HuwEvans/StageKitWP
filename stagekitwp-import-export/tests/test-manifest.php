<?php
/**
 * Tests for STAGEKITWP_IMPORT_EXPORT_Manifest.
 *
 * @package STAGEKITWP_IO
 */

class Test_STAGEKITWP_IMPORT_EXPORT_Manifest extends WP_UnitTestCase {

	// ── build() ───────────────────────────────────────────────────────────────

	public function test_build_returns_required_fields(): void {
		$payload = [
			'stagekitwp' => [
				'label'       => 'StageKitWP (core)',
				'exported_at' => gmdate( 'c' ),
				'posts'       => [ [ 'post_title' => 'Show A' ], [ 'post_title' => 'Show B' ] ],
				'options'     => [ 'stagekitwp_color_mode' => 'dark' ],
				'extra'       => [],
			],
		];

		$manifest = STAGEKITWP_IMPORT_EXPORT_Manifest::build( $payload );

		$this->assertArrayHasKey( 'schema_version', $manifest );
		$this->assertArrayHasKey( 'plugin_version',  $manifest );
		$this->assertArrayHasKey( 'wp_version',      $manifest );
		$this->assertArrayHasKey( 'exported_at',     $manifest );
		$this->assertArrayHasKey( 'total_posts',     $manifest );
		$this->assertArrayHasKey( 'modules',         $manifest );
	}

	public function test_build_counts_posts_correctly(): void {
		$payload = [
			'stagekitwp' => [
				'label'       => 'TM',
				'exported_at' => gmdate( 'c' ),
				'posts'       => array_fill( 0, 5, [ 'post_title' => 'Show' ] ),
				'options'     => [],
				'extra'       => [],
			],
			'stagekitwp-rc-library' => [
				'label'       => 'RC',
				'exported_at' => gmdate( 'c' ),
				'posts'       => array_fill( 0, 3, [ 'post_title' => 'Book' ] ),
				'options'     => [],
				'extra'       => [],
			],
		];

		$manifest = STAGEKITWP_IMPORT_EXPORT_Manifest::build( $payload );

		$this->assertSame( 8, $manifest['total_posts'] );
		$this->assertSame( 5, $manifest['modules']['stagekitwp']['post_count'] );
		$this->assertSame( 3, $manifest['modules']['stagekitwp-rc-library']['post_count'] );
	}

	// ── parse() ───────────────────────────────────────────────────────────────

	public function test_parse_valid_manifest(): void {
		$json = wp_json_encode( [
			'schema_version' => '1.0',
			'plugin_version' => '1.0.0',
			'total_posts'    => 0,
			'modules'        => [],
			'exported_at'    => gmdate( 'c' ),
		] );

		$result = STAGEKITWP_IMPORT_EXPORT_Manifest::parse( $json );

		$this->assertIsArray( $result );
		$this->assertSame( '1.0', $result['schema_version'] );
	}

	public function test_parse_invalid_json_returns_wp_error(): void {
		$result = STAGEKITWP_IMPORT_EXPORT_Manifest::parse( 'not json {{{' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stagekitwp_import_export_manifest_parse', $result->get_error_code() );
	}

	public function test_parse_missing_schema_version_returns_wp_error(): void {
		$result = STAGEKITWP_IMPORT_EXPORT_Manifest::parse( wp_json_encode( [ 'total_posts' => 0 ] ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stagekitwp_import_export_manifest_schema', $result->get_error_code() );
	}

	public function test_parse_newer_schema_returns_wp_error(): void {
		$result = STAGEKITWP_IMPORT_EXPORT_Manifest::parse( wp_json_encode( [
			'schema_version' => '99.0',
			'total_posts'    => 0,
		] ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stagekitwp_import_export_manifest_version', $result->get_error_code() );
	}

	// ── total_records() ───────────────────────────────────────────────────────

	public function test_total_records(): void {
		$manifest = [ 'total_posts' => 42 ];
		$this->assertSame( 42, STAGEKITWP_IMPORT_EXPORT_Manifest::total_records( $manifest ) );
	}

	public function test_total_records_missing_key_returns_zero(): void {
		$this->assertSame( 0, STAGEKITWP_IMPORT_EXPORT_Manifest::total_records( [] ) );
	}
}
