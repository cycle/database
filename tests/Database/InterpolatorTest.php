<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\Interpolator;

class InterpolatorTest extends TestCase
{
    public function testInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = ? AND id IN(?, ?, ?) AND balance > ?';

        $parameters = [
            new Parameter('Anton'),
            1,
            2,
            3,
            new Parameter(120),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND id IN(1, 2, 3) AND balance > 120',
            $interpolated
        );
    }

    public function testDatesInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name'       => new Parameter('Anton'),
            ':registered' => new Parameter($date = new \DateTime('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \''
            . $date->format(\DateTime::ATOM) . '\'',
            $interpolated
        );
    }

    public function testDateInterpolationWithDateTimeImmutable(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name'       => new Parameter('Anton'),
            ':registered' => new Parameter($date = new \DateTimeImmutable('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \''
            . $date->format(\DateTime::ATOM)
            . '\'',
            $interpolated
        );
    }
}
