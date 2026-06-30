<?php declare(strict_types=1);

namespace App\Domain\Tracking;

use App\Infrastructure\Cache\ICacheAdapter;
use App\Infrastructure\Cache\ICacheRepository;

class TrackingRepository implements ICacheRepository
{
	private const CACHE_PREFIX = 'tracking_';
	public function __construct(
		private readonly ICacheAdapter $cacheAdapter,
	)
	{
	}

	public function getCacheKey(string $id): string
	{
		return self::CACHE_PREFIX . $id;
	}
	public function increment(string $id): int
	{
		$key = $this->getCacheKey($id);

		$data = $this->getById($id);
		$count = $data[$id] ?? 0;
		$count++;
		$this->cacheAdapter->set($key, [$id => $count]);

		return $count;
	}

	public function getById(string $id): array
	{
		return $this->cacheAdapter->get($this->getCacheKey($id)) ?? [$id => 0];
	}
}