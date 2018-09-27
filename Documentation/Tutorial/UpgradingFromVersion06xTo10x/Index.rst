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


Upgrading from version 0.6.x to 1.0.x
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

#. Install the latest version of the  *oelib* and  *static\_info\_tables*
   extensions.

#. Temporarily uninstall the Realty Manager.

#. Uninstall the ameos\_formidable extension.

#. Install the mkforms and rn\_base extensions.

#. Include the *MKFORMS - Basics (mkforms)* template in your site template
   under "Include static (from extensions)."

#. Update the Realty Manager.

#. Select the “UPDATE” drop-down in the extension manager for the Realty
   Manager. This will assign cities to your district records if the
   relation is unambiguous.

#. View your front-end pages that contain the Realty Manager plug-in and
   check that there are no configuration check warnings. If there are any
   warnings, fix your setup and reload that page.

#. In the extension manager, disable “Automatic configuration check”
   (this will improve performance).
