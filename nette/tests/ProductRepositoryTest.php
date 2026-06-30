<?php declare(strict_types=1);

namespace App\Tests;

use App\Domain\Product\Product;
use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Product\ValueObject\ProductName;
use App\Domain\Product\IProductRepository;
use App\Domain\Tracking\ProductTracking;
use App\Domain\Tracking\ITrackingRepository;
use App\Domain\Product\UseCase\GetProductDetailUseCase;
use App\Domain\Product\UseCase\GetProductTrackingUseCase;
use App\Infrastructure\Product\ProductRepository;
use App\Infrastructure\Tracking\TrackingRepository;
use App\Infrastructure\DB\IDBAdapter;
use App\Infrastructure\Cache\ICacheAdapter;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class GetProductDetailUseCaseTest extends TestCase
{
	public function testExecuteDetail(): void
	{
		// Arrange
		$productRepositoryMock = new class implements IProductRepository {
			public string $requestedId = '';
			public function findById(ProductId $productId): ?Product {
				$this->requestedId = $productId->toString();
				return new Product(
					$productId,
					new ProductName('Test Product')
				);
			}
		};

		$trackingRepositoryMock = new class implements ITrackingRepository {
			public string $savedId = '';
			public int $savedCount = 0;
			public function findByProductId(ProductId $productId): ProductTracking {
				return new ProductTracking($productId, 5);
			}
			public function save(ProductTracking $tracking): void {
				$this->savedId = $tracking->getProductId()->toString();
				$this->savedCount = $tracking->getCount();
			}
		};

		$useCase = new GetProductDetailUseCase(
			$productRepositoryMock,
			$trackingRepositoryMock
		);

		// Act
		$result = $useCase->execute(new ProductId('123'));

		// Assert
		Assert::same('123', $productRepositoryMock->requestedId);
		Assert::same('123', $trackingRepositoryMock->savedId);
		Assert::same(6, $trackingRepositoryMock->savedCount);
		Assert::same([
			'id' => '123',
			'name' => 'Test Product',
			'tracking_count' => 6,
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
			public function get(string $key): ?array {
				return [
					'id' => 'cache-123',
					'name' => 'Cached Product',
				];
			}
			public function set(string $key, array $value): void {
				Assert::fail('Cache write should not happen on cache hit.');
			}
		};

		$repository = new ProductRepository($storageAdapterMock, $cacheAdapterMock);

		// Act
		$product = $repository->findById(new ProductId('cache-123'));

		// Assert
		Assert::notNull($product);
		Assert::same('cache-123', $product->getId()->toString());
		Assert::same('Cached Product', $product->getName()->toString());
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
			public function get(string $key): ?array {
				return null;
			}
			public function set(string $key, array $value): void {
				$this->savedKey = $key;
				$this->savedValue = $value;
			}
		};

		$repository = new ProductRepository($storageAdapterMock, $cacheAdapterMock);

		// Act
		$product = $repository->findById(new ProductId('db-456'));

		// Assert
		Assert::notNull($product);
		Assert::same('db-456', $storageAdapterMock->requestedId);
		Assert::same('product_db-456', $cacheAdapterMock->savedKey);
		Assert::same([
			'id' => 'db-456',
			'name' => 'DB Product',
		], $cacheAdapterMock->savedValue);
		Assert::same('db-456', $product->getId()->toString());
		Assert::same('DB Product', $product->getName()->toString());
	}
}

class TrackingRepositoryTest extends TestCase
{
	public function testGetAndSaveTracking(): void
	{
		// Arrange
		$cacheAdapterMock = new class implements ICacheAdapter {
			public array $storage = [];
			public function get(string $key): ?array {
				return $this->storage[$key] ?? null;
			}
			public function set(string $key, array $value): void {
				$this->storage[$key] = $value;
			}
		};

		$repository = new TrackingRepository($cacheAdapterMock);
		$productId = new ProductId('prod-789');

		// Act - first retrieve (should default to 0)
		$tracking = $repository->findByProductId($productId);
		Assert::same(0, $tracking->getCount());

		// Act - increment and save
		$tracking->increment();
		$repository->save($tracking);

		// Act - retrieve again
		$trackingLoaded = $repository->findByProductId($productId);

		// Assert
		Assert::same(1, $trackingLoaded->getCount());
	}
}

(new GetProductDetailUseCaseTest())->run();
(new ProductRepositoryTest())->run();
(new TrackingRepositoryTest())->run();
