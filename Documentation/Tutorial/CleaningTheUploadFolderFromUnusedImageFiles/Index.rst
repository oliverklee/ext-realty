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

It is possible, that usage of the OpenImmo import leads to lots of
unused image files in the upload folder. To delete them and also to
mark non-referenced image records in the database as hidden, there is
a CLI script.

Start this script by cronjob:

#. Create a BE TYPO3 user named “\_cli\_realty” with  *User Admin* . This
   user does not need to be configured in any special way but must not be
   an admin user. (Problably, if you are using the import, there is
   already such a user in your TYPO3 installation. Then just skip this
   step.)

#. Set up a cron job to run PHP with it. The command to use for the cron
   job is:/[ *absolute path of the TYPO3 installation*
   ]/typo3/cli\_dispatch.phpsh cleanUpRealtyImages A line in your cron
   tab that imports realty objects at three o’clock a.m. then could look
   like this:0 3 \* \* \* /var/www/typo3/cli\_dispatch.phpsh
   cleanUpRealtyImages
