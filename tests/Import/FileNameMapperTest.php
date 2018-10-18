<?php

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Import_FileNameMapperTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_fileNameMapper instance to be tested
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->fixture = new tx_realty_fileNameMapper();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsTheOriginalFileNameIfNoFileWithThisNameExists()
    {
        self::assertEquals(
            'test.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped()
    {
        $this->fixture->getUniqueFileNameAndMapIt('test.txt');

        self::assertEquals(
            'test_00.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWhichBeginsWithNumbersWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped(
    ) {
        $this->fixture->getUniqueFileNameAndMapIt('1234-test.txt');

        self::assertEquals(
            '1234-test_00.txt',
            $this->fixture->getUniqueFileNameAndMapIt('1234-test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWhichAlreadyHas99SuffixWith100SuffixIfAFileWithTheOriginalNameIsAlreadyMapped(
    ) {
        $this->fixture->getUniqueFileNameAndMapIt('test_99.txt');

        self::assertEquals(
            'test_100.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test_99.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfNoFileWithThisNameExists()
    {
        self::assertEquals(
            'test_foo.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test,foo.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfAFileWithTheOriginalNameIsAlreadyMapped(
    ) {
        $this->fixture->getUniqueFileNameAndMapIt('test,foo.txt');

        self::assertEquals(
            'test_foo_00.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test,foo.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyMapped(
    ) {
        $this->fixture->getUniqueFileNameAndMapIt('test.txt');
        $this->fixture->getUniqueFileNameAndMapIt('test_00.txt');

        self::assertEquals(
            'test_01.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test.txt')
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
            $this->fixture->getUniqueFileNameAndMapIt('test.txt')
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
            $this->fixture->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfTheOriginalFileNameExistsAndTheNameWithA00SuffixIsAlreadyMapped(
    ) {
        $this->testingFramework->createDummyFile('test.txt');
        $this->fixture->getUniqueFileNameAndMapIt('test_00.txt');

        self::assertEquals(
            'test_01.txt',
            $this->fixture->getUniqueFileNameAndMapIt('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsTheOriginalNameAsMappedFileNameInAnArrayIfNoFileWithThisFilenameExists(
    ) {
        $this->fixture->getUniqueFileNameAndMapIt('test.txt');

        self::assertEquals(
            ['test.txt'],
            $this->fixture->releaseMappedFileNames('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsTheUniqueMappedFileNameInAnArrayIfOneOriginalFileHasBeenMappedTwice()
    {
        $this->fixture->getUniqueFileNameAndMapIt('test.txt');
        $this->fixture->getUniqueFileNameAndMapIt('test.txt');

        self::assertEquals(
            ['test.txt', 'test_00.txt'],
            $this->fixture->releaseMappedFileNames('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsAnEmptyArrayIfNoFileWithThisFilenameHasBeenMapped()
    {
        self::assertEquals(
            [],
            $this->fixture->releaseMappedFileNames('test.txt')
        );
    }

    /**
     * @test
     */
    public function releaseMappedFileNamesReturnsAnEmptyArrayIfAMappedFileHasBeenFetchedBefore()
    {
        $this->fixture->getUniqueFileNameAndMapIt('test.txt');
        $this->fixture->releaseMappedFileNames('test.txt');

        self::assertEquals(
            [],
            $this->fixture->releaseMappedFileNames('test.txt')
        );
    }
}
