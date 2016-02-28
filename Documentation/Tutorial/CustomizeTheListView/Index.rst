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


Customize the list view
^^^^^^^^^^^^^^^^^^^^^^^

If you like, you can customize which objects should be visible in the
list view. For hand-crafted lists, the plugin provides a field “Static
SQL filter” which you can use to limit the list view if you know some
SQL.

Some examples:

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   filter
         filter

   SQL
         SQL


.. container:: table-row

   filter
         only objects that are for renting

   SQL
         object\_type = 0


.. container:: table-row

   filter
         only objects that are for sale

   SQL
         object\_type = 1


.. container:: table-row

   filter
         only objects near Berlin (if you use German ZIP codes)

   SQL
         zip LIKE "10%"


.. container:: table-row

   filter
         with at least 5 rooms

   SQL
         number\_of\_rooms >= 5


.. container:: table-row

   filter
         with a living area of less than 50 m²

   SQL
         (living\_area != 0 AND living\_area < 50)


.. container:: table-row

   filter
         only objects that are already rented

   SQL
         rented = 1


.. ###### END~OF~TABLE ######
