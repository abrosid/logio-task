<?php declare(strict_types=1);

namespace App\Infrastructure\Cache;


interface ICacheRepository
{
	public function getCacheKey(string $id): string;
}