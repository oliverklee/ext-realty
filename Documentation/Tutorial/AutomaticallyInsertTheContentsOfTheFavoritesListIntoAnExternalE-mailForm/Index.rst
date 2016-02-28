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


Automatically insert the contents of the favorites list into an external e-mail form
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The Realty Manager allows you include the realty objects on a user’s
favorites list into TYPO3’s standard e-mail form.

#. Install the extension  *mailform\_userfunc* (version 0.0.3 of that
   extension has been tested to work) and set the split character to “>”.
   Remember to install the extension’s static template.

#. Set up a contact page with TYPO3’s default e-mail form (if you haven’t
   done so yet).

#. Click on the configuration wizard to setup the form's content. (You
   might need to click on the arrow-headed button to see the detailed
   configuration.)

#. Choose the element type “hidden” and set the value to
   “{TSFE:fe\_user>sesData>summaryStringOfFavorites}” (without the
   quotes). This will automatically insert a summary of the user’s
   favorites list.

Note: It is recommended to use the Realty Manager's own contact form.
The summary of the favorites list is also provided there. (See section
above.)
