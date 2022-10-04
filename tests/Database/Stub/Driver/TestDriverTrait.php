<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Exception\StatementException\ConnectionException;
use Exception;
use PDO;
use PDOStatement;

trait TestDriverTrait
{
    public int $disconnectCalls = 0;
    public int $exceptionOnTransactionBegin = 0;

    protected function getPDO(): PDOInterface
    {
        $pdo = parent::getPDO();

        return new TestPDO($pdo, $this->exceptionOnTransactionBegin);
    }

    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        if ($exception instanceof ConnectionException) {
            return $exception;
        }
        return parent::mapException($exception, $query);
    }

    public function disconnect(): void
    {
        ++$this->disconnectCalls;
        parent::disconnect();
    }

    public function setDefaults(): void
    {
        $this->disconnectCalls = 0;
        $this->exceptionOnTransactionBegin = 0;
    }
}
