<?php declare(strict_types=1);

namespace App\Infrastructure\Redis\Mock;

/**
 * This is a very "dummy" replacement for Redis. It is used for demonstration/testing purposes only.
 */
class RedisDriver
{
	private static array $cache;

	public function __construct()
	{
		self::$cache = [];
	}

	public function get(string $key): array
	{
		return self::$cache[$key] ?? [];
	}

	public function set(string $key, array $data): void
	{
		self::$cache[$key] = $data;
	}

}