<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Cycle\Database\Driver\CompilerInterface;

/**
 * Default implementation of SQLFragmentInterface, provides ability to inject custom SQL code into
 * query builders. Usually used to mock database specific functions.
 *
 * Example: ...->where('time_created', '>', new SQLFragment("NOW()"));
 */
class Fragment implements FragmentInterface, \Stringable
{
    private string $fragment;

    /** @var ParameterInterface[] */
    private array $parameters = [];

    /**
     * @psalm-param non-empty-string $fragment
     */
    public function __construct(string $fragment, mixed ...$parameters)
    {
        $this->fragment = $fragment;
        foreach ($parameters as $parameter) {
            if ($parameter instanceof ParameterInterface) {
                $this->parameters[] = $parameter;
            } else {
                $this->parameters[] = new Parameter($parameter);
            }
        }
    }

    public function getType(): int
    {
        return CompilerInterface::FRAGMENT;
    }

    public function getTokens(): array
    {
        return [
            'fragment'   => $this->fragment,
            'parameters' => $this->parameters,
        ];
    }

    public function __toString(): string
    {
        return $this->fragment;
    }

    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['fragment'] ?? $an_array['statement'],
            ...($an_array['parameters'] ?? []),
        );
    }
}
