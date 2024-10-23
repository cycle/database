<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

abstract class ConnectionConfig
{
    use RestoreStateTrait;

    /**
     * @var array<non-empty-string>
     */
    protected array $nonPrintableOptions = [
        // Postgres and MySQL
        'password',
        // IBM, ODBC and DB2
        'PWD',
    ];

    /**
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     */
    public function __construct(
        public ?string $user = null,
        public ?string $password = null,
    ) {}

    /**
     * @return non-empty-string|null
     */
    public function getUsername(): ?string
    {
        return $this->user;
    }

    /**
     * @return non-empty-string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    protected function toArray(bool $secure = true): array
    {
        $options = \get_object_vars($this);

        foreach ($options as $key => $value) {
            if ($secure && \in_array($key, $this->nonPrintableOptions, true)) {
                $value = '<hidden>';
            }

            $options[$key] = $value;
        }

        return $options;
    }
}
