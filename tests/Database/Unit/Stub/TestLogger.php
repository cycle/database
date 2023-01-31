<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Stub;

use Psr\Log\LoggerInterface;

final class TestLogger implements LoggerInterface
{
    private \Stringable|string $message;

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->message = $message;
    }

    public function getMessage(): \Stringable|string
    {
        return $this->message;
    }
}
