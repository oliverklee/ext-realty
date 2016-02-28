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


Change the extension's look & feel
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can change the look & feel by copying the files
*tx\_realty\_pi1.tpl.css* and  *tx\_realty\_pi1.tpl.htm* from the
folder typo3conf/ext/realty/pi1/ and setting the TS setup variable
*plugin.tx\_realty\_pi1.templateFile* and the constant
*plugin.tx\_realty\_pi1.cssFile* to point to the copies. Then you can
modify the copies. You can also change only the HTML template or the
CSS file if that suffices to achieve the look & feel you want.

**Note: Never edit the original HTML or CSS file within the extension
directory itself!** If you do this, upgrading the extension will
overwrite any changes you have made to the files.

Also have a look at the reference to see how the extension can be
configured using TS setup and flexforms.
