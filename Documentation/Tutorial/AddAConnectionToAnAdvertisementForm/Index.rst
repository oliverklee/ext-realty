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


Add a connection to an advertisement form
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you have a page with an advertising form (eg. for print
advertisements), you can create links to it from the “my objects”
page.

#. Create a FE page for the form and place the form on it.

#. On the “my objects” page, edit the Realty Manager plugin and switch to
   the tab “advertisements”.

#. Select the page that contains the form.

#. Enter the name of the GET parameter that should contain the object UID
   in the link.

#. If the advertisements should expire after some time, enter the
   duration of the expiration period in days.

Note: The advertising form is not provided by the Realty Manager
extension. You need to use your own plugin for this.
