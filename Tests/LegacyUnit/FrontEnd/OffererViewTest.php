<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_OffererViewTest extends TestCase
{
    /**
     * @var tx_realty_pi1_OffererView
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new tx_realty_pi1_OffererView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'company'
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////
    // Utility functions
    //////////////////////

    /**
     * Creates a realty object with an FE user as owner.
     *
     * @param array $ownerData the data to store for the owner
     *
     * @return tx_realty_Model_RealtyObject the realty object with the owner
     */
    private function getRealtyObjectWithOwner(array $ownerData = [])
    {
        return Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)->getLoadedTestingModel(
            [
                'owner' => $this->testingFramework->createFrontEndUser('', $ownerData),
                'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ]
        );
    }

    ///////////////////////////////////////////
    // Tests concerning the utility functions
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function getRealtyObjectWithOwnerReturnsRealtyObjectModel()
    {
        self::assertInstanceOf(
            tx_realty_Model_RealtyObject::class,
            $this->getRealtyObjectWithOwner()
        );
    }

    /**
     * @test
     */
    public function getRealtyObjectWithOwnerAddsAnOwnerToTheModel()
    {
        self::assertTrue(
            $this->getRealtyObjectWithOwner()->hasOwner()
        );
    }

    /**
     * @test
     */
    public function getRealtyObjectWithCanStoreDataToOwner()
    {
        $owner = $this->getRealtyObjectWithOwner(['name' => 'foo'])->getOwner();

        self::assertEquals(
            'foo',
            $owner->getName()
        );
    }

    /////////////////////////////
    // Testing the offerer view
    /////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForShowUidOfExistingRecord()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['employer' => 'foo']);

        self::assertNotEquals(
            '',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['employer' => 'foo']);

        $result = $this->subject->render(['showUid' => $realtyObject->getUid()]);

        self::assertNotEquals(
            '',
            $result
        );
        self::assertNotContains(
            '###',
            $result
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheRealtyObjectsEmployerForValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['employer' => 'foo']);

        self::assertContains(
            'foo',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForValidRealtyObjectWithoutData()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([]);

        self::assertEquals(
            '',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    //////////////////////////////////////////////
    // Testing the displayed offerer information
    //////////////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsContactInformationIfEnabledAndInformationIsSetInTheRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['phone_switchboard' => '12345']);

        $this->subject->setConfigurationValue('displayedContactInformation', 'telephone');

        self::assertContains(
            $this->subject->translate('label_offerer'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsPhoneNumberIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['phone_switchboard' => '12345']);

        $this->subject->setConfigurationValue('displayedContactInformation', 'telephone');

        self::assertContains(
            '12345',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsBasicContactNameIfOffererDataIsEnabledAndInformationIsSetInTheRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['contact_person' => 'Ali Baba']);

        $this->subject->setConfigurationValue('displayedContactInformation', 'offerer_label');

        self::assertContains(
            'Ali Baba',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsFullContactNameIfOffererDataIsEnabledAndInformationIsSetInTheRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)->getLoadedTestingModel(
            [
                'contact_person' => 'Green',
                'contact_person_first_name' => 'Laci',
                'contact_person_salutation' => 'Ms.',
            ]
        );

        $this->subject->setConfigurationValue('displayedContactInformation', 'offerer_label');

        self::assertContains(
            'Ms. Laci Green',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForDisplayContactTelephoneEnabledContactFromObjectAndDirectExtensionSetShowsDirectExtensionNumber(
    ) {
        /** @var tx_realty_Model_RealtyObject|PHPUnit_Framework_MockObject_MockObject $model */
        $model = $this->createPartialMock(
            \tx_realty_Model_RealtyObject::class,
            ['getContactPhoneNumber', 'getProperty']
        );
        $model->expects(self::once())->method('getContactPhoneNumber');
        $model->setData([]);

        /** @var tx_realty_Mapper_RealtyObject|PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->createPartialMock(\tx_realty_Mapper_RealtyObject::class, ['find']);
        $mapper->method('find')
            ->willReturn($model);
        Tx_Oelib_MapperRegistry::set('tx_realty_Mapper_RealtyObject', $mapper);

        $this->subject->setConfigurationValue('displayedContactInformation', 'telephone');

        $this->subject->render(['showUid' => 0]);
    }

    /**
     * @test
     */
    public function renderReturnsCompanyIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['employer' => 'test company']);

        self::assertContains(
            'test company',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsOwnersPhoneNumberIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['telephone' => '123123']
        );

        $this->subject->setConfigurationValue('displayedContactInformation', 'telephone');

        self::assertContains(
            '123123',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsOwnersCompanyIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['company' => 'any company']
        );

        self::assertContains(
            'any company',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsContactInformationIfOptionIsDisabled()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['phone_switchboard' => '12345']);

        $this->subject->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            $this->subject->translate('label_offerer'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsContactInformationForEnabledOptionAndDeletedOwner()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['company' => 'any company', 'deleted' => 1]
        );

        self::assertNotContains(
            $this->subject->translate('label_offerer'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsContactInformationForEnabledOptionAndOwnerWithoutData()
    {
        $realtyObject = $this->getRealtyObjectWithOwner();

        self::assertNotContains(
            $this->subject->translate('label_offerer'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsLabelForLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['username' => 'foo']
        );

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,objects_by_owner_link'
        );
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            $this->subject->translate('label_this_owners_objects'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsLabelOffererIfTheLinkToTheObjectsByOwnerListIsEnabled()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['username' => 'foo']
        );

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,objects_by_owner_link'
        );
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            $this->subject->translate('label_offerer'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['username' => 'foo']
        );
        $objectsByOwnerPid = $this->testingFramework->createFrontEndPage();

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,objects_by_owner_link'
        );
        $this->subject->setConfigurationValue('objectsByOwnerPID', $objectsByOwnerPid);

        self::assertContains(
            '?id=' . $objectsByOwnerPid,
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsOwnerUidInLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet()
    {
        $ownerUid = $this->testingFramework->createFrontEndUser(
            '',
            ['username' => 'foo']
        );
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([
                'owner' => $ownerUid,
                'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ]);

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,objects_by_owner_link'
        );
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );

        $result = $this->subject->render(['showUid' => $realtyObject->getUid()]);

        self::assertContains('owner', $result);
        self::assertContains('=' . $ownerUid, $result);
    }

    /**
     * @test
     */
    public function renderForDisabledOptionAndOwnerSetHidesObjectsByOwnerLink()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['username' => 'foo']
        );

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label'
        );
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertNotContains(
            $this->subject->translate('label_this_owners_objects'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsLinkToTheObjectsByOwnerListForEnabledOptionAndNoOwnerSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([
                'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ]);

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,objects_by_owner_link'
        );
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertNotContains(
            $this->subject->translate('label_this_owners_objects'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsLinkToTheObjectsByOwnerListForDisabledContactInformationAndOwnerAndPidSet()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(
            ['username' => 'foo']
        );

        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertNotContains(
            $this->subject->translate('label_this_owners_objects'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForNoObjectsByOwnerPidSetAndOwnerSetReturnsLinkWithoutId()
    {
        $realtyObject = $this->getRealtyObjectWithOwner(['username' => 'foo']);

        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,objects_by_owner_link'
        );

        self::assertNotContains(
            '?id=',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }
}
