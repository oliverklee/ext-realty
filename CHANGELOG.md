# Change log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).

## x.y.z

### Added
- Add more accessibility-related fields to the FE editor (#18, #60)
- Display the rent with heating costs (#42)
- Import the rent with heating costs (#41)
- Add a field for the rent with heating costs (#40)
- Import the new accessibility-related fields (#39, #17)
- Add and display more accessibility-related fields (#16, #38)
- Also import the deposit in text form (#35, #36)
- Use the static SQL filter in the filter form (#34)
- Use additional WHERE clauses in countByCity and countByDistrict (#33)
- Make more fields available in the list view (#31)
- switch from ext_autoload to Composer autoloading (#9)
- Composer script for PHP linting (#5)
- add TravisCI builds
- configure the BE search fields
- import and display more heating types
- for full import, delete non-imported objects
- add RealtyObjectMapper::deleteByAnidWithExceptions
- add RealtyObjectMapper::findByAnid
- add a configuration for PDF rendering of the manual
- add a composer.json
- add a RealURL configuration for the search parameters
- provide a RealURL auto configuration file
- make the TCA cacheable

### Changed
- Convert the FE editor JavaScript to jQuery (#73)
- Automatically include the mkforms static TypoScript template (#71)
- !!! Switch from PrototypeJS to jQuery (#69)
- !!! Switch from ameos_formidable to mkforms (#62)
- Convert the image upload form configuration from XML to TypoScript (#59)
- Convert the form configuration from XML to TypoScript (#57)
- Also allow oelib 2.0.x (#54)
- Also check for the PID when deleting imported objects (#43)
- Import both possible fields for the number of balconies (#37)
- Render the teaser as an RTE field in the FE (#32)
- Update to PHPUnit 5.3 (#28)
- Implement the updated geocoding interface (#26)
- require ameos_formidable in Composer mode (#14)
- require static_info_tables >= 6.3.7 (#3)
- convert the locallang files to XLIFF (#1)
- move the extension to GitHub
- allow oelib up to 1.9.99
- set 7.9.99 as maximum TYPO3 version
- require static_info_tables >= 6.2.0
- require TYPO3 6.2 and PHP 5.5
- move all includes and locallang loading into the classes
- move the TypoScript to Configuration/TypoScript
- reformat the code to PSR-2
- replace "language" in the PHPDoc
- clean up the OpenImmo import classes

### Deprecated

### Removed
- Drop support for TYPO3 <= 6.2
- Only depend on the Composer formidable on Travis (#25)
- Drop the incorrect TYPO3 Core license headers  (#24)
- drop the feature of importing into different folders by file name
- drop old update functions
- drop the css_styled_content requirement
- drop version-specific code and class_exists
- drop the old XCLASS footers
- drop global variables from pi1_wizicon

### Fixed
- Re-add missing district drop-down to the FE editor (#72)
- Adapt the FE image upload to mkforms (#67)
- Properly encode alert texts (#66)
- Remove double labels for radiobutton groups (#65)
- Bring back the localized labels in the FE image upload (#64)
- Update the composer package name of static-info-tables (#53)
- Add the missing required PHP extensions to the composer.json (#51)
- Fix crash in the FE editor (#49)
- Properly make hidden list view parts visible again if needed (#44, #48)
- Fix the paths to the locallang files for the FE (#30)
- Re-add ext_autoload.php for compatibility with TYPO3 6.2 (#29)
- Provide cli_dispatch.phpsh for 8.7 on Travis (#23)
- Install Composer version of ameos/formidable from GitHub (#20)
- send email after OpenImmo import again (#15)
- reliably create the upload folder on installation (#13)
- match only the first 4 characters of the OpenImmo ANID for a full sync
- match only the first 4 characters when matching FE users by ANID
- update the ameos_formidable URL in the manual
- heating types are set even if "false" in OpenImmo
- make realty compatible with PHP 7
- make realty compatible with MySQL in strict mode
- remove all references to t3lib
- fix the static-info-tables dependency in the composer.json
- use MathUtility instead of t3lib_utility_Math
- explicitly provide the configuration check namespaces
- stop calling deprecated config check methods
- submitting the search keeps the current pagination
- update .htaccess to be Apache 2.4 compatible
- display the provision as a free-text field
- crash when deleting and updating an object in the same import
- test crash in the RealtyMapper tests with an empty DB
- update the FORMidable download link
- download link to FORMidable is split across several lines
- missing comma in tca.php
- fix the casing of all tx_oelib usages to Tx_Oelib
- replace the deprecated bigDoc
- rename tx_oelib_Exception_AccessDenied to Tx_Oelib_Exception_AccessDenied
- change tx_oelib_db to Tx_Oelib_Db
- change tx_oelib_configcheck to Tx_Oelib_ConfigCheck
- change tx_oelib_configurationProxy to Tx_Oelib_ConfigurationProxy
- change t3lib_refindex to \TYPO3\CMS\Core\Database\ReferenceIndex
- change t3lib_l10n_parser_Llxml to LocallangXmlParser
- rename tx_oelib_Model_FrontEndUser to Tx_Oelib_Model_FrontEndUser
- change tslib_cObj to ContentObjectRenderer
- change tslib_pibase to \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
- change t3lib_mail_Message to TYPO3\CMS\Core\Mail\MailMessage
- change t3lib_cs to \TYPO3\CMS\Core\Charset\CharsetConverter
- change t3lib_cache_Manager to \TYPO3\CMS\Core\Cache\CacheManager
- change t3lib_basicFileFunctions to \TYPO3\CMS\Core\Utility\File\BasicFileUtility
- change tslib_fe to TypoScriptFrontendController
- change t3lib_beUserAuth to BackendUserAuthentication
- change t3lib_SCbase to \TYPO3\CMS\Backend\Module\BaseScriptClass
- change t3lib_extMgm to \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
- change t3lib_BEfunc into TYPO3\CMS\Backend\Utility\BackendUtility
- change t3lib_div into \TYPO3\CMS\Core\Utility\GeneralUtility
- update the static_info_tables version dependencies
- document the Prototype/jQuery clash
- document the new FORMidable in the upgrade notes
- remove the byte order mark from the ReST files
- fix the spelling of "comma-separated"
- rename tx_oelib_List to Tx_Oelib_List
- rename tx_oelib_Model to Tx_Oelib_Model
- rename tx_oelib_Interface_Sortable to Tx_Oelib_Interface_Sortable
- rename tx_oelib_Visibility_Tree to Tx_Oelib_Visibility_Tree
- rename tx_oelib_ViewHelper_Price to Tx_Oelib_ViewHelper_Price
- rename tx_oelib_Mapper_FrontEndUser to Tx_Oelib_Mapper_FrontEndUser
- rename tx_oelib_Mapper_Country to Tx_Oelib_Mapper_Country
- rename tx_oelib_Geocoding_Dummy to Tx_Oelib_Geocoding_Dummy
- rename tx_oelib_Geocoding_Calculator to Tx_Oelib_Geocoding_Calculator
- rename tx_oelib_Exception_NotFound to Tx_Oelib_Exception_NotFound
- rename tx_oelib_Exception_EmptyQueryResult to Tx_Oelib_Exception_EmptyQueryResult
- rename tx_oelib_Exception_Database to Tx_Oelib_Exception_Database
- rename tx_oelib_Interface_Geo to Tx_Oelib_Interface_Geo
- rename Tx_Oelib_HeaderProxyFactory to Tx_Oelib_HeaderProxyFactory
- rename tx_oelib_Time to Tx_Oelib_Time
- make the calls to static PHPUnit methods static

## 0.6.0

The [change log up to version 0.6.0](Documentation/changelog-archive.txt)
has been archived.
