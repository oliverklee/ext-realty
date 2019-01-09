<?php

namespace OliverKlee\Realty\Tests\Unit\Model;

use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class RealtyObjectTest extends UnitTestCase
{
    /**
     * @var \tx_realty_Model_RealtyObject
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new \tx_realty_Model_RealtyObject(true);
    }

    /**
     * @test
     */
    public function isAbstractModel()
    {
        static::assertInstanceOf(\Tx_Oelib_Model::class, $this->subject);
    }
}
