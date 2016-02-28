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


Create system folders for records
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

First you create the data pool: a  **system folder for auxiliary
records** like house types, cities or heating types. It is important
that you  **put all auxiliary records into exactly one folder** , or
you will not be able to edit realty objects in the BE.

In this folder, simply click on “Create new record” and choose the
type of record.

Note: After successfully installing the Realty Manager, you should be
able to choose from a great variety of record types like “house type”,
“garage type” etc. If you don't seem those have, your installation is
broken.

**Example:** Create record:

- House Type ---> One-Family Home

- House Type ---> Multi-Family Home

Then create one or more  **system folders for the realty objects** .
If you have just a few realty objects without images, one folder
should suffice. If you have lots of objects and many images, it is
recommended to create a separate folder for each object (or each
street).

Edit this system folder (or the parent folder if you have more than
one system folder for realty objects) and choose the system folder
with the auxiliary records as  **General Record Storage page** . This
is important so that you can select the house types etc. when editing
realty objects.

A BE folder structure with two streets and a folder for each street
will look like this:

|img-11|  *Figure 11: BE folder structure with two streets and a
folder for each street*
