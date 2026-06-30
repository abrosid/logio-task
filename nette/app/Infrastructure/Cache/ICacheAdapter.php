<?php declare(strict_types=1);

namespace App\Infrastructure\Cache;

interface ICacheAdapter
{
	public  function get(string $key): array;
	public function set(string $key, array $value): void;
}