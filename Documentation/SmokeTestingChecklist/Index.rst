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


Smoke testing checklist
-----------------------

#. In the EM, configure the OpenImmo import to only send the e-mails to
   yourself.

#. Import an object using OpenImmo. There should be no error messages
   (except for validation warnings) on the console.

#. Check the import e-mail.

#. In the search widget, select a price span and check the list view.

#. Click on an object title and check the single view.

#. Click on an image and check the Lightbox gallery.

#. Login as a FE user.

#. In the FE editor, create an object for rent. Choose the owner as
   contact source. Enter a title, an object number, a rent, a ZIP, a
   description and select a city.

#. Check that the created object is listed in the “my objects” list.

#. Click on “upload images”, enter a title and upload an image. Check
   that it is shown in the list view.

#. View the offerer list.

#. Click on the “all objects by this offerer” button.

#. Select an object and click on “add to favorites”.

#. Check that the object is listed on the favorites list.

#. Check the object and click on “remove from favorites”.

#. Go to the (general) contact page and send an e-mail.

#. Check your e-mail. There should be the e-mail from the contact form
   and a notification e-mail that an object has been created in the FE.

|img-17| EXT: Realty Manager - 22
