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


Create a search form
^^^^^^^^^^^^^^^^^^^^

A search form can be used either on the same page as the list view or
on any other page. With this form, users can filter the realty list by
certain criteria: One can fill in a ZIP code or a city, enter or
select a price range, enter a living area range or select a city from
a drop-down menu. After submit, only objects which fulfill these
criteria will be displayed.

To setup a search form, do the following:

#. Choose a FE page and create a new content element. Set the element
   type to “plugin” and choose Realty Manager as plugin type.

#. In the content element, set “ **What should be displayed** ” to “
   **Search form** ”

#. Set “ **Target page for the search widget** ” to the **list view
   page** . Do not forget to set the list view to “ **no cache** ”.

#. Decide which parts of your search form should be visible: You can have
   a drop-down box for price ranges, an input field for a site search, an
   input field for the object number, an input field for the UID, a drop-
   down box for city selection, district selection or house type
   selection, object type radiobuttons or input fields for the living
   area, or all at the same time.

- **Search fields to show:** You can check each element you want to be
  displayed in the search form

- “ **Price ranges for the search form** ” must be of the following
  format:

  - Ranges are separated by “,”.

  - Each range has an upper and a lower limit separated by “-”. If there
    is only an upper limit provided, the lower one is considered to be
    zero. If there is only lower limit, the other one is interpreted as
    infinitely high.

  Example: “-100000,100001-300000,300001-” will create three options:
  “less than 100000”, “100001 to 300000”, “more than 300001”.

#. Save and close.
