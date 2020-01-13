<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

use Spiral\Database\Tests\BaseTest;
use Spiral\Tokenizer;
use Symfony\Component\Finder\Finder;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$databases = [
    'sqlite'    => [
        'namespace' => 'Spiral\Database\Tests\Driver\SQLite',
        'directory' => __DIR__ . '/Database/Driver/SQLite/'
    ],
    'mysql'     => [
        'namespace' => 'Spiral\Database\Tests\Driver\MySQL',
        'directory' => __DIR__ . '/Database/Driver/MySQL/'
    ],
    'postgres'  => [
        'namespace' => 'Spiral\Database\Tests\Driver\Postgres',
        'directory' => __DIR__ . '/Database/Driver/Postgres/'
    ],
    'sqlserver' => [
        'namespace' => 'Spiral\Database\Tests\Driver\SQLServer',
        'directory' => __DIR__ . '/Database/Driver/SQLServer/'
    ]
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

class %s extends \%s
{
    const DRIVER = "%s";
}',
                $details['namespace'],
                $class->getShortName(),
                $class->getName(),
                $driver
            )
        );
    }
}
