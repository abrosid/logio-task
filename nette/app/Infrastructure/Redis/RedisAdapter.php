<?php declare(strict_types=1);

namespace App\Infrastructure\Redis;

use App\Infrastructure\Cache\ICacheAdapter;

class RedisAdapter implements ICacheAdapter
{
	public function __construct(
		private readonly IRedisDriver $redisDriver
	)
	{
	}

	public function get(string $key): ?array
	{
		return $this->redisDriver->get($key);
	}

	public function set(string $key, array $value): void
	{
		$this->redisDriver->set($key, $value);
	}
}