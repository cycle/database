<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Injection;

use Spiral\Database\Driver\CompilerInterface;

/**
 * Default implementation of SQLFragmentInterface, provides ability to inject custom SQL code into
 * query builders. Usually used to mock database specific functions.
 *
 * Example: ...->where('time_created', '>', new SQLFragment("NOW()"));
 */
class Fragment implements FragmentInterface
{
    /** @var string */
    private $fragment;

    /** @var ParameterInterface[] */
    private $parameters = [];

    /**
     * @param string $fragment
     * @param mixed  ...$parameters
     */
    public function __construct(string $fragment, ...$parameters)
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fragment;
    }

    /**
     * @param array $an_array
     * @return Fragment
     */
    public static function __set_state(array $an_array): Fragment
    {
        return new self(
            $an_array['fragment'] ?? $an_array['statement'],
            ...($an_array['parameters'] ?? [])
        );
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return CompilerInterface::FRAGMENT;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'fragment'   => $this->fragment,
            'parameters' => $this->parameters
        ];
    }
}
