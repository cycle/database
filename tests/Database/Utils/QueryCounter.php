<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Utils;

use Psr\Log\AbstractLogger;

/**
 * Counts SQL statements executed by a driver.
 *
 * Only records produced for real statements are counted: driver emits them with the `elapsed` key
 * in the context, while transaction/savepoint messages carry no context at all.
 */
final class QueryCounter extends AbstractLogger
{
    /** @var list<string> */
    private array $queries = [];

    public function log($level, $message, array $context = []): void
    {
        if (!\array_key_exists('elapsed', $context)) {
            return;
        }

        $this->queries[] = (string) $message;
    }

    public function reset(): void
    {
        $this->queries = [];
    }

    public function count(): int
    {
        return \count($this->queries);
    }

    /**
     * @return list<string>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function __toString(): string
    {
        $result = [];
        foreach ($this->queries as $i => $query) {
            $result[] = \sprintf('%d. %s', $i + 1, \preg_replace('/\s+/', ' ', $query));
        }

        return \implode("\n", $result);
    }
}
