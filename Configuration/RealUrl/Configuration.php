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
 * This class adds a RealURL configuration.
 *
 * @package    TYPO3
 * @subpackage tx_realty
 *
 * @author     Oliver Klee <typo3-coding@oliverklee.de>
 */
class Tx_Realty_Configuration_RealUrl_Configuration {
	/**
	 * Adds RealURL configuration.
	 *
	 * @param mixed[][] $parameters the RealUrl configuration to modify
	 *
	 * @return mixed[][] the modified RealURL configuration
	 */
	public function addConfiguration(array $parameters) {
		$preVariables = array(
			array(
				'GETvar' => 'no_cache',
				'valueMap' => array(
					'no_cache' => 1,
				),
				'noMatch' => 'bypass',
			),
		);

		$paginationGetVariable = array(
			'GETvar' => 'tx_realty_pi1[pointer]',
			'valueMap' => array(
				'1' => '0',
				'2' => '1',
				'3' => '2',
				'4' => '3',
				'5' => '4',
				'6' => '5',
				'7' => '6',
				'8' => '7',
				'9' => '8',
				'10' => '9',
				'11' => '10',
				'12' => '11',
				'13' => '12',
				'14' => '13',
				'15' => '14',
				'16' => '15',
				'17' => '16',
				'18' => '17',
				'19' => '18',
				'20' => '19',
				'21' => '20',
				'22' => '21',
				'23' => '22',
				'24' => '23',
				'25' => '24',
				'26' => '25',
				'27' => '26',
				'28' => '27',
				'29' => '28',
				'30' => '29',
				'31' => '30',
			),
			'noMatch' => 'bypass',
		);

		$realtyObjectGetVariable = array(
			'GETvar' => 'tx_realty_pi1[showUid]',
			'lookUpTable' => array(
				'table' => 'tx_realty_objects',
				'id_field' => 'uid',
				'alias_field' => 'title',
				'addWhereClause' => ' AND NOT deleted',
				'useUniqueCache' => TRUE,
				'useUniqueCache_conf' => array(
					'strtolower' => 1,
					'spaceCharacter' => '-',
				),
				'autoUpdate' => TRUE,
			),
		);

		return array_merge_recursive(
			$parameters['config'],
			array(
				'preVars' => $preVariables,
				'postVarSets' => array(
					'_DEFAULT' => array(
						'immo' => array($paginationGetVariable, $realtyObjectGetVariable),
					),
				),
			)
		);
	}
}