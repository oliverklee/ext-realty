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
 * This script acts as a dispatcher for AJAX requests.
 *
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
tslib_eidtools::connectDB();
tslib_eidtools::initTCA();

$cityUid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('city');
$showWithNumbers = (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('type') == 'withNumber');
if ($cityUid > 0) {
    $output = tx_realty_Ajax_DistrictSelector::render($cityUid, $showWithNumbers);
} else {
    $output = '';
}

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');
header('Content-Length: ' . strlen($output));

echo $output;
