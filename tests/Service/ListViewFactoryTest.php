<?php

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Service_ListViewFactoryTest extends Tx_Phpunit_TestCase
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
        self::assertTrue(
            tx_realty_pi1_ListViewFactory::make(
                'realty_list',
                [],
                $this->cObjMock
            ) instanceof tx_realty_pi1_DefaultListView
        );
    }

    /**
     * @test
     */
    public function canCreateAFavoritesListViewInstance()
    {
        self::assertTrue(
            tx_realty_pi1_ListViewFactory::make(
                'favorites',
                [],
                $this->cObjMock
            ) instanceof tx_realty_pi1_FavoritesListView
        );
    }

    /**
     * @test
     */
    public function canCreateAMyObjectsListViewInstance()
    {
        self::assertTrue(
            tx_realty_pi1_ListViewFactory::make(
                'my_objects',
                [],
                $this->cObjMock
            ) instanceof tx_realty_pi1_MyObjectsListView
        );
    }

    /**
     * @test
     */
    public function canCreateAnObjectsByOwnerListViewInstance()
    {
        self::assertTrue(
            tx_realty_pi1_ListViewFactory::make(
                'objects_by_owner',
                [],
                $this->cObjMock
            ) instanceof tx_realty_pi1_ObjectsByOwnerListView
        );
    }

    /**
     * @test
     */
    public function throwsExceptionForInvalidViewType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The given list view type "foo" is invalid.'
        );

        tx_realty_pi1_ListViewFactory::make('foo', [], $this->cObjMock);
    }
}
