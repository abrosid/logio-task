<?php declare(strict_types=1);

namespace App\Infrastructure\MySQL;

interface IMySQLDriver
{
	public function findProduct(string $id): array;
}