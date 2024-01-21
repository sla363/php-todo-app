<?php

declare(strict_types=1);

use TodoApp\Kernel;

require __DIR__.'/../vendor/autoload.php';

main();

/**
 * @throws Exception
 */
function main(): void
{
    $kernel = new Kernel();
    $kernel->run();
}

