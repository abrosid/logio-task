<?php declare(strict_types=1);

namespace App\Infrastructure\DB;

interface IDBAdapter
{
	public function getByID(string $id): array;
}