<?php

namespace OliverKlee\Realty\Tests\Unit\Model;

use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class RealtyObjectTest extends UnitTestCase
{
    /**
     * @var \tx_realty_Model_RealtyObject
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new \tx_realty_Model_RealtyObject(true);
    }

    /**
     * @test
     */
    public function isAbstractModel()
    {
        self::assertInstanceOf(\Tx_Oelib_Model::class, $this->subject);
    }

    /**
     * @test
     */
    public function getObidReturnsObid()
    {
        $obid = 'bklhjewkbjvewq';
        $this->subject->setData(['openimmo_obid' => $obid]);

        self::assertSame($obid, $this->subject->getObid());
    }

    /**
     * @test
     */
    public function getNumberOfAttachmentsInitiallyReturnsZero()
    {
        $this->subject->setData([]);

        $result = $this->subject->getNumberOfAttachments();

        self::assertSame(0, $result);
    }

    /**
     * @test
     */
    public function setNumberOfAttachmentsSetsNumberOfAttachments()
    {
        $number = 12;
        $this->subject->setData([]);

        $this->subject->setNumberOfAttachments($number);

        self::assertSame(12, $this->subject->getNumberOfAttachments());
    }

    /**
     * @test
     */
    public function setNumberOfAttachmentsMarksModelAsDirty()
    {
        $this->subject->setData([]);
        $this->subject->setNumberOfAttachments(1);

        self::assertTrue($this->subject->isDirty());
    }

    /**
     * @test
     */
    public function increaseNumberOfAttachmentsIncreasesNumberByOne()
    {
        $number = 0;
        $this->subject->setData([]);
        $this->subject->setNumberOfAttachments($number);

        $this->subject->increaseNumberOfAttachments();

        self::assertSame($number + 1, $this->subject->getNumberOfAttachments());
    }

    /**
     * @test
     */
    public function decreaseNumberOfAttachmentsDecreasesNumberByOne()
    {
        $number = 1;
        $this->subject->setData([]);
        $this->subject->setNumberOfAttachments($number);

        $this->subject->decreaseNumberOfAttachments();

        self::assertSame($number - 1, $this->subject->getNumberOfAttachments());
    }

    /**
     * @test
     */
    public function decreaseNumberOfAttachmentsDoesNotGoLowerThanZero()
    {
        $this->subject->setData([]);
        $this->subject->setNumberOfAttachments(0);

        $this->subject->decreaseNumberOfAttachments();

        self::assertSame(0, $this->subject->getNumberOfAttachments());
    }
}
