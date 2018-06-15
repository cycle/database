<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Injections;

/**
 * Default implementation of SQLFragmentInterface, provides ability to inject custom SQL code into
 * query builders. Usually used to mock database specific functions.
 *
 * Example: ...->where('time_created', '>', new SQLFragment("NOW()"));
 */
class Fragment implements FragmentInterface
{
    /**
     * @var string
     */
    protected $statement = null;

    /**
     * @param string $statement
     */
    public function __construct(string $statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(): string
    {
        return $this->statement;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->sqlStatement();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['statement' => $this->sqlStatement()];
    }

    /**
     * @param array $an_array
     *
     * @return Fragment
     */
    public static function __set_state(array $an_array): Fragment
    {
        return new static($an_array['statement']);
    }
}
