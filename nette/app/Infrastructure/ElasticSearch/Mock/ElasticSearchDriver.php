<?php declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Mock;

use App\Infrastructure\ElasticSearch\IElasticSearchDriver;

/**
 * This is a very "dummy" replacement for the ElasticSearch driver. It is used for demonstration/testing purposes only.
 */
class ElasticSearchDriver implements IElasticSearchDriver
{

	public function findByID(string $id): array
	{
		return [
			'id' => $id,
			'name' => 'Product from ElasticSearch',
		];
	}
}