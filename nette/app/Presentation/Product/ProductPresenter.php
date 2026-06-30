<?php declare(strict_types=1);

namespace App\Presentation\Product;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\Product\UseCase\GetProductDetailUseCase;
use App\Domain\Product\UseCase\GetProductTrackingUseCase;
use Nette;

final class ProductPresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private readonly GetProductDetailUseCase $getProductDetailUseCase,
		private readonly GetProductTrackingUseCase $getProductTrackingUseCase,
	) {
		parent::__construct();
	}

	public function actionDetail(string $id): void
	{
		$productId = new ProductId($id);
		$data = $this->getProductDetailUseCase->execute($productId);

		if ($data === null) {
			$this->error('Product not found', Nette\Http\IResponse::S404_NotFound);
		}

		$this->sendJson($data);
	}

	public function actionTracking(string $id): void
	{
		$productId = new ProductId($id);
		$data = $this->getProductTrackingUseCase->execute($productId);

		$this->sendJson($data);
	}
}
