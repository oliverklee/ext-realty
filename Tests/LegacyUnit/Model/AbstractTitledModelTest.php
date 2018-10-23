<?php

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_AbstractTitledModelTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Model_AbstractTitledModel
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getMockForAbstractClass(\tx_realty_Model_AbstractTitledModel::class);
    }

    /**
     * @test
     */
    public function classIsModel()
    {
        self::assertInstanceOf(Tx_Oelib_Model::class, $this->subject);
    }

    /**
     * @test
     */
    public function getTitleWithNonEmptyTitleReturnsTitle()
    {
        $title = 'God save McQueen!';
        $this->subject->setData(['title' => $title]);

        self::assertSame(
            $title,
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'The early bird needs coffee!';
        $this->subject->setTitle($title);

        self::assertSame(
            $title,
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
