<?php

namespace OliverKlee\Realty\Tests\Functional;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ContactFormTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/oelib', 'typo3conf/ext/realty'];

    /**
     * @var \tx_realty_contactForm
     */
    private $fixture = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int dummy realty object ID
     */
    private $realtyUid = null;

    /**
     * @var string title for the dummy realty object
     */
    const REALTY_TITLE = 'test title';

    /**
     * @var string object number for the dummy realty object
     */
    const REALTY_OBJECT_NUMBER = '1234567';

    /**
     * @var MailMessage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message = null;

    protected function setUp()
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Alex Doe';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'alex@example.com';

        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());
        $this->realtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::REALTY_TITLE,
                'object_number' => self::REALTY_OBJECT_NUMBER,
            ]
        );

        $this->fixture = new \tx_realty_contactForm(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $this->createContentMock()
        );

        $this->fixture->setConfigurationValue(
            'defaultContactEmail',
            'default-contact@example.com'
        );
        $this->fixture->setConfigurationValue('blindCarbonCopyAddress', '');
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'name,street,zip_and_city,telephone,request,viewing,information,callback'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            'request'
        );

        $this->message = $this->getMock(MailMessage::class, ['send']);
        GeneralUtility::addInstance(MailMessage::class, $this->message);
    }

    protected function tearDown()
    {
        // Get any surplus instances added via GeneralUtility::addInstance.
        GeneralUtility::makeInstance(MailMessage::class);

        $this->testingFramework->cleanUp();
        parent::tearDown();
    }

    /*
     * Utility functions.
     */

    /**
     * Creates a mock content object that can create URLs in the following
     * form:
     *
     * index.php?id=42
     *
     * The page ID isn't checked for existence. So any page ID can be used.
     *
     * @return ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContentMock()
    {
        $mock = $this->getMock(ContentObjectRenderer::class, ['getTypoLink_URL']);
        $mock->method('getTypoLink_URL')
            ->will(self::returnCallback([$this, 'getTypoLinkUrl']));

        return $mock;
    }

    /**
     * Callback function for creating mock typolink URLs.
     *
     * @param int $pageId the page ID to link to, must be >= 0
     *
     * @return string faked URL, will not be empty
     */
    public function getTypoLinkUrl($pageId)
    {
        return 'index.php?id=' . $pageId;
    }

    /*
     * Tests for the utility functions.
     */

    /**
     * @test
     */
    public function createContentMockCreatesContentInstance()
    {
        self::assertInstanceOf(ContentObjectRenderer::class, $this->createContentMock());
    }

    /**
     * @test
     */
    public function createTypoLinkInContentMockCreatesUrlToPageId()
    {
        $contentMock = $this->createContentMock();

        self::assertContains(
            'index.php?id=42',
            $contentMock->getTypoLink_URL(42)
        );
    }

    /*
     * Tests concerning view-dependently displayed strings.
     */

    /**
     * @test
     */
    public function specializedContactFormContainsObjectTitle()
    {
        self::assertContains(
            self::REALTY_TITLE,
            $this->fixture->render(
                ['showUid' => $this->realtyUid]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormContainsObjectNumber()
    {
        self::assertContains(
            self::REALTY_OBJECT_NUMBER,
            $this->fixture->render(
                ['showUid' => $this->realtyUid]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormDoesNotContainTitleLabelWithoutRealtyObjectSet()
    {
        self::assertNotContains(
            $this->fixture->translate('label_title'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function generalContactFormDoesNotContainObjectNumberLabelWithoutRealtyObjectSet()
    {
        self::assertNotContains(
            $this->fixture->translate('label_object_number'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormHasNoDisabledFieldsIfNotLoggedIn()
    {
        self::assertNotContains(
            'disabled',
            $this->fixture->render(['showUid' => $this->realtyUid])
        );
    }

    /**
     * @test
     */
    public function generalContactFormHasNoDisabledFieldsIfNotLoggedIn()
    {
        self::assertNotContains(
            'disabled',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormHasDisabledNameFieldIfLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['name' => 'test user']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            'value="test user" disabled="disabled"',
            $this->fixture->render(['showUid' => $this->realtyUid])
        );
    }

    /**
     * @test
     */
    public function contactFormHasNoNameFieldIfLoggedInButNameIsDisabledByConfiguration()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['name' => 'test user']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->setConfigurationValue('visibleContactFormFields', '');

        self::assertNotContains(
            'value="test user"',
            $this->fixture->render(['showUid' => $this->realtyUid])
        );
    }

    /**
     * @test
     */
    public function specializedContactFormHasDisabledEmailFieldIfLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['email' => 'frontend-user@example.com']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            'value="frontend-user@example.com" disabled="disabled"',
            $this->fixture->render(['showUid' => $this->realtyUid])
        );
    }

    /**
     * @test
     */
    public function generalContactFormHasDisabledNameFieldIfLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['name' => 'test user']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            'value="test user" disabled="disabled"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function generalContactFormHasDisabledEmailFieldIfLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['email' => 'frontend-user@example.com']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            'value="frontend-user@example.com" disabled="disabled"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormHasNoDisabledInfomationIfNotLoggedIn()
    {
        self::assertNotContains(
            $this->fixture->translate('label_requester_data_is_uneditable'),
            $this->fixture->render(['showUid' => $this->realtyUid])
        );
    }

    /**
     * @test
     */
    public function generalContactHasNoDisabledInfomationIfNotLoggedIn()
    {
        self::assertNotContains(
            $this->fixture->translate('label_requester_data_is_uneditable'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormHasDisabledInfomationIfLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['name' => 'test user']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->fixture->translate('label_requester_data_is_uneditable'),
            $this->fixture->render(['showUid' => $this->realtyUid])
        );
    }

    /**
     * @test
     */
    public function generalContactFormHasDisabledInfomationIfLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(['name' => 'test user']);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->fixture->translate('label_requester_data_is_uneditable'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysGeneralViewIfTheRealtyObjectUidWasNotNumeric()
    {
        self::assertNotContains(
            $this->fixture->translate('label_object_number'),
            $this->fixture->render(
                ['showUid' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormNotDisplaysUnreplacedMarkersIfRealtyObjectDoesNotExist()
    {
        self::assertNotContains(
            '###',
            $this->fixture->render(
                [
                    'showUid' => $this->testingFramework->createRecord(
                        'tx_realty_objects',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormNotDisplaysObjectNumberLabelIfRealtyObjectDoesNotExist()
    {
        self::assertNotContains(
            $this->fixture->translate('label_object_number'),
            $this->fixture->render(
                [
                    'showUid' => $this->testingFramework->createRecord(
                        'tx_realty_objects',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormNotDisplaysTitleLabelIfRealtyObjectDoesNotExist()
    {
        self::assertNotContains(
            $this->fixture->translate('label_title'),
            $this->fixture->render(
                [
                    'showUid' => $this->testingFramework->createRecord(
                        'tx_realty_objects',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormNotDisplaysSubmitLabelIfRealtyObjectDoesNotExist()
    {
        self::assertNotContains(
            $this->fixture->translate('label_submit'),
            $this->fixture->render(
                [
                    'showUid' => $this->testingFramework->createRecord(
                        'tx_realty_objects',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormNotDisplaysYourNameLabelIfRealtyObjectDoesNotExist()
    {
        self::assertNotContains(
            $this->fixture->translate('label_your_name'),
            $this->fixture->render(
                [
                    'showUid' => $this->testingFramework->createRecord(
                        'tx_realty_objects',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysYourNameLabelIfThisIsConfigured()
    {
        self::assertContains(
            $this->fixture->translate('label_your_name'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysYourNameLabelIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', '');

        self::assertNotContains(
            $this->fixture->translate('label_your_name'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysRequestFieldIfThisIsConfigured()
    {
        self::assertContains(
            'name="tx_realty_pi1[request]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysRequestFieldIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

        self::assertNotContains(
            'name="tx_realty_pi1[request]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysViewingFieldIfThisIsConfigured()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'viewing'
        );

        self::assertContains(
            'name="tx_realty_pi1[viewing]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysViewingFieldIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

        self::assertNotContains(
            'name="tx_realty_pi1[viewing]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysInformationFieldIfThisIsConfigured()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'information'
        );

        self::assertContains(
            'name="tx_realty_pi1[information]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysInformationFieldIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

        self::assertNotContains(
            'name="tx_realty_pi1[information]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysCallbackFieldIfThisIsConfigured()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback'
        );

        self::assertContains(
            'name="tx_realty_pi1[callback]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysCallbackFieldIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

        self::assertNotContains(
            'name="tx_realty_pi1[callback]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysCallbackAsteriskIfCallbackAndLawTextAreVisible()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback,law'
        );

        self::assertContains(
            'class="tx-realty-pi1-law-asterisk"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysCallbackAsteriskIfCallbackIsVisibleAndLawTextIsNotVisible()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback'
        );

        self::assertNotContains(
            'class="tx-realty-pi1-law-asterisk"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysTermsFieldIfThisIsConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');

        self::assertContains(
            'name="tx_realty_pi1[terms]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysTermsFieldIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

        self::assertNotContains(
            'name="tx_realty_pi1[terms]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysLawTextIfThisIsConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'law');

        self::assertContains(
            'class="tx-realty-pi1-law"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function contactFormNotDisplaysLawTextIfThisIsNotConfigured()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

        self::assertNotContains(
            'class="tx-realty-pi1-law"',
            $this->fixture->render()
        );
    }

    /*
     * Test concerning the link to the terms
     */

    /**
     * @test
     */
    public function termsLabelContainsLinkToTermsPage()
    {
        $termsPid = 1337;
        $this->fixture->setConfigurationValue('termsPID', $termsPid);
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');

        self::assertContains(
            'a href="index.php?id=' . $termsPid,
            $this->fixture->render()
        );
    }

    /*
     * Tests concerning (error) messages.
     */

    /**
     * @test
     */
    public function specializedContactFormDisplaysAnErrorIfRealtyObjectDoesNotExist()
    {
        self::assertContains(
            $this->fixture->translate('message_noResultsFound_contact_form'),
            $this->fixture->render(
                [
                    'showUid' => $this->testingFramework->createRecord(
                        'tx_realty_objects',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided()
    {
        self::assertContains(
            $this->fixture->translate('label_set_valid_email_address'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester-invalid-email',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided()
    {
        self::assertContains(
            $this->fixture->translate('label_set_valid_email_address'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester-invalid-email',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheEmailField()
    {
        self::assertContains(
            $this->fixture->translate('label_set_valid_email_address'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com' . LF . 'anything',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheNameField()
    {
        self::assertContains(
            $this->fixture->translate('label_set_name'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name' . LF . 'anything',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysErrorAfterSubmittingIfAngleBracketsAreSetInTheNameField()
    {
        self::assertContains(
            $this->fixture->translate('label_set_name'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name < anything',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysErrorAfterSubmittingIfQuotationMarksAreSetInTheNameField()
    {
        self::assertContains(
            $this->fixture->translate('label_set_name'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name " anything',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvidedButIsRequired()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'name');

        self::assertContains(
            $this->fixture->translate('message_required_field'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => '',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvidedButIsRequired()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'name');

        self::assertContains(
            $this->fixture->translate('message_required_field'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => '',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormForRequiredMessageDisplaysErrorAfterSubmittingIfTheRequestWasEmpty()
    {
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            'request'
        );

        self::assertContains(
            $this->fixture->translate('message_required_field_request'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => '',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormForRequiredMessageDisplaysErrorAfterSubmittingIfTheRequestWasEmpty()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'request');

        self::assertContains(
            $this->fixture->translate('message_required_field_request'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => '',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormWithoutRequiredMessageNotDisplaysErrorAfterSubmittingIfTheRequestWasEmpty()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', '');

        self::assertNotContains(
            $this->fixture->translate('message_required_field_request'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => '',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormDisplaysErrorAfterSubmittingIfTheObjectHasNoContactDataAndNoDefaultEmailWasSet(
    ) {
        $this->fixture->setConfigurationValue('defaultContactEmail', '');

        self::assertContains(
            $this->fixture->translate('label_no_contact_person'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormDisplaysAnErrorAfterSubmittingIfNoDefaultEmailAddressWasSet()
    {
        $this->fixture->setConfigurationValue('defaultContactEmail', '');

        self::assertContains(
            $this->fixture->translate('label_no_contact_person'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysTwoErrorMessagesIfNameAndStreetAreRequiredButEmpty()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'name,street');

        self::assertEquals(
            2,
            substr_count(
                $this->fixture->render(
                    [
                        'isSubmitted' => true,
                        'requesterName' => '',
                        'requesterEmail' => 'requester@example.com',
                        'request' => 'foo',
                    ]
                ),
                $this->fixture->translate('message_required_field')
            )
        );
    }

    /**
     * @test
     */
    public function specializedContactFormStillDisplaysTheFormIfAnErrorOccurs()
    {
        $result = $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => '',
            ]
        );

        self::assertContains(
            $this->fixture->translate('message_required_field_request'),
            $result
        );
        self::assertContains(
            self::REALTY_TITLE,
            $result
        );
        self::assertContains(
            $this->fixture->translate('label_your_request'),
            $result
        );
    }

    /**
     * @test
     */
    public function contactFormStillDisplaysGeneralViewOfTheFormIfAnErrorOccurs()
    {
        $result = $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => '',
            ]
        );

        self::assertContains(
            $this->fixture->translate('message_required_field_request'),
            $result
        );
        self::assertNotContains(
            self::REALTY_TITLE,
            $result
        );
        self::assertContains(
            $this->fixture->translate('label_your_request'),
            $result
        );
    }

    /**
     * @test
     */
    public function specializedContactFormShowsSubmittedMessageIfAllContentIsValid()
    {
        self::assertContains(
            $this->fixture->translate('label_message_sent'),
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function generalContactFormShowsSubmittedMessageIfAllContentIsValid()
    {
        self::assertContains(
            $this->fixture->translate('label_message_sent'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysErrorMessageForEmptyRequiredStreetField()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'street');

        self::assertContains(
            $this->fixture->translate('message_required_field'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'foo bar',
                    'requesterStreet' => '',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysErrorMessageForEmptyRequiredCityField()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'city');

        self::assertContains(
            $this->fixture->translate('message_required_field_requesterCity'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'foo bar',
                    'requesterCity' => '',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormDisplaysNoErrorMessageForNonEmptyRequiredField()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'street');

        self::assertNotContains(
            $this->fixture->translate('message_required_field'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'request' => 'foo bar',
                    'requesterStreet' => 'main street',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormForVisibleAndNotSubmittedTermsFieldDisplaysErrorMessage()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');
        $this->fixture->setConfigurationValue('requiredContactFormFields', '');

        self::assertContains(
            $this->fixture->translate('message_required_field_terms'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormForVisibleAndFilledTermsFieldNotDisplaysErrorMessage()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');
        $this->fixture->setConfigurationValue('requiredContactFormFields', '');

        self::assertNotContains(
            $this->fixture->translate('message_required_field_terms'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'terms' => '1',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function contactFormForNotVisibleAndNotSubmittedTermsFieldNotDisplaysErrorMessage()
    {
        $this->fixture->setConfigurationValue('visibleContactFormFields', '');
        $this->fixture->setConfigurationValue('requiredContactFormFields', '');

        self::assertNotContains(
            $this->fixture->translate('message_required_field_terms'),
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /*
     * Tests for generally displayed strings.
     */

    /**
     * @test
     */
    public function formWithMinimalContentDoesNotContainUnreplacedMarkers()
    {
        self::assertNotContains(
            '###',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function formHasInputFieldForStreet()
    {
        self::assertContains(
            'tx_realty_pi1[requesterStreet]',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function formHasInputFieldForZip()
    {
        self::assertContains(
            'tx_realty_pi1[requesterStreet]',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function formHasInputFieldForCity()
    {
        self::assertContains(
            'tx_realty_pi1[requesterStreet]',
            $this->fixture->render()
        );
    }

    /*
     * Tests concerning the form fields' values.
     */

    /**
     * @test
     */
    public function notSuccessfullySubmittedFormStillContainsSubmittedValueForRequest()
    {
        self::assertContains(
            '>the request</textarea>',
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'request' => 'the request',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function notSuccessfullySubmittedFormStillContainsSubmittedValueForName()
    {
        self::assertContains(
            'value="any name"',
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterName' => 'any name',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function notSuccessfullySubmittedFormStillContainsSubmittedValueForPhone()
    {
        self::assertContains(
            'value="1234567"',
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterPhone' => '1234567',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function notSuccessfullySubmittedFormStillContainsSubmittedValueOfEmail()
    {
        self::assertContains(
            'value="requester@example.com"',
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function notSuccessfullySubmittedFormStillContainsSubmittedValueOfFalseEmail()
    {
        self::assertContains(
            'value="requester-invalid-email"',
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester-invalid-email',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function notSuccessfullySubmittedFormStillContainsSubmittedValueWithHtmlSpecialCharedTags()
    {
        self::assertContains(
            '>&lt;fieldset /&gt;the request&lt;script /&gt;</textarea>',
            $this->fixture->render(
                [
                    'isSubmitted' => true,
                    'request' => '<fieldset />the request<script />',
                ]
            )
        );
    }

    /*
     * Tests concerning the content of e-mails.
     */

    /**
     * @test
     */
    public function specializedContactFormUsesDefaultEmailAddressIfTheObjectHasNoContactData()
    {
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'default-contact@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormSendsEmail()
    {
        $this->message->expects(self::once())->method('send');

        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );
    }

    /**
     * @test
     */
    public function specializedContactFormUsesFullContactNameAndEmailFromObjectWhenDataSourceIsSetToRealtyObject()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            [
                'contact_data_source' => \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
                'contact_person_salutation' => 'Mr.',
                'contact_person_first_name' => 'Larry',
                'contact_person' => 'Page',
                'contact_email' => 'any-valid@example.com',
            ]
        );
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertSame(
            ['any-valid@example.com' => 'Mr. Larry Page'],
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormUsesTheDefaultEmailAddressEmailIfTheOwnersAddressWasNotValid()
    {
        $ownerUid = $this->testingFramework->createFrontEndUser(
            '',
            ['email' => 'invalid-address']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            [
                'contact_data_source' => \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
                'owner' => $ownerUid,
            ]
        );
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'default-contact@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormUsesTheDefaultEmailAddressIfTheContactPersonsAddressIsInvalid()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            [
                'contact_person' => 'Mr.Contact',
                'contact_email' => 'invalid-address',
            ]
        );
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'default-contact@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToOwner()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            [
                'contact_data_source' => \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
                'owner' => $this->testingFramework->createFrontEndUser(
                    '',
                    ['name' => 'Trinity', 'email' => 'frontend-user@example.com']
                ),
            ]
        );

        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertSame(
            ['frontend-user@example.com' => 'Trinity'],
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormUsesDefaultEmailAddressWhenDataSourceIsDeletedOwner()
    {
        $deletedUserUid = $this->testingFramework->createFrontEndUser(
            '',
            [
                'name' => 'deleted user',
                'email' => 'deleted-user@example.com',
                'telephone' => '7654321',
                'deleted' => 1,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            [
                'contact_data_source' => \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
                'owner' => $deletedUserUid,
            ]
        );
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'default-contact@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function specializedContactFormUsesDefaultEmailAddressForInvalidAddressFromOwnerAccount()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            [
                'contact_data_source' => \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
                'owner' => $this->testingFramework->createFrontEndUser(
                    '',
                    ['email' => 'invalid-email']
                ),
            ]
        );
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'default-contact@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function generalContactFormSendsEmail()
    {
        $this->message->expects(self::once())->method('send');

        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );
    }

    /**
     * @test
     */
    public function generalContactFormUsesTheDefaultEmailAddress()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'default-contact@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function usesDefaultFromEmailFromInstallTool()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        static::assertSame(['alex@example.com' => 'Alex Doe'], $this->message->getFrom());
    }

    /**
     * @test
     */
    public function nameAndEmailAddressAreFetchedAutomaticallyAsReplyToIfAFeUserIsLoggedIn()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(
            [
                'name' => 'test user',
                'email' => 'frontend-user@example.com',
            ]
        );
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'request' => 'the request',
            ]
        );

        self::assertSame(
            ['frontend-user@example.com' => 'test user'],
            $this->message->getReplyTo()
        );
    }

    /**
     * @test
     */
    public function emailAddressIsFetchedAutomaticallyAsReplyToIfAFeUserIsLoggedInAndNoUserNameSet()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(
            [
                'name' => '',
                'email' => 'frontend-user@example.com',
            ]
        );
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'frontend-user@example.com',
            $this->message->getReplyTo()
        );
    }

    /**
     * @test
     */
    public function senderDoesNotContainTheNameIfAFeUserIsLoggedAndUserNameVisibilityDisabled()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData(
            [
                'name' => 'test user',
                'email' => 'frontend-user@example.com',
            ]
        );
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->setConfigurationValue('visibleContactFormFields', '');

        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'request' => 'the request',
            ]
        );

        self::assertArrayNotHasKey(
            'test user',
            $this->message->getReplyTo()
        );
    }

    /**
     * @test
     */
    public function headerContainsABccAddressIfThisWasConfigured()
    {
        $this->fixture->setConfigurationValue(
            'blindCarbonCopyAddress',
            'bcc-address@example.com'
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertArrayHasKey(
            'bcc-address@example.com',
            $this->message->getBcc()
        );
    }

    /**
     * @test
     */
    public function headerContainsNoBccLineIfNoAddressWasConfigured()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertNull(
            $this->message->getBcc()
        );
    }

    /**
     * @test
     */
    public function noEmailIsSentIfTheContactFormWasNotFilledCorrectly()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'any name',
            ]
        );

        $this->message->expects(self::never())->method('send');
    }

    /**
     * @test
     */
    public function emailWithMinimumContentContainsNoUnreplacedMarkers()
    {
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertNotContains(
            '###',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailWithNonEmptyRequestContainsRequestIntro()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'name');
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'Bonjour!',
            ]
        );

        self::assertContains(
            $this->fixture->translate('label_has_request'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailWithEmptyRequestNotContainsRequestIntro()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'name');
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => '',
            ]
        );

        self::assertNotContains(
            $this->fixture->translate('label_has_request'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailWithMissingRequestNotContainsRequestIntro()
    {
        $this->fixture->setConfigurationValue('requiredContactFormFields', 'name');
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
            ]
        );

        self::assertNotContains(
            $this->fixture->translate('label_has_request'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailContainsTheTitleOfTheRequestedObjectIfASpecializedContactFormWasSubmitted()
    {
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            self::REALTY_TITLE,
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailContainsTheObjectNumberOfTheRequestedObjectIfASpecializedContactFormWasSubmitted()
    {
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            self::REALTY_OBJECT_NUMBER,
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailFromGeneralContactFormContainsASummaryStringOfTheFavoritesList()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
                'summaryStringOfFavorites' => 'summary of favorites',
            ]
        );

        self::assertContains(
            'summary of favorites',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyContainsTheRequestersName()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            'a name of a requester',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyContainsTheRequestersEmailAddress()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            'requester@example.com',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyContainsTheRequestersPhoneNumber()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'requesterPhone' => '1234567',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            '1234567',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyNotContainsThePhoneNumberLabelIfNoPhoneNumberWasSet()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertNotContains(
            $this->fixture->translate('label_requester_phone'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyContainsTheRequestersStreet()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'requesterStreet' => 'main street',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            'main street',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyContainsTheRequestersZip()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'requesterZip' => '12345',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            '12345',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailBodyContainsTheRequestersCity()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'requesterCity' => 'a city',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            'a city',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailSubjectIsGeneralForTheGeneralForm()
    {
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            $this->fixture->translate('label_email_subject_general'),
            $this->message->getSubject()
        );
    }

    /**
     * @test
     */
    public function emailSubjectIsSpecializedForTheSpecializedForm()
    {
        $this->fixture->render(
            [
                'showUid' => $this->realtyUid,
                'isSubmitted' => true,
                'requesterName' => 'any name',
                'requesterEmail' => 'requester@example.com',
                'request' => 'the request',
            ]
        );

        self::assertContains(
            self::REALTY_OBJECT_NUMBER,
            $this->message->getSubject()
        );
    }

    /*
     * Tests concerning the monkey functionality of the checkboxes
     */

    /**
     * @test
     */
    public function viewingCheckboxNotSubmittedIsNotMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'viewing'
        );

        self::assertNotContains(
            'checked="checked" name="tx_realty_pi1[viewing]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function viewingCheckboxSubmittedCheckedIsMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'viewing'
        );

        self::assertContains(
            'checked="checked" name="tx_realty_pi1[viewing]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'viewing' => '1',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function informationCheckboxNotSubmittedIsNotMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'information'
        );

        self::assertNotContains(
            'checked="checked" name="tx_realty_pi1[information]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function informationCheckboxSubmittedCheckedIsMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'information'
        );

        self::assertContains(
            'checked="checked" name="tx_realty_pi1[information]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'information' => '1',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function callbackCheckboxNotSubmittedIsNotMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback'
        );

        self::assertNotContains(
            'checked="checked" name="tx_realty_pi1[callback]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function callbackCheckboxSubmittedCheckedIsMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback'
        );

        self::assertContains(
            'checked="checked" name="tx_realty_pi1[callback]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'callback' => '1',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function termsCheckboxNotSubmittedIsNotMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'terms'
        );

        self::assertNotContains(
            'checked="checked" name="tx_realty_pi1[terms]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function termsCheckboxSubmittedCheckedIsMarkedAsChecked()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'terms'
        );

        self::assertContains(
            'checked="checked" name="tx_realty_pi1[terms]"',
            $this->fixture->render(
                [
                    'showUid' => $this->realtyUid,
                    'isSubmitted' => true,
                    'requesterEmail' => 'requester@example.com',
                    'terms' => '1',
                ]
            )
        );
    }

    /*
     * Tests concerning the checkbox texts in the e-mail
     */

    /**
     * @test
     */
    public function emailForCheckedViewingCheckboxContainsViewingCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'viewing'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'viewing' => '1',
            ]
        );

        self::assertContains(
            $this->fixture->translate('label_viewing'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForVisibleAndNotCheckedViewingCheckboxNotContainsViewingCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'viewing'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'viewing' => '',
            ]
        );

        self::assertNotContains(
            $this->fixture->translate('label_viewing'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForCheckedInformationCheckboxContainsInformationCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'information'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'information' => '1',
            ]
        );

        self::assertContains(
            $this->fixture->translate('label_information'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForVisibleAndNotCheckedInformationCheckboxNotContainsInformationCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'information'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'information' => '',
            ]
        );

        self::assertNotContains(
            $this->fixture->translate('label_information'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForCheckedCallbackCheckboxContainsCallbackCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'callback' => '1',
            ]
        );

        self::assertContains(
            $this->fixture->translate('label_callback'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForVisibleAndNotCheckedCallbackCheckboxNotContainsCallbackCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'callback'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'callback' => '',
            ]
        );

        self::assertNotContains(
            $this->fixture->translate('label_callback'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForCheckedTermsCheckboxContainsStrippedTermsCheckboxText()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'terms'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'terms' => '1',
            ]
        );

        $label = strip_tags(
            str_replace(' %s', '', $this->fixture->translate('label_terms'))
        );
        self::assertContains(
            $label,
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function emailForVisibleAndNotCheckedTermsCheckboxNotSendsEmail()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'terms'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'terms' => '',
            ]
        );

        $this->message->expects(self::never())->method('send');
    }

    /**
     * @test
     */
    public function emailForCheckedInformationAndCallbackCheckboxContainsBothCheckboxTexts()
    {
        $this->fixture->setConfigurationValue(
            'visibleContactFormFields',
            'information,callback'
        );
        $this->fixture->setConfigurationValue(
            'requiredContactFormFields',
            ''
        );
        $this->fixture->render(
            [
                'isSubmitted' => true,
                'requesterName' => 'a name of a requester',
                'requesterEmail' => 'requester@example.com',
                'information' => '1',
                'callback' => '1',
            ]
        );

        $emailBody = $this->message->getBody();
        self::assertContains(
            $this->fixture->translate('label_information'),
            $emailBody
        );
        self::assertContains(
            $this->fixture->translate('label_callback'),
            $emailBody
        );
    }
}
