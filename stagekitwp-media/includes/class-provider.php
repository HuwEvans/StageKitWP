<?php
namespace SKWPM;

interface Provider {
	public function slug(): string;
	public function label(): string;
	public function is_configured(): bool;

	/** @return array[] Normalized items */
	public function search( string $query, array $args = [] ): array;

	/** @return array|null Normalized item */
	public function fetch( string $external_id ):?array;
}