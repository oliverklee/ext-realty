<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_CityTest extends TestCase
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
     */
    public function setTitleWithEmptyStringThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->setTitle('');
    }
}
