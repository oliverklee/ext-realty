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

defined('TYPO3_cliMode') or die('You cannot run this script directly!');

setlocale(LC_NUMERIC, 'C');

/**
 * This class provides access via command-line interface.
 *
 * To run this script, use the following command in a console: '/[absolute path
 * of the TYPO3 installation]/typo3/cli_dispatch.phpsh openImmoImport'.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cli
{
    /**
     * Calls the OpenImmo importer.
     *
     * @return void
     */
    public function main()
    {
        try {
            /** @var tx_realty_openImmoImport $importer */
            $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_realty_openImmoImport');
            echo $importer->importFromZip();
        } catch (Exception $exception) {
            echo $exception->getMessage() . LF . LF .
                $exception->getTraceAsString() . LF . LF;
        }
    }
}

/** @var tx_realty_cli $cli */
$cli = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_realty_cli');
$cli->main();
