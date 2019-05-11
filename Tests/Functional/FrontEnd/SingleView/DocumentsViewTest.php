<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd\SingleView;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use OliverKlee\Realty\Tests\Functional\Traits\FalHelper;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class DocumentsViewTest extends FunctionalTestCase
{
    use FalHelper;

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

    protected function setUp()
    {
        parent::setUp();

        $this->provideAdminBackEndUserForFal();

        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new \tx_realty_pi1_DocumentsView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUpWithoutDatabase();
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
