<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2011 Oliver Klee <typo3-coding@oliverklee.de>
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

/**
 * Class tx_realty_pi1_DocumentsView for the "realty" extension.
 *
 * This class represents a view that contains the PDF documents attached to an
 * object.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_DocumentsView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the rendered view.
	 *
	 * @param array $piVars
	 *        piVars, must contain the key "showUid" with a valid realty object
	 *        UID as value
	 *
	 * @return string HTML for this view or an empty string if the realty object
	 *                with the provided UID has no documents
	 */
	public function render(array $piVars = array()) {
		$documents = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($piVars['showUid'])
			->getDocuments();
		if ($documents->isEmpty()) {
			return '';
		}

		$result = '';
		foreach ($documents as $document) {
			$link = $this->cObj->typoLink(
				htmlspecialchars($document->getTitle()),
				array(
					'parameter' => tx_realty_Model_Document::UPLOAD_FOLDER .
						$document->getFileName(),
				)
			);

			$this->setMarker('document_file', $link);
			$result .= $this->getSubpart('DOCUMENT_ITEM');
		}

		$this->setSubpart('DOCUMENT_ITEM', $result);

		return $this->getSubpart('FIELD_WRAPPER_DOCUMENTS');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/DocumentsView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/DocumentsView.php']);
}
?>