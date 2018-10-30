<?php

namespace OliverKlee\Realty\Tests\Unit\Controller;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use OliverKlee\Realty\Controller\OpenImmoController;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ProphecySubjectInterface;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class OpenImmoControllerTest extends UnitTestCase
{
    /**
     * @var OpenImmoController
     */
    private $subject = null;

    /**
     * @var \tx_realty_openImmoImport|ObjectProphecy
     */
    private $importServiceProphecy = null;

    /**
     * @var BackendTemplateView|ObjectProphecy
     */
    private $viewProphecy = null;

    protected function setUp()
    {
        $this->subject = new OpenImmoController();

        $this->importServiceProphecy = $this->prophesize(\tx_realty_openImmoImport::class);
        /** @var \tx_realty_openImmoImport|ProphecySubjectInterface $importService */
        $importService = $this->importServiceProphecy->reveal();
        $this->subject->injectImportService($importService);

        $this->viewProphecy = $this->prophesize(BackendTemplateView::class);
        /** @var BackendTemplateView|ProphecySubjectInterface $view */
        $view = $this->viewProphecy->reveal();
        $this->inject($this->subject, 'view', $view);
    }

    /**
     * @test
     */
    public function isActionController()
    {
        static::assertInstanceOf(ActionController::class, $this->subject);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function indexActionCanBeCalled()
    {
        $this->subject->indexAction();
    }

    /**
     * @test
     */
    public function importActionAssignsSuccessfulImportResultToView()
    {
        $importResult = 'Great import!';
        $this->importServiceProphecy->importFromZip()->willReturn($importResult);
        $this->importServiceProphecy->wasSuccessful()->willReturn(true);

        $this->viewProphecy->assign('importResults', $importResult)->shouldBeCalled();
        $this->viewProphecy->assign('importStatus', 0)->shouldBeCalled();

        $this->subject->importAction();
    }

    /**
     * @test
     */
    public function importActionAssignsFailedImportResultToView()
    {
        $importResult = 'Great import!';
        $this->importServiceProphecy->importFromZip()->willReturn($importResult);
        $this->importServiceProphecy->wasSuccessful()->willReturn(false);

        $this->viewProphecy->assign('importResults', $importResult)->shouldBeCalled();
        $this->viewProphecy->assign('importStatus', 2)->shouldBeCalled();

        $this->subject->importAction();
    }
}
