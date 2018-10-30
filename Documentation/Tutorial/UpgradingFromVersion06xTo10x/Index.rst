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


Upgrading
^^^^^^^^^

Upgrading from version 0.6.x to 1.0.x
######################################

#. Make sure that you have jQuery in your page HEAD.

#. Install the latest versions of the  *oelib* and  *static\_info\_tables*
   extensions.

#. Temporarily uninstall the Realty Manager.

#. Uninstall the ameos\_formidable extension.

#. Install the mkforms and rn\_base extensions.

#. Update the Realty Manager.

#. View your front-end pages that contain the Realty Manager plug-in and
   check that there are no configuration check warnings. If there are any
   warnings, fix your setup and reload that page.

#. In the extension manager, disable “Automatic configuration check”
   (this will improve performance).

#. If you are using a modified HTML template, you will need to do some changes
   to you template regarding the way JavaScript events are attached to elements.
   Please see this change for details:
   https://github.com/oliverklee/ext-realty/pull/69/files#diff-a4cb18715aa583919424191c6ad93483

#. The gallery also has been updated from Lightbox to Lightbox 2. Please check
   that the gallery works for you (if you are using the gallery) and that you
   do not get any warnings or error in your browser error console.

Upgrading from version 1.0.x to 1.1.x
######################################

#. Update the realty extension.

#. If you are using a cronjob for the OpenImmo import, remove the CLI call
   and use the corresponding Scheduler task. (The CLI script is deprecated
   and will be removed in realty 2.0.0.)

#. If you are using a cronjob for the image cleanup, remove the CLI call
   and use the corresponding Scheduler task. (The CLI script is deprecated
   and will be removed in realty 2.0.0.)
