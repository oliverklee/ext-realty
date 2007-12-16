<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_domdocument_converter' for the 'realty' extension.
 * It converts DOMDocuments of OpenImmo data to arrays which have the columns of
 * the database table 'tx_realty_objects' as keys.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_domdocument_converter {
	/**
	 * Associates database column names with their correspondings used in
	 * OpenImmo records.
	 * In OpenImmo, nested tags are used to describe values. The beginning of
	 * these tags is the same for all columns in this array:
	 * '<openimmo><anbieter><immobilie>...</immobilie></anbieter></openimmo>'.
	 * The last tags, each property's name and the category where to find this
	 * name differ.
	 * The database column names are the keys of the outer array, their values
	 * are arrays which have the category as key and the property as value.
	 */
	private $propertyArray = array(
		// OpenImmo tag for 'starttime' could possibly be 'beginn_bietzeit'.
		'starttime' => array('bieterverfahren' => 'beginn_angebotsphase'),
		'endtime' => array('bieterverfahren' => 'ende_bietzeit'),
		'object_number' => array('verwaltung_techn' => 'objektnr_extern'),
		'title' => array('freitexte' => 'objekttitel'),
		'street' => array('geo' => 'strasse'),
		'zip' => array('geo' => 'plz'),
		'city' => array('geo' => 'ort'),
		'district' => array('geo' => 'regionaler_zusatz'),
		'number_of_rooms' => array('flaechen' => 'anzahl_zimmer'),
		'living_area' => array('flaechen' => 'wohnflaeche'),
		'total_area' => array('flaechen' => 'gesamtflaeche'),
		'rent_excluding_bills' => array('preise' => 'kaltmiete'),
		'extra_charges' => array('preise' => 'nebenkosten'),
		'heating_included' => array('preise' => 'heizkosten_enthalten'),
		'deposit' => array('preise' => 'kaution'),
		'provision' => array('preise' => 'aussen_courtage'),
		// OpenImmo tag for 'usable_from' could possibly be 'abdatum'.
		'usable_from' => array('verwaltung_objekt' => 'verfuegbar_ab'),
		'buying_price' => array('preise' => 'kaufpreis'),
		'year_rent' => array('preise' => 'mieteinnahmen_ist'),
		'rented' => array('verwaltung_objekt' => 'vermietet'),
		'floor' => array('geo' => 'etage'),
		'floors' => array('geo' => 'anzahl_etagen'),
		'bedrooms' => array('flaechen' => 'anzahl_schlafzimmer'),
		'bathrooms' => array('flaechen' => 'anzahl_badezimmer'),
		'pets' => array('verwaltung_objekt' => 'haustiere'),
		'construction_year' => array('zustand_angaben' => 'baujahr'),
		'balcony' => array('flaechen' => 'anzahl_balkon_terrassen'),
		'garden' => array('ausstattung' => 'gartennutzung'),
		'accessible' => array('ausstattung' => 'rollstuhlgerecht'),
		'description' => array('freitexte' => 'objektbeschreibung'),
		'equipment' => array('freitexte' => 'ausstatt_beschr'),
		'location' => array('freitexte' => 'lage'),
		'misc' => array('freitexte' => 'sonstige_angaben'),
		'openimmo_obid' => array('verwaltung_techn' => 'openimmo_obid'),
		'contact_person' => array('kontaktperson' => 'name'),
		'contact_email' => array('kontaktperson' => 'email_zentrale')
	);

	/** data which is the same for all realty records of one DOMDocument */
	private $universalRealtyData = array();

	/** imported data of a realty record */
	private $importedData = array();

	/**
	 * Handles the conversion of a DOMDocument and returns the realty records
	 * found in the DOMDocument as values of an array. Each of this values is an
	 * array with column names like in the database table 'tx_realty_objects' as
	 * keys and their corresponding values fetched from the DOMDocument.
	 * As images need to be inserted to a separate database table, all image
	 * data is stored in an inner array in the element 'images' of each record.
	 * The returned array is empty if the given DOMDocument could not be
	 * converted.
	 *
	 * @param	DOMDocument		data to convert, must not be null
	 *
	 * @return	array		data of each realty record, may be empty
	 */
	public function getConvertedData(DOMDocument $domDocument) {
		$this->fetchUniversalData($domDocument);

		$result = array();
		$singleRealtyRecords = $this->isolateRealtyRecords($domDocument);

		foreach ($singleRealtyRecords as $record) {
			$realtyRecordArray = $this->getRealtyArrayOfDomNode($record);
			$this->addUniversalData(&$realtyRecordArray);

			$result[] = $realtyRecordArray;
		}

		return $result;
	}

	/**
	 * Appends realty data found in $this->universalRealtyData to the given
	 * array. This data is the same for all realty records in one DOMDocument
	 * and is fetched by $this->fetchUniversalData().
	 *
	 * @param	array		realty data, may be empty
	 */
	private function addUniversalData(array &$realtyDataArray) {
		$realtyDataArray = array_merge(
			$realtyDataArray,
			$this->universalRealtyData
		);
	}

	/**
	 * Fetches data from a DOMDocument which is equal for the whole set of
	 * realty records in this DOMDocument and stores it to
	 * $this->universalRealtyData.
	 *
	 * @param	DOMDocument		realty records to import, must not be null
	 */
	private function fetchUniversalData(DOMDocument $rawDomDocument) {
		$openImmoNode = $this->findBeginningOpenImmoNode($rawDomDocument);
		if (!$openImmoNode) {
			return;
		}

		$this->universalRealtyData = $this->fetchEmployerAndAnid($openImmoNode);
	}

	/**
	 * Fetches 'employer' and 'openimmo_anid' from the DOMNode named 'openimmo'
	 * of an OpenImmo record and returns them in an array. These nodes must only
	 * occur once in an OpenImmo record.
	 *
	 * @param 	DOMNode		node named 'openimmo' of an OpenImmo record, must
	 * 						not be null
	 *
	 * @return	array		contains the elements 'employer' and
	 * 						'openimmo_anid', will be empty if the nodes were not
	 * 						found
	 */
	private function fetchEmployerAndAnid(DOMNode $openImmoNode) {
		$result = array();

		foreach (array(
			'firma' => 'employer',
			'openimmo_anid' => 'openimmo_anid'
		) as $grandchild => $columnName) {
			$node = $this->findFirstGrandchild(
				$openImmoNode,
				'anbieter',
				$grandchild
			);
			$this->addElementToArray(
				&$result,
				$columnName,
				$node->nodeValue
			);
		}

		return $result;
	}

	/**
	 * Splits the DOMDocument as an OpenImmo record can contain several realty
	 * records. Returns an array of records.
	 *
	 * @param	DOMDocument		realty records to import, must not be null
	 *
	 * @return	array		DOMNodes of single realty records as elements, may
	 * 						be empty
	 */
	protected function isolateRealtyRecords(DOMNode $rawDomDocument) {
		$result = array();

		$openImmoNode = $this->findBeginningOpenImmoNode($rawDomDocument);
		if ($openImmoNode) {
			$nodeWithRecordNodes = $this->findFirstChild($openImmoNode, 'anbieter');
			if ($nodeWithRecordNodes) {
				$result = $this->fetchRealtyNodes($nodeWithRecordNodes);
			}
		}

		return $result;

	}

	/**
	 * Substitutes XML namespaces from a node name and returns the name.
	 *
	 * @param	DOMNode		node with a name, may be null
	 *
	 * @return	string		node name without namespaces, may be empty
	 */
	protected function getNodeName($domNode) {
		if (!is_object($domNode)) {
			return '';
		}

		$rawNodeName = $domNode->nodeName;
		$position = strpos($rawNodeName, ':');

		if ($position === false) {
			$nodeName = $rawNodeName;
		} else {
			$nodeName = substr($rawNodeName, $position + 1);
		}

		return $nodeName;
	}

	/**
	 * Fetches DOMNodes containing a whole realty record. These nodes must be
	 * child nodes of the given node and named 'immobilie'.
	 *
	 * @param	DOMNode		realty records, must not be null
	 *
	 * @return	array		DOMNodes containing the realty records, empty if no
	 * 						record could be fetched
	 */
	private function fetchRealtyNodes(DOMNode $realtyRecords) {
		$result = array();
		$nodeOfSingleRecord = null;
		$currentChild = $realtyRecords->firstChild;

		do {
			if ($this->getNodeName($currentChild) == 'immobilie') {
				$result[] = $currentChild;
			}
			$currentChild = $currentChild->nextSibling;
		} while ($currentChild);

		return $result;
	}

	/**
	 * Converts a DOMNode named 'immobilie'. The result is an array. It contains
	 * the values, fetched from the DOMNode. The keys are the same as the column
	 * names of the database table 'tx_realty_objects'.
	 * The result is an empty array if the given DOMNode is of an invalid
	 * format.
	 *
	 * @param	DOMNode		data of a realty object, must not be null
	 *
	 * @return	array		data of the realty object, empty if the DOMNode is
	 * 						not convertible
	 */
	private function getRealtyArrayOfDomNode(DOMNode $realtyData) {
		$this->fetchNodeValues($realtyData);
		$this->fetchImages($realtyData);
		$this->fetchEquipmentAttributes($realtyData);
		$this->fetchCategoryAttributes($realtyData);
		$this->fetchState($realtyData);
		$this->fetchAction($realtyData);
		$this->fetchGaragePrice($realtyData);

		return $this->importedData;

	}

	/**
	 * Fetches node values from the DOMNode named 'immobilie' and stores them
	 * with their corresponding database column names as keys in
	 * $this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie', must not be null
	 */
	private function fetchNodeValues(DOMNode $openImmoNode) {
		foreach ($this->propertyArray as $databaseColumnName => $openImmoNames) {
			$currentDomNode = $this->findFirstGrandchild(
				$openImmoNode,
				key($openImmoNames),
				implode($openImmoNames)
			);
			$this->addImportedData(
				$databaseColumnName,
				$currentDomNode->nodeValue
			);
		}
		$this->addComission($openImmoNode);
		$this->appendStreetNumber($openImmoNode);
	}

	/**
	 * Fetches 'innen_courtage' from the DOMNode named 'immobilie' and stores it
	 * to the array element 'provision' of $this->importedData. If the key
	 * 'provision' already exists, the sum of 'innen_courtage' and the current
	 * value of 'provision' is stored in this element. The current value is
	 * usually the value fetched from 'aussen_courtage' from the DOMNode.
	 *
	 * @param	DOMNode		node named 'immobilie', must not be null
	 */
	private function addComission(DOMNode $openImmoNode) {
		$innenCourtageNode = $this->findFirstGrandchild(
			$openImmoNode,
			'preise',
			'innen_courtage'
		);
		if (!$this->importedData['provision']) {
			$this->addImportedData('provision',	$innenCourtageNode->nodeValue);
		} elseif (is_numeric($innenCourtageNode->nodeValue)
			&& is_numeric($this->importedData['provision'])
		) {
			$courtage = $innenCourtageNode->nodeValue
				+ $this->importedData['provision'];
			$this->addImportedData('provision',	$courtage);
		}
	}

	/**
	 * Fetches 'hausnummer' from the DOMNode named 'immobilie' and appends it to
	 * the string in the array element 'street' of $this->importedData. If
	 * 'street' is empty or does not exist, nothing is changed at all.
	 *
	 * @param	DOMNode		node named 'immobilie', must not be null
	 */
	private function appendStreetNumber(DOMNode $openImmoNode) {
		if (!$this->importedData['street']
			|| ($this->importedData['street'] == '')
		) {
			return;
		}

		$streetNumberNode = $this->findFirstGrandchild(
			$openImmoNode,
			'geo',
			'hausnummer'
		);
		if ($streetNumberNode) {
			$this->addImportedData(
				'street',
				$this->importedData['street'].' '.$streetNumberNode->nodeValue
			);
		}
	}

	/**
	 * Fetches information about images from $openImmoNode of an OpenImmo record
	 * and stores them as an inner array in $this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie', must not be null
	 */
	private function fetchImages(DOMNode $openImmoNode) {
		$appendix = $this->findFirstChild($openImmoNode, 'anhaenge');

		if ($appendix) {
			$this->addImportedData(
				'images',
				$this->createRecordsForImages($appendix)
			);
		}
	}

	/**
	 * Creates an array of image records for one realty record.
	 *
	 * @param	DOMNode		node named 'anhang', must not be null
	 *
	 * @return	array		image records, may be empty
	 */
	protected function createRecordsForImages(DOMNode $domElementAppendix) {
		$images = array();
		$domElementAppendix = $domElementAppendix->firstChild;

		while ($domElementAppendix) {
			$title = '';
			$fileName = '';

			if ($domElementAppendix->hasChildNodes()) {
				// Only one of the child nodes' name is 'anhangtitel'.
				foreach ($domElementAppendix->childNodes as $child) {
					if ($this->getNodeName($child) == 'anhangtitel') {
						$title = $child->nodeValue;
					}
					$nodeOfPath = $this->findFirstGrandchild(
						$domElementAppendix,
						'daten',
						'pfad'
					);
					if ($nodeOfPath) {
						$fileName = basename($nodeOfPath->nodeValue);
					}
				}
			}
			$domElementAppendix = $domElementAppendix->nextSibling;

			if ($fileName != '') {
				$images[] = array(
					'caption' => $title,
					'image' => $fileName
				);
			}
		}

		return $images;
	}

	/**
	 * Fetches attributes about equipment from the DOMNode named 'immobilie' of
	 * an OpenImmo record and stores them with their corresponding database
	 * column names as keys in $this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie' of an OpenImmo record
	 */
	private function fetchEquipmentAttributes(DOMNode $openImmoNode) {
		$rawAttributes = array();

		foreach (array(
			'stellplatzart',
			'serviceleistungen',
			'fahrstuhl',
			'kueche',
			'heizungsart'
		) as $grandchildName) {
			$nodeWithAttributes = $this->findFirstGrandchild(
				$openImmoNode,
				'ausstattung',
				$grandchildName
			);
			$rawAttributes[$grandchildName] = $this->fetchDomAttributes(
				$nodeWithAttributes
			);
		}

		if (!empty($rawAttributes['stellplatzart'])) {
			$this->addImportedData(
				'garage_type',
				ucwords(implode(', ', array_keys($rawAttributes['stellplatzart'])))
			);
		}

		if ($rawAttributes['serviceleistungen']['BETREUTES_WOHNEN']) {
			$this->addImportedData('assisted_living', 1);
		}

		if ($rawAttributes['fahrstuhl']['PERSONEN']
			|| $rawAttributes['fahrstuhl']['LASTEN']
		) {
			$this->addTrueToImportedData('elevator');
		}

		if ($rawAttributes['kueche']['EBK']) {
			$this->addTrueToImportedData('fitted_kitchen');
		}

		if (!empty($rawAttributes['heizungsart'])) {
			$this->addImportedData(
				'heating_type',
				ucwords(implode(', ', array_keys($rawAttributes['heizungsart'])))
			);
		}
	}

	/**
	 * Fetches attributes about 'objektkategorie' from the DOMNode named
	 * 'immobilie' of an OpenImmo record and stores them with their
	 * corresponding database column names as keys in this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie' of an OpenImmo record, must
	 * 						not be null
	 */
	private function fetchCategoryAttributes(DOMNode $openImmoNode) {
		$categoryNode = $this->findFirstChild($openImmoNode, 'objektkategorie');
		if (!$categoryNode) {
			return;
		}

		$this->fetchHouseType($categoryNode);

		$nodeWithAttributes = $this->findFirstChild($categoryNode, 'vermarktungsart');
		$objectTypeAttributes = $this->fetchDomAttributes($nodeWithAttributes);
		if (!empty($objectTypeAttributes)) {
			if (array_key_exists('KAUF', $objectTypeAttributes)) {
				$this->addTrueToImportedData('object_type');
			} else {
				$this->addImportedData('object_type', 0);
			}
		}

		$nodeWithAttributes = $this->findFirstChild($categoryNode, 'nutzungsart');
		$utilizationAttributes = $this->fetchDomAttributes($nodeWithAttributes);
		if (!empty($utilizationAttributes)) {
			$this->addImportedData(
				'utilization',
				ucwords(implode(', ', array_keys($utilizationAttributes)))
			);
		}
	}

	/**
	 * Fetches the 'Objektart' from the DOMNode named 'objektkategorie' of an
	 * OpenImmo record and stores it with the corresponding database column name
	 * 'house_type' as key in $this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie' of an OpenImmo record, must
	 * 						not be null
	 */
	private function fetchHouseType(DOMNode $categoryNode) {
		$nodeContainingAttributeNode = $this->findFirstChild(
			$categoryNode,
			'objektart'
		);

		// $this->findFirstChild() cannot be used here, because the attribute
		// nodes can have various names.
		$nodeWithAttributes = $nodeContainingAttributeNode->firstChild;
		// In case the first child is a dummy with a name starting with '#', the
		// next sibling is taken.
		if (strspn($this->getNodeName($nodeWithAttributes), '#') > 0) {
			$nodeWithAttributes = $nodeWithAttributes->nextSibling;
		}

		$value = $this->getNodeName($nodeWithAttributes);

		if ($value != '') {
			$attributes = $this->fetchDomAttributes($nodeWithAttributes);

			if (!empty($attributes)) {
				$value .= ': '.implode(',', array_values($attributes));
			}

			$this->addImportedData('house_type', ucwords($value));
		}
	}

	/**
	 * Fetches the attribute for 'garage_price' from the DOMNode named
	 * 'immobilie' of an OpenImmo record and stores them with the corresponding
	 * database column name as key in this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie' of an OpenImmo record, must
	 * 						not be null
	 */
	private function fetchGaragePrice(DOMNode $openImmoNode) {
		$nodeWithAttributes = $this->findFirstGrandchild(
			$openImmoNode,
			'preise',
			// 'stp_*' exists for each defined type of 'stellplatz'
			'stp_garage'
		);
		$attributes = $this->fetchDomAttributes($nodeWithAttributes);

		if (!empty($attributes)) {
			$this->addImportedData(
				'garage_rent',
				$attributes['stellplatzmiete']
			);
			$this->addImportedData(
				'garage_price',
				$attributes['stellplatzkaufpreis']
			);
		}
	}

	/**
	 * Fetches the attributes for 'state' from the DOMNode named 'immobilie' of
	 * an OpenImmo record and stores them with the corresponding database column
	 * name as key in this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie' of an OpenImmo record, must
	 * 						not be null
	 */
	private function fetchState(DOMNode $openImmoNode) {
		$nodeWithAttributes = $this->findFirstGrandchild(
			$openImmoNode,
			'zustand_angaben',
			'zustand'
		);
		$attributes = $this->fetchDomAttributes(
			$nodeWithAttributes
		);
		if (!empty($attributes)) {
			$this->addImportedData(
				'state',
				ucwords(implode(', ', array_values($attributes)))
			);
		}
	}

	/**
	 * Fetches the attribute 'aktion' from the DOMNode named 'immobilie' of an
	 * OpenImmo record and stores them with the corresponding database column
	 * name as key in this->importedData.
	 *
	 * @param	DOMNode		node named 'immobilie' of an OpenImmo record, must
	 * 						not be null
	 */
	private function fetchAction(DOMNode $openImmoNode) {
		$nodeWithAttributes = $this->findFirstGrandchild(
			$openImmoNode,
			'verwaltung_techn',
			'aktion'
		);
		$attributes = $this->fetchDomAttributes($nodeWithAttributes);
		$value = $this->getNodeName($nodeWithAttributes);

		if ($value) {
			if (!empty($attributes)) {
				$value .= ': '.implode(',', array_values($attributes));
			}
			$this->addImportedData('action', ucwords($value));
		}
	}

	/**
	 * Returns the first grandchild of a DOMNode specified by the child's and
	 * the grandchild's name. If one of these names can not be found, null is
	 * returned.
	 *
	 * @param	DOMNode		node where to find the grandchild, must not be null
	 * @param	string		name of child, must not be empty
	 * @param	string		name of grandchild, must not be empty
	 *
	 * @return	DOMNode		first grandchild with this name, null if it does not
	 * 						exist
	 */
	protected function findFirstGrandchild(
		DOMNode $domNode,
		$nameOfChild,
		$nameOfGrandchild
	) {
		$child = $this->findFirstChild($domNode, $nameOfChild);
		if ($child) {
			$result = $this->findFirstChild($child, $nameOfGrandchild);
		} else {
			$result = null;
		}

		return $result;
	}

	/**
	 * Returns the first child of a DOMNode specified by the its name. If the
	 * name can not be found, null is returned.
	 *
	 * @param	DOMNode		node where to find the grandchild, must not be null
	 * @param	string		name of child, must not be empty
	 *
	 * @return	DOMNode		first child with this name, null if it does not
	 * 						exist
	 */
	protected function findFirstChild(DOMNode $domNode, $nameOfChild) {
		if (!$domNode->hasChildNodes()) {
			return null;
		}

		$result = null;

		foreach ($domNode->childNodes as $child) {
			if ($this->getNodeName($child) == $nameOfChild) {
						$result = $child;
			}
		}

		return $result;
	}

	/**
	 * Returns the node named 'openimmo' from $realtyData or null if this node
	 * was not found.
	 *
	 * @param	DOMDocument		OpenImmo record
	 *
	 * @return	DOMNode		node named 'openimmo', null if this node was not
	 * 						found
	 */
	private function findBeginningOpenImmoNode(DOMDocument $realtyData) {
		if ($this->getNodeName($realtyData) == 'openimmo') {
			$openImmoNode = $realtyData;
		} elseif ($this->getNodeName($realtyData->firstChild) == 'openimmo' ) {
			$openImmoNode = $realtyData->firstChild;
		} elseif ($this->getNodeName($realtyData->firstChild->nextSibling)
			== 'openimmo'
		) {
			$openImmoNode = $realtyData->firstChild->nextSibling;
		} else {
			$openImmoNode = null;
		}

		return $openImmoNode;
	}

	/**
	 * Adds a new element $value to the array $arrayExpand, using the key $key.
	 * The element will not be added if is null.
	 *
	 * @param	array		into which the new element should be inserted, may
	 *						be empty
	 * @param	string		key to insert
	 * @param	mixed		value to insert
	 */
	protected function addElementToArray(array &$arrayToExpand, $key, $value) {
		if (!is_null($value)) {
			$arrayToExpand[$key] = $value;
		}
	}

	/**
	 * Adds an element to $this->importedData.
	 *
	 * @param	string		key to insert
	 * @param	mixed		value to insert
	 */
	private function addImportedData($key, $value) {
		$this->addElementToArray(&$this->importedData, $key, $value);
	}

	/**
	 * Adds an element with the value 1 to $this->importedData.
	 *
	 * @param	string		key to insert
	 */
	private function addTrueToImportedData($key) {
		$this->addElementToArray(&$this->importedData, $key, 1);
	}

	/**
	 * Fetches an attribute from a given node and returns name/value pairs as an
	 * array. If there are no attributes, the returned array will be empty.
	 *
	 * @param	DOMNode		node from where to fetch the attribute, may be null
	 *
	 * @return	array		attributes and attribute values, empty if there are
	 * 						no attributes
	 */
	protected function fetchDomAttributes($nodeWithAttributes) {
		if (!$nodeWithAttributes) {
			return array();
		}

		$fetchedValues = array();
		$attributeToFetch = $nodeWithAttributes->attributes;
		if ($attributeToFetch) {
			foreach ($attributeToFetch as $domObject) {
				$fetchedValues[$domObject->name] =
					$domObject->value;
			}
		}

		return $fetchedValues;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_domdocument_converter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_domdocument_converter.php']);
}

?>
