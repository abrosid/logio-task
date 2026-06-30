<?php declare(strict_types=1);

namespace App\Domain\Tracking;

use App\Domain\Product\ValueObject\ProductId;

class ProductTracking
{
	public function __construct(
		private readonly ProductId $productId,
		private int $count = 0
	) {}

	public function getProductId(): ProductId
	{
		return $this->productId;
	}

	public function getCount(): int
	{
		return $this->count;
	}

	public function increment(): void
	{
		$this->count++;
	}

	public function toArray(): array
	{
		return [
			$this->productId->toString() => $this->count
		];
	}
}
