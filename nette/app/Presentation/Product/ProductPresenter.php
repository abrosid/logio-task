<?php declare(strict_types=1);

namespace App\Presentation\Product;

use App\Domain\Product\ProductService;
use Nette;
use Nette\DI\Attributes\Inject;


final class ProductPresenter extends Nette\Application\UI\Presenter
{
	#[Inject]
	public ProductService $productService;

	public function actionDetail(string $id): void
	{
		$data = $this->productService->getProductDetail($id);

		$this->sendJson($data);
	}

	public function actionTracking(string $id): void
	{
		$data = $this->productService->getProductTrackingData($id);

		$this->sendJson($data);
	}
}
