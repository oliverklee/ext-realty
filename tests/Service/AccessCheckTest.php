<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Service_AccessCheckTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_pi1_AccessCheck
	 */
	private $fixture = NULL;
	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var int UID of the dummy object
	 */
	private $dummyObjectUid;

	protected function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();

		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->dummyObjectUid = $this->testingFramework->createRecord(
			'tx_realty_objects'
		);

		$this->fixture = new tx_realty_pi1_AccessCheck();
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	//////////////////////////////////////////////
	// Tests concerning access to the FE editor.
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function checkAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('fe_editor', array(
			'showUid' => $this->testingFramework->createRecord(
				'tx_realty_objects', array('deleted' => 1)
			)
		));
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidUidAndAUserLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_fe_editor'
		);

		$this->fixture->checkAccess('fe_editor', array(
			'showUid' => $this->testingFramework->createRecord(
				'tx_realty_objects', array('deleted' => 1)
			)
		));
	}

	/**
	 * @test
	 */
	public function header404IsSentWhenCheckAccessForFeEditorThrowsExceptionWithObjectDoesNotExistMessage() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess('fe_editor', array(
				'showUid' => $this->testingFramework->createRecord(
					'tx_realty_objects', array('deleted' => 1)
				)
			));
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		self::assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForNewObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_access_denied'
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function header403IsSentWhenCheckAccessForFeEditorThrowsExceptionWithAccessDeniedMessage() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess(
				'fe_editor', array('showUid' => $this->dummyObjectUid)
			);
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		self::assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorDoesNotThrowAnExceptionIfTheObjectExistsAndTheUserIsLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->dummyObjectUid,
			array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess(
			'fe_editor', array('showUid' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorDoesNotThrowAnExceptionIfTheNonPublishedObjectExistsAndTheUserIsLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			'tx_realty_objects',
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

	/**
	 * @test
	 */
	public function checkAccessForFeEditorDoesNotThrowAnExceptionIfTheObjectIsNewAndTheUserIsLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getLoadedTestingModel(array());
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorForLoggedInUserWithNoObjectsLeftToEnterThrowsExceptionWithNoObjectsLeftMessage() {
		/** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMock('tx_realty_Model_FrontEndUser', array('getNumberOfObjects'));
		$user->setData(array('tx_realty_maximum_objects' => 1));
		$user->expects(self::any())->method('getNumberOfObjects')->will(self::returnValue(1));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_no_objects_left'
		);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorForLoggedInUserWithNoObjectsLeftToEnterAndEditingAnExistingObjectDoesNotThrowException() {
		/** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMock('tx_realty_Model_FrontEndUser', array('getNumberOfObjects'));
		$user->setData(array('tx_realty_maximum_objects' => 1));
		$user->expects(self::any())->method('getNumberOfObjects')->will(self::returnValue(1));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$objectUid = $this->testingFramework->createRecord(
			'tx_realty_objects', array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess('fe_editor', array('showUid' => $objectUid));
	}

	/**
	 * @test
	 */
	public function checkAccessForFeEditorForLoggedInUserWithObjectsLeftToEnterThrowsNoException() {
		/** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMock('tx_realty_Model_FrontEndUser', array('getNumberOfObjects'));
		$user->setData(array('tx_realty_maximum_objects' => 1));
		$user->expects(self::any())->method('getNumberOfObjects')->will(self::returnValue(0));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('fe_editor', array('showUid' => 0));
	}


	/////////////////////////////////////////////////
	// Tests concerning access to the image upload.
	/////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function checkAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('image_upload', array(
			'showUid' => $this->testingFramework->createRecord(
				'tx_realty_objects', array('deleted' => 1)
			)
		));
	}

	/**
	 * @test
	 */
	public function checkAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessageForZeroObjectUidAndAUserLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_image_upload'
		);

		$this->fixture->checkAccess('image_upload', array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function checkAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidObjectUidAndAUserLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_fe_editor'
		);

		$this->fixture->checkAccess('image_upload', array(
			'showUid' => $this->testingFramework->createRecord(
				'tx_realty_objects', array('deleted' => 1)
			)
		));
	}

	/**
	 * @test
	 */
	public function header404IsSentWhenCheckAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessage() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess('image_upload', array(
				'showUid' => $this->testingFramework->createRecord(
					'tx_realty_objects', array('deleted' => 1)
				)
			));
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		self::assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForNewObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('image_upload', array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function checkAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('image_upload', array(
			'showUid' => $this->dummyObjectUid
		));
	}

	/**
	 * @test
	 */
	public function checkAccessForImageUploadThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_access_denied'
		);

		$this->fixture->checkAccess(
			'image_upload', array('showUid' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function header403IsSentWhenCheckAccessForImageUploadThrowsExceptionWithAccessDeniedMessage() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess(
				'image_upload', array('showUid' => $this->dummyObjectUid)
			);
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		self::assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForImageUploadDoesNotThrowAnExceptionIfTheObjectExistsAndTheUserIsOwner() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			'tx_realty_objects',
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

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('my_objects', array(
			'delete' => $this->testingFramework->createRecord(
				'tx_realty_objects', array('deleted' => 1)
			)
		));
	}

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidObjectToDeleteUidAndAUserLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_noResultsFound_fe_editor'
		);

		$this->fixture->checkAccess('my_objects', array(
			'delete' => $this->testingFramework->createRecord(
				'tx_realty_objects', array('deleted' => 1)
			)
		));
	}

	/**
	 * @test
	 */
	public function header404IsSentWhenCheckAccessForMyObjectsThrowsExceptionWithObjectDoesNotExistMessage() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess('my_objects', array(
				'delete' => $this->testingFramework->createRecord(
					'tx_realty_objects', array('deleted' => 1)
				)
			));
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		self::assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('my_objects', array('delete' => 0));
	}

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageWhenNotLoggedInUserAttemptsToDeleteAnObject() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess(
			'my_objects', array('delete' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToDeleteAnObjectHeDoesNotOwn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_access_denied'
		);

		$this->fixture->checkAccess(
			'my_objects', array('delete' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function header403IsSentWhenCheckAccessForMyObjectsThrowsExceptionWithAccessDeniedMessage() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		try {
			$this->fixture->checkAccess(
				'my_objects', array('delete' => $this->dummyObjectUid)
			);
		} catch (tx_oelib_Exception_AccessDenied $exception) {
		}

		self::assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsDoesNotThrowAnExceptionIfTheObjectToDeleteExistsAndTheOwnerIsLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->dummyObjectUid,
			array('owner' => $user->getUid())
		);

		$this->fixture->checkAccess(
			'my_objects', array('delete' => $this->dummyObjectUid)
		);
	}

	/**
	 * @test
	 */
	public function checkAccessForMyObjectsDoesNotThrowAnExceptionIfNoObjectToDeleteIsSetAndTheUserIsLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('my_objects', array('delete' => 0));
	}


	////////////////////////////////////////////////
	// Tests concerning access to the single view.
	////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function checkAccessForSingleViewThrowsExceptionWithPleaseLoginMessageForNewObjectIfNoUserIsLoggedIn() {
		$this->setExpectedException(
			'tx_oelib_Exception_AccessDenied', 'message_please_login'
		);

		$this->fixture->checkAccess('single_view', array());
	}

	/**
	 * @test
	 */
	public function checkAccessForSingleViewDoesNotThrowAnExceptionIfTheObjectIsNewAndTheUserIsLoggedIn() {
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')->getNewGhost();
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->checkAccess('single_view', array());
	}


	//////////////////////////////////////////////
	// Test concerning access to any other view.
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function checkAccessForOtherViewDoesNotThrowAnException() {
		$this->fixture->checkAccess('other', array());
	}
}