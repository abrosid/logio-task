<?php declare(strict_types=1);

namespace App\Domain\Product\UseCase;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Tracking\ITrackingRepository;

class GetProductTrackingUseCase
{
	public function __construct(
		private readonly ITrackingRepository $trackingRepository,
	) {}

	public function execute(ProductId $productId): array
	{
		$tracking = $this->trackingRepository->findByProductId($productId);
		return $tracking->toArray();
	}
}
