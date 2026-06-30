<?php declare(strict_types=1);

namespace App\Domain\Tracking;

use App\Domain\Product\ValueObject\ProductId;

interface ITrackingRepository
{
	public function findByProductId(ProductId $productId): ProductTracking;
	public function save(ProductTracking $tracking): void;
}
