<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$bootstrap = new App\Bootstrap;
$container = $bootstrap->bootWebApplication();
$latte = $container->getByType(Nette\Bridges\ApplicationLatte\TemplateFactory::class)
	->createTemplate()
	->getLatte();

$latte->addExtension(new Latte\Tools\LinterExtension);

$linter = new Latte\Tools\Linter($latte, strict: true);
$ok = $linter->scanDirectory('app/Presentation/Home');
exit($ok ? 0 : 1);
