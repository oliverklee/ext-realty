<?php

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Model_DocumentTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Model_Document
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new tx_realty_Model_Document();
    }

    /*
     * Tests concerning the title
     */

    /**
     * @test
     */
    public function getTitleReturnsTitle()
    {
        $this->subject->setData(['title' => 'Just another document']);

        self::assertEquals(
            'Just another document',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setTitle('Just another document');

        self::assertEquals(
            'Just another document',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setTitleForEmptyTitleThrowsException()
    {
        $this->subject->setTitle('');
    }

    ////////////////////////////////////////////
    // Tests concerning the document file name
    ////////////////////////////////////////////

    /**
     * @test
     */
    public function getFileNameReturnsDocumentFileName()
    {
        $this->subject->setData(['filename' => 'foo.pdf']);

        self::assertEquals(
            'foo.pdf',
            $this->subject->getFileName()
        );
    }

    /**
     * @test
     */
    public function setFileNameSetsFileName()
    {
        $this->subject->setFileName('bar.pdf');

        self::assertEquals(
            'bar.pdf',
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
