<?php

namespace App\Domain\Product;

use App\Infrastructure\Cache\ICacheAdapter;
use App\Infrastructure\Cache\ICacheRepository;
use App\Infrastructure\DB\DBRepository;
use App\Infrastructure\DB\IDBAdapter;

class ProductRepository extends DBRepository implements IProductRepository, ICacheRepository
{
	private const CACHE_PREFIX = 'product_';
	public function __construct(
		IDBAdapter $storageAdapter,
		private readonly ICacheAdapter $cacheAdapter
	)
	{
		parent::__construct($storageAdapter);
	}

	public function getCacheKey(string $id): string
	{
		return self::CACHE_PREFIX . $id;
	}

	public function getProduct(string $id): array
	{
		$cacheKey = $this->getCacheKey($id);
		$data = $this->cacheAdapter->get($cacheKey);
		if ($data === []) {
			$data = $this->storageAdapter->getByID($id);
			$this->cacheAdapter->set($cacheKey, $data);
		}

		return $data;
	}
}