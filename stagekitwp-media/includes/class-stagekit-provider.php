<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Provider implementation for locally managed StageKit Media items.
 */
class StageKitProvider implements Provider {

	public function slug(): string {
		return 'stagekit-media';
	}

	public function label(): string {
		return __( 'StageKit Media Library', 'stagekitwp-media' );
	}

	public function is_configured(): bool {
		return true;
	}

	public function search( string $query, array $args = [] ): array {
		$query_args = array_merge( [
			'search'   => $query,
			'per_page' => 40,
		], $args );

		$result = MediaRepository::query( $query_args );
		$items  = [];

		foreach ( $result['items'] as $m ) {
			$items[] = [
				'provider'    => $this->slug(),
				'external_id' => (string) $m['id'],
				'type'        => $m['type'] ?? 'image',
				'url'         => $m['file_url'],
				'thumb'       => $m['thumb_url'] ?: $m['file_url'],
				'title'       => $m['title'] ?: $m['filename'],
				'author'      => $m['author'] ?? '',
				'license'     => $m['license'] ?? '',
			];
		}

		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$m = MediaRepository::get( (int) $external_id );
		if ( ! $m ) {
			return null;
		}

		return [
			'provider'    => $this->slug(),
			'external_id' => (string) $m['id'],
			'type'        => $m['type'] ?? 'image',
			'url'         => $m['file_url'],
			'thumb'       => $m['thumb_url'] ?: $m['file_url'],
			'title'       => $m['title'] ?: $m['filename'],
			'author'      => $m['author'] ?? '',
			'license'     => $m['license'] ?? '',
		];
	}
}
