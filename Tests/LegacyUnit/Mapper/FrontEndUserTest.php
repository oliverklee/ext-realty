<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Mapper_FrontEndUserTest extends TestCase
{
    /**
     * @var tx_realty_Mapper_FrontEndUser
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->subject = new tx_realty_Mapper_FrontEndUser();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /////////////////////////////////////////
    // Tests concerning the basic functions
    /////////////////////////////////////////

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsFrontEndUserInstance()
    {
        $uid = $this->testingFramework->createFrontEndUser();

        self::assertInstanceOf(
            tx_realty_Model_FrontEndUser::class,
            $this->subject->find($uid)
        );
    }
}
