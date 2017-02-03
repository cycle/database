<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database;

use Spiral\Database\Injections\FragmentInterface;

abstract class BaseQueryTest extends BaseTest
{
    /**
     * Send sample query in a form where all quotation symbols replaced with { and }.
     *
     * @param string                   $query
     * @param string|FragmentInterface $fragment
     */
    protected function assertSameQuery(string $query, $fragment)
    {
        //Preparing query
        $query = str_replace(
            ['{', '}'],
            explode('.', $this->database()->getDriver()->identifier('.')),
            $query
        );

        $this->assertSame(
            preg_replace('/\s+/', '', $query),
            preg_replace('/\s+/', '', (string)$fragment)
        );
    }
}