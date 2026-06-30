<?php declare(strict_types=1);

namespace App\Infrastructure\MySQL;

use App\Infrastructure\DB\IDBAdapter;

class MySQLAdapter implements IDBAdapter
{
	public function __construct(
		private readonly IMySQLDriver $mySQLDriver
	)
	{
	}

	public function getByID(string $id): array
	{
		return $this->mySQLDriver->findProduct($id);
	}
}