# Change log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).

## x.y.z

### Added

### Changed

### Deprecated

### Removed

### Fixed

## 2.0.0

### Added
- !!! Use the default From: email address from the install tool (#173)
- Make the layout field an RTE field (#168)
- Add support for TYPO3 8.7 (#152)

### Changed
- Move the tests to the nimut testing framework (#170, #171)
- Run the functional tests in parallel to each other (#172)
- Use spaces for indenting the SQL file (#169)
- Require oelib >= 2.0 (#163)
- Move the CSS files to Resources/ (#160)
- Streamline ext_emconf.php (#159)
- Move the templates to Resources/ (#157)
- Move the FE images to Resources/ (#156)
- Move the record icon files to Resources/ (#154)
- Move the JavaScript file to Resources/ (#155)
- Move the flexforms configuration file to Configuration/ (#153)
- Clean up localconf.php (#150)

### Removed
- Drop the empty summaries from HTML tables (#149)
- Remove the "submit and stay" button (#147)
- Remove code for TYPO3 < 7.6 (#146)
- Remove usages of the GRSP (#145)
- Remove the old BE module (#143)
- Remove the CLI scripts (#144)
- Remove obsolete "checkbox" options from the TCA (#141)
- Remove the ext_autoload.php file (#139)
- Drop support for TYPO3 6.2 and require TYPO3 >= 7.6 (#138)

### Fixed
- Fix the CSS of the FE editor (#167)
- Make the content wizard icon visible in TYPO3 8.7 (#166)
- Don't log "no zips" as an import error (#165)
- Keep the BE language when using the BE module button (#164)
- Fix SQL errors in MySQL strict mode (#142)

## 1.1.0

### Added
- Status code for the OpenImmo import (#136)
- Scheduler task for the image cleanup (#135)
- Scheduler task for the OpenImmo import (#134)
- New back-end module for TYPO3 >= 7.6 (#131)
- Starter tests with nimut/testing-framework (#130)

### Changed
- Move the old tests to Tests/LegacyUnit/ (#121)

### Fixed
- Use the current composer names of static_info_tables (#129)
- Avoid conflict with the Bootstrap package for the Lightbox JavaScript (#128)
- Correct the wording of the energy performance certificate (#122)
- Add a conflict with a PHP-7.0-incompatible static_info_tables version (#119)

## 1.0.1

### Added
- Provide an API key for Google Maps (#47, #101)
- Auto-release to the TER (#98)

### Changed
- Simplify the TCA for images and documents (#116)
- Clean up the JavaScript (#114)

### Removed
- Disable the old BE module in TYPO3 8.7 (#97)

### Fixed
- Consistently use example.com for dummy URLs and emails (#115)
- Make the "add to favorites" button in the single view work again (#113)
- Conditionally hide/show the contact data in the TCEforms (#111)
- Allow an empty construction year again in the TCEforms (#110)
- Increase the allowed image and document size to 4 MB (#109)
- Fix most deprecation warnings in TYPO3 7.6 (#108)
- Fix code inspection warnings (#107)
- Also accept rent with heating as valid rent in the FE editor (#106)
- Don't HTML-encode the data from the FE editor on saving (#105)
- Show the "pages" selector in the flexforms for the "my objects" view (#104)
- Stop using the storage PID in the BE in TYPO3 >= 7.6 (#103)
- Fix the calls to IMAGE in TYPO3 7.6 and 8.7 (#99)

## 1.0.0

### Added
- Add compatibility with PHP 7.1 and 7.2 (#94)
- Run the unit tests on TravisCI (#11)
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
- Move the TCA class to Classes/ and namespace it (#85)
- Use an HTML5 document type for the HTML templates (#75)
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

### Removed
- Drop the update wizard that does nothing (#80)
- !!! Remove the possibility of adding new districts in the FE editor (#74)
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
- Make the tests URL-encoding agnostic (#91)
- Fix the casing of used classes and methods (#87)
- Use a fixed SIM_EXEC_TIME in the tests (#86)
- Replace deprecated code usages in 6.2 (#81)
- Fix the number of call arguments (#79)
- Add missing ext-json requirement to the composer.json (#78)
- Fix/add PHPDoc type annotations (#77)
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
