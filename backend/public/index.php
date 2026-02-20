<?php

declare(strict_types=1);

use App\App;
use App\Autoload;
use App\Env;

require __DIR__ . '/../src/Autoload.php';
require __DIR__ . '/../src/Env.php';

Autoload::register(__DIR__ . '/../src');
Env::load(dirname(__DIR__, 2));

$app = App::bootstrap(basePath: dirname(__DIR__));
$app->handle();

