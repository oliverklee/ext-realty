<?php

/**
 * This script acts as a dispatcher for AJAX requests.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

$cityUid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('city');
$showWithNumbers = (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('type') === 'withNumber');
if ($cityUid > 0) {
    $output = \tx_realty_Ajax_DistrictSelector::render($cityUid, $showWithNumbers);
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
