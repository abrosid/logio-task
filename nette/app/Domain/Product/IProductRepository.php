<?php declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Product\ValueObject\ProductId;

interface IProductRepository
{
	public function findById(ProductId $productId): ?Product;
}