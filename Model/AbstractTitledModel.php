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
 * This class represents a titled model.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
abstract class tx_realty_Model_AbstractTitledModel extends Tx_Oelib_Model {
	/**
	 * @var string
	 */
	protected $titleFieldName = 'title';

	/**
	 * @var bool
	 */
	protected $allowEmptyTitle = FALSE;

	/**
	 * Gets this model's title.
	 *
	 * @return string the model's title, might be empty
	 */
	public function getTitle() {
		return $this->getAsString($this->titleFieldName);
	}

	/**
	 * Sets this model's title.
	 *
	 * @param string $title the title to set, may be empty
	 *
	 * @return void
	 */
	public function setTitle($title) {
		if (!$this->allowEmptyTitle && ($title === '')) {
			throw new InvalidArgumentException('$title must not be empty.', 1421163107);
		}

		$this->setAsString($this->titleFieldName, $title);
	}
}