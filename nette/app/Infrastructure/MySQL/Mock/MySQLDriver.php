<?php declare(strict_types=1);

namespace App\Infrastructure\MySQL\Mock;

use App\Infrastructure\MySQL\IMySQLDriver;

/**
 * This is a very "dummy" replacement for the MySQL driver. It is used for demonstration/testing purposes only.
 */
class MySQLDriver implements IMySQLDriver
{

	public function findProduct(string $id): array
	{
		return [
			'id' => $id,
			'name' => 'Product from MySQL',
		];
	}
}