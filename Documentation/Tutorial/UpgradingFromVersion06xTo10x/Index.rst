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

#. Update to the `patched ameos\_formidable
   <https://dl.dropboxusercontent.com/u/27225645/Extensions/T3X_ameos_formidable-1_1_564-z-201506082123.t3x>`_.

#. Update the Realty Manager.

#. Select the “UPDATE” drop-down in the extension manager for the Realty
   Manager. This will assign cities to your district records if the
   relation is unambiguous.

#. View your front-end pages that contain the Realty Manager plug-in and
   check that there are no configuration check warnings. If there are any
   warnings, fix your setup and reload that page.

#. In the extension manager, disable “Automatic configuration check”
   (this will improve performance).
