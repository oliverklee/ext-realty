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
    private $subject = null;

    /**
     * @test
     */
    public function translatorReturnsEnglishString()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsString('cliLanguage', 'en');
        $this->subject = new tx_realty_translator();

        self::assertEquals(
            'Allowed',
            $this->subject->translate('label_allowed')
        );
    }

    /**
     * @test
     */
    public function translatorReturnsDefaultLanguageStringForInvalidLanguageKey()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsString('cliLanguage', 'xy');
        $this->subject = new tx_realty_translator();

        self::assertEquals(
            'Allowed',
            $this->subject->translate('label_allowed')
        );
    }

    /**
     * @test
     */
    public function translatorReturnsDefaultLanguageStringForEmptyLanguageKey()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsString('cliLanguage', '');
        $this->subject = new tx_realty_translator();

        self::assertEquals(
            'Allowed',
            $this->subject->translate('label_allowed')
        );
    }

    /**
     * @test
     */
    public function translatorThrowsAnExceptionForEmptyKey()
    {
        $this->subject = new tx_realty_translator();

        $this->expectException(\InvalidArgumentException::class);

        $this->subject->translate('');
    }
}
