<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\Interpolator;

class InterpolatorTest extends TestCase
{
    public function testFlattening()
    {
        $parameters = [
            ':named' => new Parameter('test'),
            ':value' => new Parameter('value')
        ];

        $flattened = iterator_to_array(Interpolator::flattenParameters($parameters));
        $this->assertCount(2, $flattened);

        $this->assertArrayHasKey(':named', $flattened);

        foreach ($flattened as $parameter) {
            $this->assertInstanceOf(ParameterInterface::class, $parameter);
        }

        $this->assertSame('test', $flattened[':named']->getValue());
        $this->assertSame('value', $flattened[':value']->getValue());
    }

    public function testFlatteningArray()
    {
        $parameters = [
            new Parameter('test'),
            new Parameter([1, 2, 3], \PDO::PARAM_INT)
        ];

        $flattened = iterator_to_array(Interpolator::flattenParameters($parameters));
        $this->assertCount(4, $flattened);

        foreach ($flattened as $parameter) {
            $this->assertInstanceOf(ParameterInterface::class, $parameter);
        }

        $this->assertSame('test', $flattened[1]->getValue());
        $this->assertSame(1, $flattened[2]->getValue());
        $this->assertSame(2, $flattened[3]->getValue());
        $this->assertSame(3, $flattened[4]->getValue());
    }

    public function testInterpolation()
    {
        $query = 'SELECT * FROM table WHERE name = ? AND id IN(?, ?, ?) AND balance > ?';

        $parameters = [
            new Parameter('Anton'),
            new Parameter([1, 2, 3], \PDO::PARAM_INT),
            new Parameter(120),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND id IN(1, 2, 3) AND balance > 120',
            $interpolated
        );
    }

    public function testDatesInterpolation()
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name'       => new Parameter('Anton'),
            ':registered' => new Parameter($date = new \DateTime('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \'' . $date->format(\DateTime::ISO8601) . '\'',
            $interpolated
        );
    }
}