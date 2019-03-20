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

Upgrading from version 2.x.y to 3.x.y
#####################################

#. Note: The separate image thumbnails have been removed.

#. Note: The image position setting has been removed. If you are using
   a customized HTML template, please remove the IMAGES\_POSITION\_* subparts.

#. The image cleanup Scheduler task has been removed as it no longer needed
   with the change to FAL. If you are using this Scheduler task, please delete it.

#. Install the latest versions of the  *oelib* and  *static\_info\_tables*
   extensions.

#. Update the realty extension.

#. The images and documents have been converted to FAL.
   Please run the update function for the realty extension in
   the extension manager to convert your data.

#. The previous/next buttons have been removed to allow for better caching
   and to enable more speaking URLs. Please remove the
   FIELD\_WRAPPER\_NEXTPREVIOUSBUTTONS subpart from your HTML template,
   replace "###BACK_URL###" with "#"
   and re-save the single view content element one time to update the
   flexforms data.
