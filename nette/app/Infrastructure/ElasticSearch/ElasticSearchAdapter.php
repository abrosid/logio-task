<?php declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch;

use App\Infrastructure\DB\IDBAdapter;

class ElasticSearchAdapter implements IDBAdapter
{
	public function __construct(
		private readonly IElasticSearchDriver $elasticSearchDriver
	)
	{
	}

	public function getByID(string $id): array
	{
		return $this->elasticSearchDriver->findByID($id);
	}
}