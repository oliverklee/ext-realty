<?php

namespace OliverKlee\Realty\Tests\Functional;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class HelloWorldTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/realty'];

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function timeSpaceContinuumWorksFine()
    {
        static::assertSame(2, 1 + 1);
    }
}
