<?php declare(strict_types=1);

namespace App\Infrastructure\Tracking;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Tracking\ITrackingRepository;
use App\Domain\Tracking\ProductTracking;
use App\Infrastructure\Cache\ICacheAdapter;
use App\Infrastructure\Cache\ICacheRepository;

class TrackingRepository implements ITrackingRepository, ICacheRepository
{
	private const CACHE_PREFIX = 'tracking_';

	public function __construct(
		private readonly ICacheAdapter $cacheAdapter,
	) {}

	public function getCacheKey(string $id): string
	{
		return self::CACHE_PREFIX . $id;
	}

	public function findByProductId(ProductId $productId): ProductTracking
	{
		$idStr = $productId->toString();
		$key = $this->getCacheKey($idStr);
		$data = $this->cacheAdapter->get($key);

		if ($data === null) {
			return new ProductTracking($productId, 0);
		}

		$count = $data[$idStr] ?? 0;
		return new ProductTracking($productId, $count);
	}

	public function save(ProductTracking $tracking): void
	{
		$idStr = $tracking->getProductId()->toString();
		$key = $this->getCacheKey($idStr);
		$this->cacheAdapter->set($key, [$idStr => $tracking->getCount()]);
	}
}
