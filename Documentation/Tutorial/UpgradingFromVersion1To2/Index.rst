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


Upgrading
^^^^^^^^^

Upgrading from version 1.x.y to 2.x.y
#####################################

#. In the install tool, set **MAIL > defaultMailFromAddress** and
   **MAIL > defaultMailFromName**.

#. Install the latest versions of the  *oelib* and  *static\_info\_tables*
   extensions.

#. Update the realty extension.

#. In the extension manager, reset the the email template path to
   EXT:realty/Resources/Private/Templates/Email/Notification.txt.

#. If you are using a cronjob for the OpenImmo import, remove the CLI call
   and use the corresponding Scheduler task.

#. If you are using a cronjob for the image cleanup, remove the CLI call
   and use the corresponding Scheduler task.
