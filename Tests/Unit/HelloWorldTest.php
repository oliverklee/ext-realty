<?php

namespace OliverKlee\Realty\Tests\Unit;

use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class HelloWorldTest extends UnitTestCase
{
    /**
     * @test
     */
    public function timeSpaceContinuumWorksFine()
    {
        static::assertSame(2, 1 + 1);
    }
}
