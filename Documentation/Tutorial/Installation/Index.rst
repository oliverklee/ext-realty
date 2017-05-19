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

#. Make sure that you use UTF-8 in the BE and FE. Otherwise, the OpenImmo
   import won’t work properly, and the FE output might be broken.
   **Don’t set config.renderCharset or config.metaCharset** (leaving
   those values empty will automatically cause TYPO3 to use the BE
   charset in the FE as well).

#. If you want to use the offerers list view, make sure that the
   **sf\_register** or **sr\_feuser\_register** extension is installed.

#. Install **ameos\_formidable**: Please use the `patched ameos\_formidable
   <https://www.dropbox.com/s/y513ui1u08mcc2z/T3X_ameos_formidable-1_1_564-z-201506082123.t3x?dl=0>`_.

#. Install the required extensions **static\_info\_tables** and **oelib**
   which are available in the TER.

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
