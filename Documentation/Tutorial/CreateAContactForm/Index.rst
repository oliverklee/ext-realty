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


Create a contact form
^^^^^^^^^^^^^^^^^^^^^

The Realty Manager provides two types of contact forms, which type is
used depends on the link with which the contact form is reached:

- **General type** : Visitors can reach the form with a button on their
  favorites list or in the navigation menu. This form will always send
  requests to the default e-mail address. The sent e-mails will contain
  a summary of the visitor's current favorites list.

- **Specialized type** : Visitors can reach the form by using the
  “Contact us” link an object's single view page. The page with the form
  will show the object's title and number. E-mails are sent directly to
  the offerer of the object.The offerer is, determined by
  “contact\_data\_source”, either the owner or is defined in the realty
  object's “contact\_email” field. If no valid e-mail address could be
  fetched from the defined source, the default e-mail address is used.


Configuration for the contact form
""""""""""""""""""""""""""""""""""

#. Create or choose a FE page for the contact form. It is recommended to
   activate “ **hide in menu** ” because the summary of the visitor's
   current favorites and the realty object information can only be
   provided if the corresponding links in the favorites or single view
   were used to reach this form.If the single view and the contact form
   for an object should be displayed simultaneously, choose the FE page
   which contains the single view for the contact form, too.

#. Create a new content element: Plugins > Realty Manager.

#. In the content element, set “ **What should be displayed** ” to “
   **Contact form** ”.

#. Set a  **default e-mail** address. This address will be used for all
   requests if the general contact form is displayed. For the specialized
   contact form with information about a realty object, only messages
   which cannot be sent to the offerer will be sent to this address.The
   e-mail address can be set via flexforms or TS setup.

#. If needed, set a  **BCC address** , where all requests will also be
   sent to.

#. Customize your contact form by setting which fields should be
   displayed and which of those should be required.

#. Set “ **contactPID** ” in the TS setup to your FE page which now
   contains the realty plugin with the contact form:
   *plugin.tx\_realty\_pi1.contactPID =* [PID of the contact form]
