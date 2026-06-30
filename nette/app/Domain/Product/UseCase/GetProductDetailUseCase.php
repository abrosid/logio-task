<?php declare(strict_types=1);

namespace App\Domain\Product\UseCase;

use App\Domain\Product\IProductRepository;
use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Tracking\ITrackingRepository;

class GetProductDetailUseCase
{
	public function __construct(
		private readonly IProductRepository $productRepository,
		private readonly ITrackingRepository $trackingRepository,
	) {}

	public function execute(ProductId $productId): ?array
	{
		$product = $this->productRepository->findById($productId);
		if ($product === null) {
			return null;
		}

		$tracking = $this->trackingRepository->findByProductId($productId);
		$tracking->increment();
		$this->trackingRepository->save($tracking);

		return [
			'id' => $product->getId()->toString(),
			'name' => $product->getName()->toString(),
			'tracking_count' => $tracking->getCount(),
		];
	}
}
