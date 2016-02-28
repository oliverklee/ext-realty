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


Create a list of objects by one offerer
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The “Objects by offerer” view lists all objects that have the same
owner. User can reach this view by clicking the link to this list in
the “Offerer list” or optionally, if this is configured, by clicking
this link in the single view of one object.

How to setup this list:

#. Create a FE page. This page should not be in visible in menu.

#. Create a new content element: Plugins > Realty Manager.

#. In the content element, set “What should be displayed” to “Objects by
   offerer”.

#. Set the starting point like for the list view.

#. Define the PID for the detail view and for the favorites view.

#. As for the normal list view, you can setup sorting options and create
   search filter checkboxes.

#. Save and close. You are done.

Do not forget to provide this PID to the offerer list, and, if needed,
to customize the single view, so the link to this list is visible
there, too. (See section Create the single view.)
