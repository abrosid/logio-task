<?php declare(strict_types=1);

namespace App\Infrastructure\Redis;

interface IRedisDriver
{
	public function get(string $key): ?array;
	public function set(string $key, array $data): void;
}
