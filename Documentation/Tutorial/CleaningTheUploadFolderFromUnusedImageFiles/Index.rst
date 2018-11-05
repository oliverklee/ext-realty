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


Cleaning the upload folder from unused image files
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

It is possible that the usage of the OpenImmo import leads to lots of
unused image files in the upload folder. To delete them and also to
mark non-referenced image records in the database as hidden, you can
use the Scheduler Task "realty: Image cleanup".
