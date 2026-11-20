<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

use Cycle\Database\Exception\StatementException;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\JsonPathEscapingTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class JsonPathEscapingTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    /**
     * SQLite 3.46 finds no key with `"` or `\` in a quoted path member, escaped or not; 3.53 reads the escapes.
     * Older parsers may reject the escaped path outright.
     */
    protected function supportsEscapedPathKeys(): bool
    {
        try {
            return $this->database->query('SELECT json_extract(\'{"a\"b": 1}\', \'$."a\"b"\')')->fetchColumn() !== null;
        } catch (StatementException) {
            return false;
        }
    }
}
