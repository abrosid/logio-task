<?php declare(strict_types=1);

namespace App\Tests;

use App\Domain\Product\ProductService;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\IProductRepository;
use App\Domain\Tracking\TrackingRepository;
use App\Infrastructure\DB\IDBAdapter;
use App\Infrastructure\Cache\ICacheAdapter;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ProductServiceTest extends TestCase
{
	public function testGetProductDetail(): void
	{
		// Arrange
		$productRepositoryMock = new class implements IProductRepository {
			public string $requestedId = '';
			public function getProduct(string $id): array {
				$this->requestedId = $id;
				return [
					'id' => $id,
					'name' => 'Test Product',
				];
			}
		};

		$trackingRepositoryMock = new class extends TrackingRepository {
			public function __construct() {}
			public string $incrementedId = '';
			public function increment(string $id): int {
				$this->incrementedId = $id;
				return 99;
			}
		};

		$productService = new ProductService(
			$productRepositoryMock,
			$trackingRepositoryMock
		);

		// Act
		$result = $productService->getProductDetail('123');

		// Assert
		Assert::same('123', $productRepositoryMock->requestedId);
		Assert::same('123', $trackingRepositoryMock->incrementedId);
		Assert::same([
			'id' => '123',
			'name' => 'Test Product',
			'tracking_count' => 99,
		], $result);
	}
}

class ProductRepositoryTest extends TestCase
{
	public function testGetProductFromCacheHit(): void
	{
		// Arrange
		$storageAdapterMock = new class implements IDBAdapter {
			public function getByID(string $id): array {
				Assert::fail('Storage adapter should not be called on cache hit.');
				return [];
			}
		};

		$cacheAdapterMock = new class implements ICacheAdapter {
			public function get(string $key): array {
				return [
					'id' => $key,
					'name' => 'Cached Product',
				];
			}
			public function set(string $key, array $value): void {
				Assert::fail('Cache write should not happen on cache hit.');
			}
		};

		$repository = new ProductRepository($storageAdapterMock, $cacheAdapterMock);

		// Act
		$result = $repository->getProduct('cache-123');

		// Assert
		Assert::same([
			'id' => 'cache-123',
			'name' => 'Cached Product',
		], $result);
	}

	public function testGetProductFromCacheMiss(): void
	{
		// Arrange
		$storageAdapterMock = new class implements IDBAdapter {
			public string $requestedId = '';
			public function getByID(string $id): array {
				$this->requestedId = $id;
				return [
					'id' => $id,
					'name' => 'DB Product',
				];
			}
		};

		$cacheAdapterMock = new class implements ICacheAdapter {
			public string $savedKey = '';
			public array $savedValue = [];
			public function get(string $key): array {
				return [];
			}
			public function set(string $key, array $value): void {
				$this->savedKey = $key;
				$this->savedValue = $value;
			}
		};

		$repository = new ProductRepository($storageAdapterMock, $cacheAdapterMock);

		// Act
		$result = $repository->getProduct('db-456');

		// Assert
		Assert::same('db-456', $storageAdapterMock->requestedId);
		Assert::same('db-456', $cacheAdapterMock->savedKey);
		Assert::same([
			'id' => 'db-456',
			'name' => 'DB Product',
		], $cacheAdapterMock->savedValue);
		Assert::same([
			'id' => 'db-456',
			'name' => 'DB Product',
		], $result);
	}
}

(new ProductServiceTest())->run();
(new ProductRepositoryTest())->run();
