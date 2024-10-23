<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLite;

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
     */
    public function __construct(
        string|\Stringable $dsn,
        array $options = [],
    ) {
        parent::__construct($options);

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->dsn = DataSourceName::normalize((string) $dsn, $this->getName());
    }

    public function getSourceString(): string
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $this->database ??= \substr($this->getDsn(), \strlen($this->getName()) + 1);
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }
}
