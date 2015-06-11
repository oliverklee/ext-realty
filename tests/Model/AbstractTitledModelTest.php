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
 * @subpackage  tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_AbstractTitledModelTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_Model_AbstractTitledModel
	 */
	private $subject = NULL;

	protected function setUp() {
		$this->subject = $this->getMockForAbstractClass('tx_realty_Model_AbstractTitledModel');
	}

	/**
	 * @test
	 */
	public function classIsModel() {
		self::assertInstanceOf(
			'Tx_Oelib_Model',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getTitleWithNonEmptyTitleReturnsTitle() {
		$title = 'God save McQueen!';
		$this->subject->setData(array('title' => $title));

		self::assertSame(
			$title,
			$this->subject->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$title = 'The early bird needs coffee!';
		$this->subject->setTitle($title);

		self::assertSame(
			$title,
			$this->subject->getTitle()
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setTitleWithEmptyStringThrowsException() {
		$this->subject->setTitle('');
	}
}