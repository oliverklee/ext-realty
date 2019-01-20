<?php

namespace OliverKlee\Realty\Tests\Functional\Import;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use OliverKlee\Realty\Tests\Unit\Import\Fixtures\TestingDomDocumentConverter;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Benjamin Schulte <benj@minschulte.de>
 */
class DomDocumentConverterTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/static_info_tables',
        'typo3conf/ext/oelib',
        'typo3conf/ext/realty',
    ];

    /**
     * @var TestingDomDocumentConverter
     */
    private $subject = null;

    /**
     * static_info_tables UID of Germany
     *
     * @var int
     */
    const DE = 54;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new TestingDomDocumentConverter(new \tx_realty_fileNameMapper());
    }

    /*
     * Utility functions
     */

    /**
     * Loads an XML string, sets the raw realty data and returns a DOMDocument
     * of the provided string.
     *
     * @param string $xmlString XML string to set for converting, must contain well-formed XML, must not be empty
     *
     * @return \DOMDocument \DOMDocument of the provided XML string
     */
    private function setRawDataToConvert($xmlString)
    {
        $loadedXml = new \DOMDocument();
        $loadedXml->loadXML($xmlString);
        $this->subject->setRawRealtyData($loadedXml);

        return $loadedXml;
    }

    /**
     * Imports static countries - but only if they aren't already available as static data.
     *
     * @return void
     *
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    private function importCountries()
    {
        if (!\Tx_Oelib_Db::existsRecord('static_countries')) {
            $this->importDataSet(__DIR__ . '/../Fixtures/Countries.xml');
        }
    }

    /**
     * @test
     */
    public function getConvertedDataSetsLocalizedPetsTitleIfValueIsStringTrue()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<verwaltung_objekt>'
            . '<haustiere>TRUE</haustiere>'
            . '</verwaltung_objekt>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $translator = new \tx_realty_translator();

        self::assertSame(
            $translator->translate('label_allowed'),
            $result[0]['pets']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataSetsLocalizedPetsTitleIfValueIsOne()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<verwaltung_objekt>'
            . '<haustiere>1</haustiere>'
            . '</verwaltung_objekt>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $translator = new \tx_realty_translator();

        self::assertSame(
            $translator->translate('label_allowed'),
            $result[0]['pets']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheCountryAsUidOfTheStaticCountryTableForValidCode()
    {
        $this->importCountries();

        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<land iso_land="DEU"/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            self::DE,
            $result[0]['country']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheCountryAsUidOfTheStaticCountryTableForValidCodeTwice()
    {
        $this->importCountries();

        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<land iso_land="DEU"/>' .
            '</geo>' .
            '</immobilie>' .
            '<immobilie>' .
            '<geo>' .
            '<land iso_land="DEU"/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            self::DE,
            $result[0]['country'],
            'The first country is incorrect.'
        );
        self::assertSame(
            self::DE,
            $result[1]['country'],
            'The second country is incorrect.'
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotImportsTheCountryForInvalidCode()
    {
        $this->importCountries();

        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<land iso_land="foo"/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['country'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotImportsTheCountryForEmptyCode()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<land iso_land=""/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['country'])
        );
    }
}
