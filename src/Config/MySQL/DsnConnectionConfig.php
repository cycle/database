<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\MySQL;

use Cycle\Database\Config\ProvidesSourceString;
use Cycle\Database\Config\Support\DataSourceName;

class DsnConnectionConfig extends ConnectionConfig implements ProvidesSourceString
{
    /**
     * @var non-empty-string
     *
     * @psalm-allow-private-mutation
     */
    public string $dsn;

    private ?string $database = null;

    /**
     * @param non-empty-string|\Stringable $dsn
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     */
    public function __construct(
        string|\Stringable $dsn,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        parent::__construct($user, $password, $options);

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->dsn = DataSourceName::normalize((string) $dsn, $this->getName());
    }

    public function getSourceString(): string
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $this->database ??= DataSourceName::read($this->getDsn(), 'dbname') ?? '*';
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }
}
