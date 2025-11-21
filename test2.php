<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(ProcessMaker\Console\Kernel::class)->bootstrap();
