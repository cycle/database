<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\PDOConnectionConfig;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

/**
 * The DSN builder that custom connection configs inherit.
 */
#[Test]
final class PDOConnectionConfigTest
{
    public function dsnJoinsPositionalAndNamedFieldsSkippingNulls(): void
    {
        $config = self::config(['memory', 'mode' => 'ro', 'cache' => null, 'shared' => true, 'wal' => false, 'size' => 64]);

        Assert::same($config->getDsn(), 'custom:memory;mode=ro;shared=1;wal=0;size=64');
    }

    public function dsnAcceptsStringableValues(): void
    {
        $host = new class implements \Stringable {
            public function __toString(): string
            {
                return 'db.local';
            }
        };

        Assert::same(self::config(['host' => $host])->getDsn(), 'custom:host=db.local');
    }

    public function dsnRejectsValuesWithoutStringForm(): never
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage('Can not convert config value of type "array" to string');

        self::config(['hosts' => ['a', 'b']])->getDsn();
    }

    public function debugInfoHidesThePassword(): void
    {
        $config = self::config([], user: 'root', password: 'secret');

        $info = $config->__debugInfo();

        Assert::same($info['user'], 'root');
        Assert::same($info['password'], '<hidden>');
        Assert::string(\print_r($config, true))->notContains('secret');
    }

    private static function config(array $fields, ?string $user = null, ?string $password = null): PDOConnectionConfig
    {
        return new class($fields, $user, $password) extends PDOConnectionConfig {
            public function __construct(
                private readonly array $fields,
                ?string $user,
                ?string $password,
            ) {
                parent::__construct($user, $password);
            }

            public function getName(): string
            {
                return 'custom';
            }

            public function getDsn(): string
            {
                return \sprintf('%s:%s', $this->getName(), $this->dsn($this->fields));
            }

            public function getSourceString(): string
            {
                return 'custom';
            }
        };
    }
}
