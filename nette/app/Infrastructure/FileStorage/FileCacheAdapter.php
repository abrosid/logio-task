<?php

namespace App\Infrastructure\FileStorage;

use App\Infrastructure\Cache\ICacheAdapter;
use Nette\Caching\Storages\FileStorage;

class FileCacheAdapter implements ICacheAdapter
{
	public function __construct(
		private readonly FileStorage  $fileStorage,
	)
	{
	}

	public function get(string $key): ?array
	{
		$data = $this->fileStorage->read($key);
		if ($data === null) {
			return null;
		}

		return json_decode($data, true);
	}

	public function set(string $key, array $value): void
	{
		$this->fileStorage->write($key, json_encode($value), []);
	}
}