<?php declare(strict_types=1);

namespace App\Infrastructure\Product;

use App\Domain\Product\IProductRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Product\ValueObject\ProductName;
use App\Infrastructure\Cache\ICacheAdapter;
use App\Infrastructure\Cache\ICacheRepository;
use App\Infrastructure\DB\DBRepository;
use App\Infrastructure\DB\IDBAdapter;

class ProductRepository extends DBRepository implements IProductRepository, ICacheRepository
{
	private const CACHE_PREFIX = 'product_';

	public function __construct(
		IDBAdapter $storageAdapter,
		private readonly ICacheAdapter $cacheAdapter,
	) {
		parent::__construct($storageAdapter);
	}

	public function getCacheKey(string $id): string
	{
		return self::CACHE_PREFIX . $id;
	}

	public function findById(ProductId $productId): ?Product
	{
		$idStr = $productId->toString();
		$cacheKey = $this->getCacheKey($idStr);
		$data = $this->cacheAdapter->get($cacheKey);

		if ($data === null) {
			$data = $this->storageAdapter->getByID($idStr);
			if ($data === []) {
				return null;
			}
			$this->cacheAdapter->set($cacheKey, $data);
		}

		return new Product(
			$productId,
			new ProductName($data['name'] ?? 'Product from Database')
		);
	}
}
