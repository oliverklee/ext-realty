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


Create an offerer list
^^^^^^^^^^^^^^^^^^^^^^

The offerer list can be used to display the contact information of
offerers of certain FE user groups. Each offerer item can be
configured to contain a link to the list of objects by this offerer.
It is also configurable which information to show of an offerer.

To setup this list, do the following:

#. Create a FE page, visible in menu with caching enabled.

#. Create a new content element: Plugins > Realty Manager.

#. In the content element, set “What should be displayed” to “Offerer
   list”.

#. Define which FE user groups are the offerers you intend to display.
   For this, provide a comma-separated list of UIDs to
   “userGroupsForOffererList” or choose the groups via FlexForms in the
   offerer information tab. You can also leave this value empty. All FE
   users will be displayed then.

#. Choose which data to display of each offerer by setting
   “displayedContactInformation” or checking it in the FlexForms.

#. Set the PID of the “Objects by offerer” list to enable the link to
   each offerer's objects via FlexForms or TS setup. (See section below
   for how to setup this list.)

#. Save and close, you are done.

It is also possible to show different contact data of some offerers.
For this, define the FE user groups in which these offerers are via
FlexForms or by setting
“groupsWithSpeciallyDisplayedContactInformation” in the TS setup and
set which data to display of these offerers by setting
“displayedContactInformationSpecial”.
