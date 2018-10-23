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
    private $fixture = null;

    protected function setUp()
    {
        $this->fixture = new tx_realty_Model_City();
    }

    /**
     * @test
     */
    public function getTitleWithNonEmptyTitleReturnsTitle()
    {
        $this->fixture->setData(['title' => 'London']);

        self::assertEquals(
            'London',
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->fixture->setTitle('London');

        self::assertEquals(
            'London',
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setTitleWithEmptyStringThrowsException()
    {
        $this->fixture->setTitle('');
    }
}
