<?php

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_CityTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Model_City
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new tx_realty_Model_City();
    }

    /**
     * @test
     */
    public function getTitleWithNonEmptyTitleReturnsTitle()
    {
        $this->subject->setData(['title' => 'London']);

        self::assertEquals(
            'London',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setTitle('London');

        self::assertEquals(
            'London',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setTitleWithEmptyStringThrowsException()
    {
        $this->subject->setTitle('');
    }
}
