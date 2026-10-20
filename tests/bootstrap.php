<?php

declare(strict_types=1);

use App\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

Bootstrap::init(dirname(__DIR__));
Bootstrap::prepareDatabase();
