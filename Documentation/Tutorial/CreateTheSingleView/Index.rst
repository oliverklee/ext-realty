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


Create the single view
^^^^^^^^^^^^^^^^^^^^^^

#. Create a sub page of the list view page and set it to “not in menu”
   (as it should be reached via links from the list view, not via the
   page navigation). This will be the page for the single view.

#. As for the list view, add a content object with the Realty Manager
   plug-in.

#. For “What should be displayed”, select “Single view”.

#. You can customize the single view:

#. Select which parts of the Single View should actually be displayed.

- Displaying information about the offerer:

  - Define which offerer information to display by setting
    “displayedContactInformation” or choosing the information via
    FlexForms within the offerer information tab. (It is also possible to
    show different information of some offerers. See section about the
    offerer list for this.)

  - If your site also has an offerer list, it is recommended also to set
    “userGroupsForOffererList” just to the same value as used for this
    list to keep both views consistently. This is especially important if
    the user group is configured to be displayed in both views.

  - Provide a PID for “Page with list of objects by one owner” to enable a
    link to from the single view to this list.

- Via TS setup, you can restrict the single view to logged in users. For
  this, set  *requireLoginForSingleViewPage* and define  *loginPID.*

- Via TS setup, you can also define, which fields should be displayed in
  the overview table. Provide a comma-separated list of field names for
  *fieldsInTheSingleViewTable* .

- If there should be a link to the contact page, you will need to
  configure the contact PID via TS setup. (You can also setup the
  contact form on the same page as the single view. See section  *Create
  a contact form.* )
