<?php
namespace SKWPM;

final class Registry {
	private static array $providers = [];

	public static function register( Provider $provider ): void {
 self::$providers[ $provider->slug() ] = $provider;
	}

	public static function get( string $slug ): ?Provider {
 return self::$providers[ $slug ] ?? null;
	}

	/** @return Provider[] */
	public static function all(): array {
 return self::$providers;
	}
}