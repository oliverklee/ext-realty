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


Create a TS template for this extension
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In the system folder (or the page) in which you have stored all your
TS templates, create an additional TS template and name it
“plugin.realty” or something similar.

|img-7|  *Figure 7: Create an additional TS template.*

Include it in your main template using “Include basis template”.

|img-8|  *Figure 8: Include it in your main template using “Include
basis template”.*

Edit the plugin.realty template.

|img-9|  *Figure 9: Edit the plugin.realty template.*

In the section “Include static (from extensions)”, include “Realty
List (realty)” to get the default setup for this extension.

|img-10|  *Figure 10: Include “Realty List (realty)” to get the
default setup for this extension.*

If you need additional configuration settings which either aren't
available in the flexforms or which you would like to set using TS
setup, put them in the setup of this template. (In this manual's
reference, you'll find all settings that are available in flexforms or
TS setup.)
