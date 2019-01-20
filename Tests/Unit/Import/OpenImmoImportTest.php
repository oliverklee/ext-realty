<?php

namespace OliverKlee\Realty\Tests\Unit\Import;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use OliverKlee\Realty\Tests\Unit\Import\Fixtures\TestingImmoImport;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class OpenImmoImportTest extends UnitTestCase
{
    /**
     * @var TestingImmoImport
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_ConfigurationProxy
     */
    private $globalConfiguration = null;

    protected function setUp()
    {
        $this->globalConfiguration = \Tx_Oelib_ConfigurationProxy::getInstance('realty');
        $this->globalConfiguration->setAsBoolean('notifyContactPersons', true);

        $this->subject = new TestingImmoImport(true);
    }

    /**
     * @test
     */
    public function unifyPathDoesNotChangeCorrectPath()
    {
        self::assertSame(
            'correct/path/',
            $this->subject->unifyPath('correct/path/')
        );
    }

    /**
     * @test
     */
    public function unifyPathTrimsAndAddsNecessarySlash()
    {
        self::assertSame(
            'incorrect/path/',
            $this->subject->unifyPath('incorrect/path')
        );
    }

    /*
     * Tests concerning the preparation of e-mails containing the log.
     */

    /**
     * @test
     */
    public function prepareEmailsReturnsEmptyArrayWhenEmptyArrayGiven()
    {
        $emailData = [];

        self::assertSame(
            [],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsReturnsEmptyArrayWhenInvalidArrayGiven()
    {
        $emailData = ['invalid' => 'array'];

        self::assertSame(
            [],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsFillsEmptyEmailFieldWithDefaultAddressIfNotifyContactPersonsIsEnabled()
    {
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');

        $emailData = [
            [
                'recipient' => '',
                'objectNumber' => 'foo',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'bar'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsReplacesNonEmptyEmailAddressIfNotifyContactPersonsIsDisabled()
    {
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');
        $this->globalConfiguration->setAsBoolean('notifyContactPersons', false);
        $emailData = [
            [
                'recipient' => 'foo-valid@example.com',
                'objectNumber' => 'foo',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'bar'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsUsesLogEntryIfOnlyErrorsIsDisabled()
    {
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');

        $emailData = [
            [
                'recipient' => '',
                'objectNumber' => 'foo',
                'logEntry' => 'log entry',
                'errorLog' => 'error log',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'log entry'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsUsesLogEntryIfOnlyErrorsIsEnabled()
    {
        $this->globalConfiguration->setAsBoolean('onlyErrors', true);
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');

        $emailData = [
            [
                'recipient' => '',
                'objectNumber' => 'foo',
                'logEntry' => 'log entry',
                'errorLog' => 'error log',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'error log'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsFillsEmptyObjectNumberFieldWithWrapper()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => '',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['------' => 'bar'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSortsMessagesForOneRecipientWhichHaveTheSameObjectNumber()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'foo',
                'errorLog' => 'foo',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['number' => 'bar'],
                    ['number' => 'foo'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSortsMessagesForTwoRecipientWhichHaveTheSameObjectNumber()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'foo',
                'errorLog' => 'foo',
            ],
            [
                'recipient' => 'bar',
                'objectNumber' => 'number',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['number' => 'foo'],
                ],
                'bar' => [
                    ['number' => 'bar'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSnipsObjectNumbersWithNothingToReport()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => '',
                'errorLog' => '',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['number' => 'bar'],
                ],
            ],
            $this->subject->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSnipsRecipientWhoDoesNotReceiveMessages()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => '',
                'errorLog' => '',
            ],
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => '',
                'errorLog' => '',
            ],
        ];

        self::assertSame(
            [],
            $this->subject->prepareEmails($emailData)
        );
    }
}
