<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\Postgres;

use Cycle\Database\Config\ProvidesSourceString;
use Cycle\Database\Config\Support\DataSourceName;

class DsnConnectionConfig extends ConnectionConfig implements ProvidesSourceString
{
    /**
     * @var string|null
     */
    private ?string $database = null;

    /**
     * @var non-empty-string
     *
     * @psalm-allow-private-mutation
     */
    public string $dsn;

    /**
     * @param non-empty-string|\Stringable $dsn
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     * @param array $options
     */
    public function __construct(
        string|\Stringable $dsn,
        ?string $user = null,
        ?string $password = null,
        array $options = []
    ) {
        parent::__construct($user, $password, $options);

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->dsn = DataSourceName::normalize((string)$dsn, $this->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceString(): string
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $this->database ??= DataSourceName::read($this->getDsn(), 'dbname') ?? '*';
    }

    /**
     * {@inheritDoc}
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }
}
