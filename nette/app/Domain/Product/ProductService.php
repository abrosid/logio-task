<?php

namespace App\Domain\Product;

use App\Domain\Tracking\TrackingRepository;

/**
 * this simple service demonstrates wrapping up the business logic into domain space
 * @property ProductRepository $productRepository
 * @property TrackingRepository $trackingRepository
 */
class ProductService
{
	public function __construct(
		private readonly IProductRepository $productRepository,
		private readonly TrackingRepository $trackingRepository
	)
	{
	}

	public function getProductDetail(string $id): array
	{
		$data = $this->productRepository->getProduct($id);

		$data['tracking_count'] = $this->trackingRepository->increment($id);

		return $data;
	}

	public function getProductTrackingData(string $id): array
	{
		return $this->trackingRepository->getById($id);
	}
}