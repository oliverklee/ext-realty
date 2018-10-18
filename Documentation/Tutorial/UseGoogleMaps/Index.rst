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


Use Google Maps
^^^^^^^^^^^^^^^

#. You will need to obtain a Google API key for this.

You can set the API key using TypoScript:

::

     plugin.tx_oelib.googleGeocodingApiKey = ...
     plugin.tx_oelib.googleMapsApiKey = ...

#. Set the default country for most of your objects using TS setup of the
   flexforms of the list views and single views. For all objects that are
   in a different country, you need to enter the country so the address
   can be looked up properly.

#. Enable Google Maps for the list views or single views in in the
   flexforms or in your TS Setup.

#. The coordinates or your objects will automatically be looked up and
   cached when the Google Map is displayed. Depending on whether the
   addresses of objects should be displayed, either the exact address
   will be used or a rough address.  **Note: As the Google Maps geocoding
   service blocks requests that are faster than 1.73 requests per second,
   this process may take some time the first time an object is displayed
   with Google Maps.**

#. If an address cannot be found, the checkbox “coordinates are
   synchronized with the server” will be set (so the coordinates will not
   be looked up over and over again), but the coordinates themselves will
   be empty. You then can either manually fill in the coordinates. If the
   address is incomplete, you can complete it an remove the
   “synchronized” checkbox so the coordinates will be looked up again.
