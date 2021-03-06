<?php

namespace OliverKlee\Realty\Tests\Functional\Mapper;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class RealtyObjectMapperTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/realty',
    ];

    /**
     * @var \tx_realty_Mapper_RealtyObject
     */
    private $subject = null;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new \tx_realty_Mapper_RealtyObject();
    }

    /**
     * @test
     */
    public function findForNonexistentRecordReturnsObjectDeadOnLoad()
    {
        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->subject->find(1);
        $this->subject->load($model);

        self::assertTrue($model->isDead());
    }

    /**
     * @test
     */
    public function findForExistingRecordReturnsModelWithData()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->subject->find(102);

        self::assertSame('The place to be', $model->getTitle());
        self::assertSame(3, $model->getNumberOfAttachments());
    }
}
