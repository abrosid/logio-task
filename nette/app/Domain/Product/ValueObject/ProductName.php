<?php declare(strict_types=1);

namespace App\Domain\Product\ValueObject;

class ProductName
{
	public function __construct(
		private readonly string $value
	) {
		if (trim($value) === '') {
			throw new \InvalidArgumentException('Product Name cannot be empty.');
		}
	}

	public function toString(): string
	{
		return $this->value;
	}
}
