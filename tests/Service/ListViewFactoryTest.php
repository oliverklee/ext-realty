<?php

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Service_ListViewFactoryTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var ContentObjectRenderer|PHPUnit_Framework_MockObject_MockObject
     */
    private $cObjMock;

    protected function setUp()
    {
        $this->cObjMock = $this->getMock(ContentObjectRenderer::class);
    }

    /////////////////////////////////////////////
    // Tests concerning the basic functionality
    /////////////////////////////////////////////

    /**
     * @test
     */
    public function canCreateARealtyListViewInstance()
    {
        self::assertInstanceOf(
            tx_realty_pi1_DefaultListView::class,
            tx_realty_pi1_ListViewFactory::make(
                'realty_list',
                [],
                $this->cObjMock
            )
        );
    }

    /**
     * @test
     */
    public function canCreateAFavoritesListViewInstance()
    {
        self::assertInstanceOf(
            tx_realty_pi1_FavoritesListView::class,
            tx_realty_pi1_ListViewFactory::make(
                'favorites',
                [],
                $this->cObjMock
            )
        );
    }

    /**
     * @test
     */
    public function canCreateAMyObjectsListViewInstance()
    {
        self::assertInstanceOf(
            tx_realty_pi1_MyObjectsListView::class,
            tx_realty_pi1_ListViewFactory::make(
                'my_objects',
                [],
                $this->cObjMock
            )
        );
    }

    /**
     * @test
     */
    public function canCreateAnObjectsByOwnerListViewInstance()
    {
        self::assertInstanceOf(
            tx_realty_pi1_ObjectsByOwnerListView::class,
            tx_realty_pi1_ListViewFactory::make(
                'objects_by_owner',
                [],
                $this->cObjMock
            )
        );
    }

    /**
     * @test
     */
    public function throwsExceptionForInvalidViewType()
    {
        $this->expectException(\InvalidArgumentException::class);

        tx_realty_pi1_ListViewFactory::make('foo', [], $this->cObjMock);
    }
}
