<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\MySQL;

use Cycle\Database\Config\PDOConnectionInfo;
use Cycle\Database\Config\ProvidesSourceString;

/**
 * @psalm-import-type PDOFlag from PDOConnectionInfo
 */
abstract class MySQLPDOConnectionInfo extends PDOConnectionInfo implements ProvidesSourceString
{
    /**
     * General driver specific PDO options.
     *
     * @var array<PDOFlag, mixed>
     */
    protected const DEFAULT_PDO_OPTIONS = [
        \PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        // TODO Should be moved into common driver settings.
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        \PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    /**
     * @param non-empty-string $database
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     * @param non-empty-string|null $charset
     * @param array<non-empty-string|int, non-empty-string> $options
     */
    public function __construct(
        public string $database,
        public ?string $charset = null,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        parent::__construct($user, $password, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceString(): string
    {
        return $this->database;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'mysql';
    }
}
