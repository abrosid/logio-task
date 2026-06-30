<?php

namespace App\Domain\Product;

interface IProductRepository
{
	public function getProduct(string $id): array;
}