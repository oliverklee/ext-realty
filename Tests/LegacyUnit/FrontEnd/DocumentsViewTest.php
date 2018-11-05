<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_DocumentsViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_DocumentsView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_Mapper_RealtyObject
     */
    private $realtyObjectMapper = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->realtyObjectMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->fixture = new tx_realty_pi1_DocumentsView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////////////////
    // Tests for the basic functions
    //////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $realtyObject->addDocument('new document', 'readme.pdf');

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertNotContains(
            '###',
            $result
        );
    }

    ////////////////////////////////
    // Tests for the render result
    ////////////////////////////////

    /**
     * @test
     */
    public function renderForObjectWithoutDocumentsReturnsEmptyString()
    {
        $uid = $this->realtyObjectMapper->getLoadedTestingModel([])->getUid();

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $uid])
        );
    }

    /**
     * @test
     */
    public function renderForObjectWithDocumentContainsDocumentTitle()
    {
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $object->addDocument('object layout', 'foo.pdf');

        self::assertContains(
            'object layout',
            $this->fixture->render(['showUid' => $object->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderHtmlspecialcharsDocumentTitle()
    {
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $object->addDocument('rise & shine', 'foo.pdf');

        self::assertContains(
            'rise &amp; shine',
            $this->fixture->render(['showUid' => $object->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForObjectWithTwoDocumentsContainsBothDocumentTitles()
    {
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $object->addDocument('object layout', 'foo.pdf');
        $object->addDocument('object overview', 'bar.pdf');

        $result = $this->fixture->render(['showUid' => $object->getUid()]);

        self::assertContains(
            'object layout',
            $result,
            'The first title is missing.'
        );
        self::assertContains(
            'object overview',
            $result,
            'The second title is missing.'
        );
    }

    /**
     * @test
     */
    public function renderContainsLinkToDocumentFile()
    {
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $object->addDocument('object layout', 'foo.pdf');

        self::assertContains(
            'foo.pdf"',
            $this->fixture->render(['showUid' => $object->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForObjectWithTwoDocumentsContainsBothDocumentLinks()
    {
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $object->addDocument('object layout', 'foo.pdf');
        $object->addDocument('object overview', 'bar.pdf');

        $result = $this->fixture->render(['showUid' => $object->getUid()]);

        self::assertContains(
            'foo.pdf',
            $result,
            'The first title is missing.'
        );
        self::assertContains(
            'bar.pdf',
            $result,
            'The second title is missing.'
        );
    }
}
