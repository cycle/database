<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

use Cycle\Database\Tests\BaseTest;
use Spiral\Tokenizer;
use Symfony\Component\Finder\Finder;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$databases = [
    'sqlite' => [
        'namespace' => 'Cycle\Database\Tests\Driver\SQLite',
        'directory' => __DIR__ . '/Database/Driver/SQLite/',
    ],
    'mysql' => [
        'namespace' => 'Cycle\Database\Tests\Driver\MySQL',
        'directory' => __DIR__ . '/Database/Driver/MySQL/',
    ],
    'postgres' => [
        'namespace' => 'Cycle\Database\Tests\Driver\Postgres',
        'directory' => __DIR__ . '/Database/Driver/Postgres/',
    ],
    'sqlserver' => [
        'namespace' => 'Cycle\Database\Tests\Driver\SQLServer',
        'directory' => __DIR__ . '/Database/Driver/SQLServer/',
    ],
];

echo "Generating test classes for all database types...\n";

$classes = (new Tokenizer\ClassLocator(Finder::create()->in(__DIR__)->files()))
    ->getClasses(BaseTest::class);

foreach ($classes as $class) {
    if (!$class->isAbstract() || $class->getName() === BaseTest::class) {
        continue;
    }

    echo "Found {$class->getName()}\n";
    foreach ($databases as $driver => $details) {
        $filename = sprintf('%s/%s.php', $details['directory'], $class->getShortName());
        if (file_exists($filename)) {
            continue;
        }

        file_put_contents(
            $filename,
            sprintf(
                '<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace %s;

/**
 * @group driver
 * @group driver-%s
 */
class %s extends \\%s
{
    const DRIVER = "%s";
}',
                $details['namespace'],
                $driver,
                $class->getShortName(),
                $class->getName(),
                $driver
            )
        );
    }
}
