<?php

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_ImageTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Model_Image
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new tx_realty_Model_Image();
    }

    /*
     * Tests concerning the title
     */

    /**
     * @test
     */
    public function getTitleReturnsCaption()
    {
        $this->subject->setData(['caption' => 'Just another room']);

        self::assertEquals(
            'Just another room',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setTitle('Just another room');

        self::assertEquals(
            'Just another room',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleForEmptyTitleSetsEmptyTitle()
    {
        $this->subject->setTitle('');

        self::assertEquals(
            '',
            $this->subject->getTitle()
        );
    }

    /////////////////////////////////////////
    // Tests concerning the image file name
    /////////////////////////////////////////

    /**
     * @test
     */
    public function getFileNameReturnsImageFileName()
    {
        $this->subject->setData(['image' => 'foo.jpg']);

        self::assertEquals(
            'foo.jpg',
            $this->subject->getFileName()
        );
    }

    /**
     * @test
     */
    public function setFileNameSetsFileName()
    {
        $this->subject->setFileName('bar.jpg');

        self::assertEquals(
            'bar.jpg',
            $this->subject->getFileName()
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function setFileNameForEmptyFileNameThrowsException()
    {
        $this->subject->setFileName('');
    }

    ///////////////////////////////////////////////////////
    // Tests concerning the relation to the realty object
    ///////////////////////////////////////////////////////

    /**
     * @test
     */
    public function getObjectReturnsObject()
    {
        $realtyObject = new tx_realty_Model_RealtyObject();
        $this->subject->setData(['object' => $realtyObject]);

        self::assertSame(
            $realtyObject,
            $this->subject->getObject()
        );
    }

    /**
     * @test
     */
    public function setObjectSetsObject()
    {
        $realtyObject = new tx_realty_Model_RealtyObject();
        $this->subject->setObject($realtyObject);

        self::assertSame(
            $realtyObject,
            $this->subject->getObject()
        );
    }

    /////////////////////////////////
    // Tests concerning the sorting
    /////////////////////////////////

    /**
     * @test
     */
    public function getSortingInitiallyReturnsZero()
    {
        $this->subject->setData([]);

        self::assertEquals(
            0,
            $this->subject->getSorting()
        );
    }

    /**
     * @test
     */
    public function getSortingReturnsSorting()
    {
        $this->subject->setData(['sorting' => 42]);

        self::assertEquals(
            42,
            $this->subject->getSorting()
        );
    }

    /**
     * @test
     */
    public function setSortingSetsSorting()
    {
        $this->subject->setSorting(21);

        self::assertEquals(
            21,
            $this->subject->getSorting()
        );
    }
}
