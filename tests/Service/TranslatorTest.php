<?php

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Service_TranslatorTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_translator instance to be tested
     */
    private $fixture = null;

    /**
     * @test
     */
    public function translatorReturnsEnglishString()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsString('cliLanguage', 'en');
        $this->fixture = new tx_realty_translator();

        self::assertEquals(
            'Allowed',
            $this->fixture->translate('label_allowed')
        );
    }

    /**
     * @test
     */
    public function translatorReturnsDefaultLanguageStringForInvalidLanguageKey()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsString('cliLanguage', 'xy');
        $this->fixture = new tx_realty_translator();

        self::assertEquals(
            'Allowed',
            $this->fixture->translate('label_allowed')
        );
    }

    /**
     * @test
     */
    public function translatorReturnsDefaultLanguageStringForEmptyLanguageKey()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsString('cliLanguage', '');
        $this->fixture = new tx_realty_translator();

        self::assertEquals(
            'Allowed',
            $this->fixture->translate('label_allowed')
        );
    }

    /**
     * @test
     */
    public function translatorThrowsAnExceptionForEmptyKey()
    {
        $this->fixture = new tx_realty_translator();

        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->translate('');
    }
}
