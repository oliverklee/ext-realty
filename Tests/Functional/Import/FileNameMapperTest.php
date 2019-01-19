<?php

namespace OliverKlee\Realty\Tests\Functional\Import;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class FileNameMapperTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/oelib', 'typo3conf/ext/realty'];

    /**
     * @var \tx_realty_fileNameMapper instance to be tested
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        parent::setUp();
        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->subject = new \tx_realty_fileNameMapper();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsTheOriginalFileNameIfNoFileWithThisNameExists()
    {
        self::assertEquals(
            'test.txt',
            $this->subject->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped()
    {
        $this->subject->getUniqueFileNameAndMapIt('test.txt');

        self::assertEquals(
            'test_00.txt',
            $this->subject->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWhichBeginsWithNumbersWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped(
    ) {
        $this->subject->getUniqueFileNameAndMapIt('1234-test.txt');

        self::assertEquals(
            '1234-test_00.txt',
            $this->subject->getUniqueFileNameAndMapIt('1234-test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWhichAlreadyHas99SuffixWith100SuffixIfAFileWithTheOriginalNameIsAlreadyMapped(
    ) {
        $this->subject->getUniqueFileNameAndMapIt('test_99.txt');

        self::assertEquals(
            'test_100.txt',
            $this->subject->getUniqueFileNameAndMapIt('test_99.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfNoFileWithThisNameExists()
    {
        self::assertEquals(
            'test_foo.txt',
            $this->subject->getUniqueFileNameAndMapIt('test,foo.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfAFileWithTheOriginalNameIsAlreadyMapped(
    ) {
        $this->subject->getUniqueFileNameAndMapIt('test,foo.txt');

        self::assertEquals(
            'test_foo_00.txt',
            $this->subject->getUniqueFileNameAndMapIt('test,foo.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyMapped(
    ) {
        $this->subject->getUniqueFileNameAndMapIt('test.txt');
        $this->subject->getUniqueFileNameAndMapIt('test_00.txt');

        self::assertEquals(
            'test_01.txt',
            $this->subject->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyStored()
    {
        $this->testingFramework->createDummyFile('test.txt');

        self::assertEquals(
            'test_00.txt',
            $this->subject->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyStored(
    ) {
        $this->testingFramework->createDummyFile('test.txt');
        $this->testingFramework->createDummyFile('test_00.txt');

        self::assertEquals(
            'test_01.txt',
            $this->subject->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfTheOriginalFileNameExistsAndTheNameWithA00SuffixIsAlreadyMapped(
    ) {
        $this->testingFramework->createDummyFile('test.txt');
        $this->subject->getUniqueFileNameAndMapIt('test_00.txt');

        self::assertEquals(
            'test_01.txt',
            $this->subject->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsTheOriginalNameAsMappedFileNameInAnArrayIfNoFileWithThisFilenameExists(
    ) {
        $this->subject->getUniqueFileNameAndMapIt('test.txt');

        self::assertEquals(
            ['test.txt'],
            $this->subject->releaseMappedFileNames('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsTheUniqueMappedFileNameInAnArrayIfOneOriginalFileHasBeenMappedTwice()
    {
        $this->subject->getUniqueFileNameAndMapIt('test.txt');
        $this->subject->getUniqueFileNameAndMapIt('test.txt');

        self::assertEquals(
            ['test.txt', 'test_00.txt'],
            $this->subject->releaseMappedFileNames('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsAnEmptyArrayIfNoFileWithThisFilenameHasBeenMapped()
    {
        self::assertSame([], $this->subject->releaseMappedFileNames('test.txt'));
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsAnEmptyArrayIfAMappedFileHasBeenFetchedBefore()
    {
        $this->subject->getUniqueFileNameAndMapIt('test.txt');
        $this->subject->releaseMappedFileNames('test.txt');

        self::assertSame([], $this->subject->releaseMappedFileNames('test.txt'));
    }
}
