<?php declare(strict_types=1);

namespace App\Infrastructure\Redis\Mock;

use App\Infrastructure\Redis\IRedisDriver;

/**
 * This is a very "dummy" replacement for Redis. It is used for demonstration/testing purposes only.
 */
class RedisDriver implements IRedisDriver
{
	private static array $cache;

	public function __construct()
	{
		self::$cache = [];
	}

	public function get(string $key): ?array
	{
		return self::$cache[$key] ?? null;
	}

	public function set(string $key, array $data): void
	{
		self::$cache[$key] = $data;
	}

}