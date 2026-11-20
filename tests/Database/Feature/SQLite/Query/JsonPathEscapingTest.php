<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Query;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Feature\Common\Query\JsonPathEscapingTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;

#[OnlyDriver(Driver::SQLite)]
class JsonPathEscapingTest extends CommonClass
{
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
