.. include:: Images.txt

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


Create a favorites list
^^^^^^^^^^^^^^^^^^^^^^^

#. |img-16|  *Figure 16: create a FE page for the favorites list.*

   Create a FE page for the favorites list.

#. Create a new content element: Plugins > Realty Manager.

#. |img-13|  *Figure 17: create a new content element: Plugins > Realty
   Manager*

   In the content element, set “ **What should be displayed** ” to “ **Favorites list** ”.

#. As a starting point, select the system folder for realty objects.
   (This can also be done using TS setup instead of in the flexforms.) It
   is recommended to select a parent page of all your system folders with
   your realty objects and then set the recursion depth to “infinite”.

#. |img-14|  *Figure 18: select the system folder for realty objects*

   Select the (existing) page for the single view. (This can also be done using TS setup instead of in the flexforms.)

#. Save and close.

#. Edit all your existing list view and single view content elements and
   select the page with the favorites list. Instead, you could enter the
   PID into the TS setup variable plugins.tx\_realty\_pi1.favoritesPID.

The data of the favorites list is stored in the anonymous session
under the following two keys:

- tx\_realty\_favorites: comma-separated list of the UIDs of the realty
  objects in the sessions

- tx\_realty\_favorites\_verbose: the contents of realty objects fields
  that have been selected via the TS setup variable
  plug.tx\_realty\_pi1.favoriteFieldsInSession
