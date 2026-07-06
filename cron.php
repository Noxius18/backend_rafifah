<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

try {
    /** @var Kernel $kernel */
    $kernel = $app->make(Kernel::class);
    $status = $kernel->call('schedule:run');
    $kernel->terminate(new \Symfony\Component\Console\Input\ArrayInput([]), $status);

    exit($status);
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf(
        "[%s] schedule:run failed: %s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    ));

    exit(1);
}
