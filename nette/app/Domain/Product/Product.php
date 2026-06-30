<?php declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Product\ValueObject\ProductName;

class Product
{
	public function __construct(
		private readonly ProductId $id,
		private readonly ProductName $name
	) {}

	public function getId(): ProductId
	{
		return $this->id;
	}

	public function getName(): ProductName
	{
		return $this->name;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id->toString(),
			'name' => $this->name->toString(),
		];
	}
}
