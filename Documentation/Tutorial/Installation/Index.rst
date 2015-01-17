.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Installation
^^^^^^^^^^^^

#. In the install tool, disable  **FE > pageNotFoundOnCHashError** .

#. If you want to use the OpenImmo import and your server uses SuSE
   Linux, please make sure that the package  *php5-zip* or  *php-zip* is
   installed

#. If you want to use the offerers list view, make sure that the
   sr\_feuser\_register extension is installed.

#. Install ameos\_formidable. If you are using TYPO3 >= 4.7, you will
   need the patched ameos\_formidable from `https://dl.dropboxusercontent
   .com/u/27225645/Extensions/T3X\_ameos\_formidable-1\_1\_563-z-20140417
   1623.t3x <https://dl.dropboxusercontent.com/u/27225645/Extensions
   /T3X_ameos_formidable-1_1_563-z-201404171623.t3x>`_

#. Make sure that your installation fulfills the requirements (PHP >=
   5.3, TYPO3 >= 4.5, oelib 0.8, ameos\_formidable >= 1.1.0, but <
   2.0.0).Make sure that the extension  **dbal is not installed** on your
   system.

#. Make sure that you use UTF-8 in the BE and FE. Otherwise, the OpenImmo
   import won’t work properly, and the FE output might be broken.
   **Don’t set config.renderCharset or config.metaCharset** (leaving
   those values empty will automatically cause TYPO3 to use the BE
   charset in the FE as well).

#. Install the required extensions “oelib” and “static\_info\_tables”
   which are available in the TER.

#. Install the required extension “ameos\_formidable” from the TER.
   You'll need the latest 1.x version of ameos\_formidable. If a version
   2.x is available from the TER, you'll need to select the latest 1.x
   version manually:

   #. In  *Extension Manager > Import extensions* , type “ameos\_formidable”
      and click on  *Look up* .

   #. In the list of results, click on the title  *Ameos Formidable* .

   #. Under  *SELECT COMMAND* , choose the highest 1.x.y version from the
      drop-down list.

   #. Click on  *Import/Update* .

#. Install the Realty Manager extension.

#. In the Extension Manager, make sure that “Configuration check” is
   checked. This will aid you in quickly configuring the extension.

#. You don’t need to set the other values in the Extension Manager yet
   (they’re used for the OpenImmo import).

#. In your TS setup, please set config.language and config.locale\_all so
   the extension will use the correct language in the front end.

#. Clear all caches.

#. If the front-end labels of the extension are in English instead of your
   configured language, please deleted the following files/directories:

   - typo3temp/realty-l10n-*.zip
   - typo3conf/l10n/*/realty/
