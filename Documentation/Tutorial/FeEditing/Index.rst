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


FE editing
^^^^^^^^^^

With the display type My objects logged in FE users get displayed all
objects they created. They can edit them again, delete them or create
new objects with the FE editor. They can also append and delete images
to the objects they created.

Setup the My objects list

#. Create a FE page for the My objects list. Choose for which user groups
   this page should be accessible.

#. Create a new content element: Plugins > Realty Manager.

#. In the content element, set “What should be displayed” to “My
   objects”.

#. Set the starting point like for the list view.

#. Set the PID of the FE editor.

#. Set the PID of the FE image upload.

Setup the FE editor

#. Create a FE page for the FE editor. Set this page to “hide in menu”
   and choose for which user groups this page should be accessible.

#. Create a new content element: Plugins > Realty Manager.

#. In the content element, set “What should be displayed” to “FE editor”.

#. Choose a system folder where the newly created records should be
   stored.

#. Configure which page to show after saving a realty record. (E.g. you
   can setup another FE page with tells the user that the object has been
   successfully saved.)

#. Set an e-mail address which receives a message if a new record has
   been added to the database.

Setup the FE image upload

#. Create a FE page for the FE image upload. Set this page to “hide in
   menu” and choose for which user groups this page should be accessible.
   (This should usually be the same as for the FE editor.)

#. Create a new content element: Plugins > Realty Manager.

#. In the content element, set “What should be displayed” to “Image
   upload”.

#. Configure which page to show after saving a realty record. (E.g. you
   can setup another FE page with tells the user that the object has been
   successfully saved or just show the My objects list again.)

If there are already records in your database, you want FE users to
edit on their own, just set the record's field owner to the FE user.

FE editors can create new cities and districts. All other auxiliary
records (e.g. heating types, pets) have to be chosen out of the set of
existing records. So you will need to create some of these records in
the BE.

FE user-created records can be stored city-wise. Therefore you need to
create a system folder for each city of which records should be stored
separately. Then set the Save folder field in each corresponding city
record to this system folder.

Note that FE-created record become marked as hidden by default and
have to be unhidden by a BE user.
