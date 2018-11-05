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


Import OpenImmo records from ZIP files
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

General setup
#############

#. On the realty extension's configuration page in the  *Extension
   Manger* configure the following:

- Import directory:Specify a directory from which the script should
  fetch the OpenImmo records. Ensure the user who executes the script
  has read and write permissions on this folder. The ZIP archives in
  this folder will be deleted after their contents have been imported.
  So just put copies of the original files into the import folder if you
  need to keep the records.

- Delete imported ZIPs:Set this option to delete all ZIP archives in the
  import folder automatically after their content has been added to the
  database. This will also affect records which were not imported due to
  import failures like missing required fields.ZIP archives with records
  that were not imported because FE user restriction is enabled and no
  matching FE user was found will not be deleted although this option is
  enabled.

- Only import for registered FE users:Set this option to import only
  realty records with an OpenImmo ANID that matches a FE user's OpenImmo
  ANID.Skipped records will be mentioned in the log.

- Restrict import to FE user groups:Set a comma-separated list of FE
  user group UIDs whose members' realty records may be imported. Leave
  this field empty, if all FE user groups are allowed.This option is
  only used if “Only import for registered FE users” is checked.

- PID for realty objects and images:Set the PID of the system folder
  where new realty records and related images should be stored.

- PID for auxiliary records:Specify a system folder where to store
  auxiliary records (e.g. house types, heating types, pets) or leave
  this value empty to store these records in the same folder as the
  OpenImmo records. **Note:** It is important that all auxiliary records
  are stored in only one folder, or you will not be able to edit the
  realty objects in the BE.

- Use FE user data as contact data:Check this option to set the contact
  data source of each imported object which has an owner to the owner.
  The e-mail address of the owner's FE user record then will receive the
  import log. Apart from that, this flag enables to access the owner's
  data for the detail view and the contact form (if those display types
  are configured to use record-specific contact data – see sections
  about these display types for more details).

- Recipient of the import logs:Set a default e-mail address or disable
  sending e-mails by leaving this field empty.This address receives the
  logged information about the proceedings the import. This information
  can either be a summary of all imported records or only of those which
  did not have a valid contact e-mail address (see next item).

- E-mail the log only on errors:Decide whether e-mails should contain
  only information about errors which occurred during the import. If
  this option is enabled, contact persons might not receive a message at
  all if no errors occurred while importing their data.

- Notify contact persons:Decide whether contact persons should receive
  the log information via e-mail. If this option is enabled, the default
  e-mail address is only used if records with invalid e-mail addresses
  were imported or if there were non-readable records. Each contact
  person will receive no more than one e-mail during the import even if
  there are several records with the same contact e-mail address.

- XML Schema file for validation: If the data should be validated,
  configure the path of the XML Schema file. (XML Schema files have the
  suffix \*.xsd). Note: The licensing of the OpenImmo Schema doesn’t
  allow us to distribute the Schema file with this extension. So you
  need to obtain openimmo\_120.xsd from `http://www.openimmo.de/
  <http://www.openimmo.de/>`_ yourself.

- Language of the import messages:Set the language for the log which is
  created at the end of each import. Currently English and German are
  available. Note: This setting also determines the title of imported
  records for pets.

- E-mail text template:The e-mail layout can be varied by defining
  another template file.

#. Copy the records to import into the import folder (or provide your
   users with FTP access so that they can upload their files). The
   records need to be ZIP files with one OpenImmo-XML file and a variable
   number of images inside. These ZIPs will be unpacked to folders
   created inside the import folder during runtime. These folders are
   removed when the import is done. The ZIP archives are not changed at
   all.

The import has been tested with records made with the following
programs:

- Makler 2000

- Flowfact

- IMS 2000

- Ammon

- OnOffice

They were validated against the OpenImmo schema file version 1.2.
Unfortunately, none of the files validated successfully, but the
import succeeded nonetheless.

Generally, records are not imported if fields are missing which are
required for OpenImmo records.

Whether a record already exists in the database and just needs to be
updated is checked by “object\_number”, “openimmo\_anid” and
“language”. So  **each combination of object number and OpenImmo ANID
must not occur more than once per language** to avoid unwanted
overwriting.

Image files are copied to the extension's uploads folder. When a
record is inserted, also the related records (like cities, images,
pets, etc.) are created and linked with the current record.

Database records can also be deleted during the import. This does not
mean they are totally removed from the database but the field “
*deleted* ” is set to true. For deleting, set the attribute of
“Aktion” in the OpenImmo record to “ *DELETE* ”. If this record exists
in the database it will be deleted during the next import. If it does
not exist, the record will be ignored (there will also be a log
message that the record was not written to the database then).

After a ZIP file has been successfully imported, it will automatically
be deleted if this is enabled in the extension manager.

Setting up the scheduler task
#############################

Set up the Scheduler and add a task "realty: OpenImmo import".
