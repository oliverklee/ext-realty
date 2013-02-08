<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2013 Bernd Schönbach <bernd@oliverklee.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF, 1);
$LANG->includeLLFile('EXT:realty/BackEnd/locallang.xml');

$SOBE = tx_oelib_ObjectFactory::make('tx_realty_BackEnd_Module');
$SOBE->init();

foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

echo $SOBE->render();
?>