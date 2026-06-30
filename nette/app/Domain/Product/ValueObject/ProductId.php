<?php declare(strict_types=1);

namespace App\Domain\Product\ValueObject;

class ProductId
{
	public function __construct(
		private readonly string $value
	) {
		if (trim($value) === '') {
			throw new \InvalidArgumentException('Product ID cannot be empty.');
		}
	}

	public function toString(): string
	{
		return $this->value;
	}

	public function equals(ProductId $other): bool
	{
		return $this->value === $other->value;
	}
}
