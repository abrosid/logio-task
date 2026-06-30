<?php declare(strict_types=1);

namespace App\Infrastructure\DB;

class DBRepository
{
	public function __construct(
		protected readonly IDBAdapter $storageAdapter
	)
	{
	}
}