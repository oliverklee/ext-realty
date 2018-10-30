<?php

namespace OliverKlee\Realty\Tests\Unit\SchedulerTask;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use OliverKlee\Realty\SchedulerTask\OpenImmoImport;
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
class OpenImmoImportTest extends UnitTestCase
{
    /**
     * @var OpenImmoImport
     */
    private $subject = null;

    /**
     * @var Scheduler|ProphecySubjectInterface
     */
    private $schedulerStub = null;

    /**
     * @var \tx_realty_openImmoImport|ObjectProphecy
     */
    private $importServiceProphecy = null;

    protected function setUp()
    {
        $this->schedulerStub = $this->prophesize(Scheduler::class)->reveal();
        GeneralUtility::setSingletonInstance(Scheduler::class, $this->schedulerStub);

        /** @var ObjectManager|ObjectProphecy $objectManagerProphecy */
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        /** @var ObjectManager|ProphecySubjectInterface $objectManager */
        $objectManager = $objectManagerProphecy->reveal();
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        $this->importServiceProphecy = $this->prophesize(\tx_realty_openImmoImport::class);
        /** @var \tx_realty_openImmoImport|ProphecySubjectInterface $importService */
        $importService = $this->importServiceProphecy->reveal();
        $objectManagerProphecy->get(\tx_realty_openImmoImport::class)->willReturn($importService);

        $this->subject = new OpenImmoImport();
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
    public function executeRunsOpenImmoImport()
    {
        $this->importServiceProphecy->importFromZip()->shouldBeCalled();
        $this->importServiceProphecy->wasSuccessful()->willReturn(true);

        $this->subject->execute();
    }

    /**
     * @test
     */
    public function executeForSuccessReturnsTrue()
    {
        $this->importServiceProphecy->importFromZip()->shouldBeCalled();
        $this->importServiceProphecy->wasSuccessful()->willReturn(true);

        static::assertTrue($this->subject->execute());
    }

    /**
     * @test
     */
    public function executeForFailureReturnsFalse()
    {
        $this->importServiceProphecy->importFromZip()->shouldBeCalled();
        $this->importServiceProphecy->wasSuccessful()->willReturn(false);

        static::assertFalse($this->subject->execute());
    }
}
