<?php

namespace OliverKlee\Realty\Tests\Unit\SchedulerTask;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use OliverKlee\Realty\SchedulerTask\ImageCleanup;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ProphecySubjectInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ImageCleanupTest extends UnitTestCase
{
    /**
     * @var ImageCleanup
     */
    private $subject = null;

    /**
     * @var Scheduler|ProphecySubjectInterface
     */
    private $schedulerStub = null;

    /**
     * @var \tx_realty_cli_ImageCleanUp|ObjectProphecy
     */
    private $cleanupServiceProphecy = null;

    protected function setUp()
    {
        $this->schedulerStub = $this->prophesize(Scheduler::class)->reveal();
        GeneralUtility::setSingletonInstance(Scheduler::class, $this->schedulerStub);

        /** @var ObjectManager|ObjectProphecy $objectManagerProphecy */
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        /** @var ObjectManager|ProphecySubjectInterface $objectManager */
        $objectManager = $objectManagerProphecy->reveal();
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        $this->cleanupServiceProphecy = $this->prophesize(\tx_realty_cli_ImageCleanUp::class);
        /** @var \tx_realty_cli_ImageCleanUp|ProphecySubjectInterface $cleanupService */
        $cleanupService = $this->cleanupServiceProphecy->reveal();
        $objectManagerProphecy->get(\tx_realty_cli_ImageCleanUp::class)->willReturn($cleanupService);

        $this->subject = new ImageCleanup();
    }

    protected function tearDown()
    {
        GeneralUtility::removeSingletonInstance(Scheduler::class, $this->schedulerStub);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function isSchedulerTask()
    {
        static::assertInstanceOf(AbstractTask::class, $this->subject);
    }

    /**
     * @test
     */
    public function executeRunsImageCleanup()
    {
        $this->cleanupServiceProphecy->checkUploadFolder()->shouldBeCalled();
        $this->cleanupServiceProphecy->hideUnusedImagesInDatabase()->shouldBeCalled();
        $this->cleanupServiceProphecy->deleteUnusedDocumentRecords()->shouldBeCalled();
        $this->cleanupServiceProphecy->deleteUnusedFiles()->shouldBeCalled();

        static::assertTrue($this->subject->execute());
    }
}
