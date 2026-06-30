<?php declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch;

interface IElasticSearchDriver
{
	public function findByID(string $id): array;
}