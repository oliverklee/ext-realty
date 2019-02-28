<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd\SingleView;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class DocumentsViewTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/oelib', 'typo3conf/ext/realty'];

    /**
     * @var \tx_realty_pi1_DocumentsView
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var \tx_realty_Mapper_RealtyObject
     */
    private $realtyObjectMapper = null;

    protected function setUp()
    {
        parent::setUp();
        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());

        /** @var BackendUserAuthentication|ObjectProphecy $backEndUserProphecy */
        $backEndUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backEndUserProphecy->isAdmin()->willReturn(true);
        $GLOBALS['BE_USER'] = $backEndUserProphecy->reveal();

        $this->realtyObjectMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new \tx_realty_pi1_DocumentsView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertNotContains('###', $result);
    }

    /**
     * @test
     */
    public function renderForObjectWithoutAttachmentsReturnsEmptyString()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 101]);

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function renderForObjectWithPdfAttachmentContainsEncodedTitle()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertContains('some nice &amp; fine PDF document', $result);
    }

    /**
     * @test
     */
    public function renderForObjectWithPdfAttachmentContainsLinkToDocumentFile()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertContains('test.pdf"', $result);
    }
}
