<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Saskia Metzler <saskia@merlin.owl.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Unit tests for the tx_realty_pi1_AccessCheck class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Service_AccessCheckTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_AccessCheck
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer UID of the dummy object
	 */
	private $dummyObjectUid;

	public function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();

		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->dummyObjectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS
		);

		$this->fixture = new tx_realty_pi1_AccessCheck();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////////////////////////////
	// Tests concerning access to the FE editor.
	//////////////////////////////////////////////

	public function testCheckAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('fe_editor', array(
			'showUid' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('deleted' => 1)
			)
		));
	}

	public function testCheckAccessForFeEditorThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidUidAndAUserLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_fe_editor'
		);

		$this->fixture->checkAccess('fe_editor', array(
			'showUid' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('deleted' => 1)
			)
		));
	}

	public function test404HeaderIsSentWhenCheckAccessForFeEditorThrowsExceptionWithObjectDoesNotExistMessage() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess('fe_editor', array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				)
			));
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		$this->assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testCheckAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForANewObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}

	public function testCheckAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	public function testCheckAccessForFeEditorThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_access_denied'
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	public function test403HeaderIsSentWhenCheckAccessForFeEditorThrowsExceptionWithAccessDeniedMessage() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess(
				'fe_editor', array('showUid' => $this->dummyObjectUid)
			);
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		$this->assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testCheckAccessForFeEditorDoesNotThrowAnExceptionIfTheObjectExistsAndTheUserIsLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	public function testCheckAccessForFeEditorDoesNotThrowAnExceptionIfTheNonPublishedObjectExistsAndTheUserIsLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array(
				'owner' => $user->getUid(),
				'hidden' => TRUE,
			)
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	public function testCheckAccessForFeEditorDoesNotThrowAnExceptionIfTheObjectIsNewAndTheUserIsLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getLoadedTestingModel(array());
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}

	public function testCheckAccessForFeEditorForLoggedInUserWithNoObjectsLeftToEnterThrowsExceptionWithNoObjectsLeftMessage() {
		$user = $this->getMock(
			'tx_realty_Model_FrontEndUser', array('getNumberOfObjects')
		);
		$user->setData(array('tx_realty_maximum_objects' => 1));
		$user->expects($this->any())->method('getNumberOfObjects')
			->will($this->returnValue(1));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_no_objects_left'
		);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}

	public function testCheckAccessForFeEditorForLoggedInUserWithNoObjectsLeftToEnterAndEditingAnExistingObjectDoesNotThrowException() {
		$user = $this->getMock(
			'tx_realty_Model_FrontEndUser', array('getNumberOfObjects')
		);
		$user->setData(array('tx_realty_maximum_objects' => 1));
		$user->expects($this->any())->method('getNumberOfObjects')
			->will($this->returnValue(1));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$objectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess('fe_editor', array('showUid' => $objectUid));
	}

	public function testCheckAccessForFeEditorForLoggedInUserWithObjectsLeftToEnterThrowsNoException() {
		$user = $this->getMock(
			'tx_realty_Model_FrontEndUser', array('getNumberOfObjects')
		);
		$user->setData(array('tx_realty_maximum_objects' => 1));
		$user->expects($this->any())->method('getNumberOfObjects')
			->will($this->returnValue(0));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}


	/////////////////////////////////////////////////
	// Tests concerning access to the image upload.
	/////////////////////////////////////////////////

	public function testCheckAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('image_upload', array(
			'showUid' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('deleted' => 1)
			)
		));
	}

	public function testCheckAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessageForAZeroObjectUidAndAUserLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_image_upload'
		);

		$this->fixture->checkAccess('image_upload', array('showUid' => 0));
	}

	public function testCheckAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidObjectUidAndAUserLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_fe_editor'
		);

		$this->fixture->checkAccess('image_upload', array(
			'showUid' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('deleted' => 1)
			)
		));
	}

	public function test404HeaderIsSentWhenCheckAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessage() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess('image_upload', array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				)
			));
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		$this->assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testCheckAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForANewObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('image_upload', array('showUid' => 0));
	}

	public function testCheckAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('image_upload', array(
			'showUid' => $this->dummyObjectUid
		));
	}

	public function testCheckAccessForImageUploadThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_access_denied'
		);

		$this->fixture->checkAccess(
			'image_upload', array('showUid' => $this->dummyObjectUid)
		);
	}

	public function test403HeaderIsSentWhenCheckAccessForImageUploadThrowsExceptionWithAccessDeniedMessage() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess(
				'image_upload', array('showUid' => $this->dummyObjectUid)
			);
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		$this->assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testCheckAccessForImageUploadDoesNotThrowAnExceptionIfTheObjectExistsAndTheUserIsOwner() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess(
			'image_upload', array('showUid' => $this->dummyObjectUid)
		);
	}


	////////////////////////////////////////////////////
	// Tests concerning access to the my-objects view.
	////////////////////////////////////////////////////

	public function testCheckAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('my_objects', array(
			'delete' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('deleted' => 1)
			)
		));
	}

	public function testCheckAccessForMyObjectsThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidObjectToDeleteUidAndAUserLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_fe_editor'
		);

		$this->fixture->checkAccess('my_objects', array(
			'delete' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('deleted' => 1)
			)
		));
	}

	public function test404HeaderIsSentWhenCheckAccessForMyObjectsThrowsExceptionWithObjectDoesNotExistMessage() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess('my_objects', array(
				'delete' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				)
			));
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		$this->assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testCheckAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('my_objects', array('delete' => 0));
	}

	public function testCheckAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageWhenNotLoggedInUserAttemptsToDeleteAnObject() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess(
			'my_objects', array('delete' => $this->dummyObjectUid)
		);
	}

	public function testCheckAccessForMyObjectsThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToDeleteAnObjectHeDoesNotOwn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_access_denied'
		);

		$this->fixture->checkAccess(
			'my_objects', array('delete' => $this->dummyObjectUid)
		);
	}

	public function test403HeaderIsSentWhenCheckAccessForMyObjectsThrowsExceptionWithAccessDeniedMessage() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess(
				'my_objects', array('delete' => $this->dummyObjectUid)
			);
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		$this->assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testCheckAccessForMyObjectsDoesNotThrowAnExceptionIfTheObjectToDeleteExistsAndTheOwnerIsLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess(
			'my_objects', array('delete' => $this->dummyObjectUid)
		);
	}

	public function testCheckAccessForMyObjectsDoesNotThrowAnExceptionIfNoObjectToDeleteIsSetAndTheUserIsLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('my_objects', array('delete' => 0));
	}


	////////////////////////////////////////////////
	// Tests concerning access to the single view.
	////////////////////////////////////////////////

	public function testCheckAccessForSingleViewThrowsExceptionWithPleaseLoginMessageForANewObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('single_view', array());
	}

	public function testCheckAccessForSingleViewDoesNotThrowAnExceptionIfTheObjectIsNewAndTheUserIsLoggedIn() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('single_view', array());
	}


	//////////////////////////////////////////////
	// Test concerning access to any other view.
	//////////////////////////////////////////////

	public function testCheckAccessForOtherViewDoesNotThrowAnException() {
		$this->fixture->checkAccess('other', array());
	}
}
?>