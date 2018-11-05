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


((generated))
^^^^^^^^^^^^^

Setup for the Realty Manager front-end plugin
"""""""""""""""""""""""""""""""""""""""""""""

You can configure the plugin using your TS template setup in the form
plugin.tx\_realty\_pi1. *property = value.* Some values can also be
configures using flexforms.

If your want to set a value for all instances of the plugin in one
place, use the TS template setup. If you use flexforms, make sure to
set the values at all relevant instances of the plug in: It doesn't do
to specify the fields for the FE editor in the realty list front end
plugin—you need to set these fields in the FE editor front-end plugin.

**Note: If you set any non-empty value in the flexforms, this will
override the corresponding value from TS Setup.**

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         what\_to\_display

   Data type
         string

   Description
         The kind of front end plugin to display. **This must be set using
         flexforms.**

   Default
         realty\_list


.. container:: table-row

   Property
         templateFile

   Data type
         string

   Description
         File name of the HTML template

   Default
         EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html


.. container:: table-row

   Property
         salutation

   Data type
         string

   Description
         Switch whether to use formal/informal language on the front
         end.Allowed values are:formal \| informal

         Note: Currently, there only are texts for the formal salutation.

   Default
         formal


.. container:: table-row

   Property
         currencyUnit

   Data type
         string

   Description
         currency symbol, must be a valid ISO alpha 3 code, e.g. "EUR" for
         Euro, "USD" for US dollars

   Default
         EUR


.. container:: table-row

   Property
         dateFormat

   Data type
         string

   Description
         format for all dates (as required for strftime)

   Default
         %d.%m.%Y


.. container:: table-row

   Property
         listImageMaxX

   Data type
         integer

   Description
         maximum X size of images in the list view (in pixels)

   Default
         98


.. container:: table-row

   Property
         listImageMaxY

   Data type
         integer

   Description
         maximum Y size of images in the list view (in pixels)

   Default
         98


.. container:: table-row

   Property
         singleImageMaxX

   Data type
         integer

   Description
         maximum X size of images in the single view (in pixels)

   Default
         137


.. container:: table-row

   Property
         singleImageMaxY

   Data type
         integer

   Description
         maximum Y size of images in the single view (in pixels)

   Default
         137


.. container:: table-row

   Property
         imageUploadThumbnailWidth

   Data type
         integer

   Description
         maximum width of thumbnails in the front-end image upload

   Default
         200


.. container:: table-row

   Property
         imageUploadThumbnailHeight

   Data type
         integer

   Description
         maximum height of thumbnails in the front-end image upload

   Default
         75


.. container:: table-row

   Property
         lightboxImageWidthMax

   Data type
         integer

   Description
         maximum width of the images shown in the lightbox gallery

   Default
         1024


.. container:: table-row

   Property
         lightboxImageHeightMax

   Data type
         integer

   Description
         maximum height of the images shown in the lightbox gallery

   Default
         768


.. container:: table-row

   Property
         enableLightbox

   Data type
         boolean

   Description
         whether the lightboxshould be enabled

   Default
         1


.. container:: table-row

   Property
         fieldsInSingleViewTable

   Data type
         string

   Description
         ordered, comma-separated list of fields from tx\_realty\_objects that
         will be shown in the table in the single view

   Default
         house\_type, apartment\_type, total\_area, total\_usable\_area, living\_area, office\_space, shop\_area, sales\_area, site\_occupancy\_index, floor\_space\_index, window\_bank, storage\_area, other\_area, estate\_size, garage\_type, parking\_spaces, state, status, usable\_from, number\_of\_rooms, bedrooms, bathrooms, heating\_type, furnishing\_category, flooring, construction\_year, floor, floors, layout, pets, balcony, garden, barrier\_free, wheelchair\_accessible, elevator, ramp, lifting\_platform, suitable\_for\_the\_elderly, assisted\_living, fitted\_kitchen, has\_air\_conditioning, has\_pool, has\_community\_pool, distance\_to\_the\_sea, sea\_view, buying\_price, rent\_excluding\_bills, rent\_with\_additional\_costs, year\_rent, rental\_income\_target, rent\_per\_square\_meter, extra\_charges, heating\_included, deposit, hoa\_fee, provision, garage\_rent, garage\_price, energy\_certificate\_type, energy\_certificate\_valid\_until, energy\_consumption\_characteristic, with\_hot\_water, ultimate\_energy\_demand, primary\_energy\_carrier, electric\_power\_consumption\_characteristic, heat\_energy\_consumption\_characteristic, value\_category, year\_of\_construction, energy\_certificate\_issue\_date, energy\_certificate\_year, building\_type, energy\_certificate\_text, heat\_energy\_requirement\_value, heat\_energy\_requirement\_class, total\_energy\_efficiency\_value, total\_energy\_efficiency\_class


.. container:: table-row

   Property
         defaultContactEmail

   Data type
         string

   Description
         default e-mail address for requests, used without validation

   Default


.. container:: table-row

   Property
         blindCarbonCopyAddress

   Data type
         string

   Description
         e-mail address where to send a BCC of each request, leave empty to
         disable

   Default


.. container:: table-row

   Property
         showContactPageLink

   Data type
         boolean

   Description
         whether the contact form should be displayed (applicable in single and
         favorites view)

   Default
         0


.. container:: table-row

   Property
         visibleContactFormFields

   Data type
         string

   Description
         Comma-separated list of fields to show in the contact form, allowed
         values are:name, street, zip\_and\_city, telephone, request, viewing,
         information, callback, terms, law

   Default
         name,street,zip\_and\_city,telephone,request


.. container:: table-row

   Property
         requiredContactFormFields

   Data type
         string

   Description
         Comma-separated list of required fields for the contact form; allowed
         values are:name, street, zip, city, telephone, request

   Default
         name,request


.. container:: table-row

   Property
         termsPID

   Data type
         page\_id

   Description
         PID of the page containing the terms linked from the contact form

   Default


.. container:: table-row

   Property
         favoriteFieldsInSession

   Data type
         string

   Description
         ordered, comma-separated list of field names that will be stored in
         the session when displaying the favorites list, leave empty to
         disable; all DB column names from tx\_realty\_objects are allowed

   Default


.. container:: table-row

   Property
         requireLoginForSingleViewPage

   Data type
         boolean

   Description
         whether the single view page may only be viewed by logged-in FE users

   Default
         0


.. container:: table-row

   Property
         loginPID

   Data type
         page\_id

   Description
         PID of the login page (only necessary if you set
         requireLoginForDetailsPage to 1)

   Default
         none


.. container:: table-row

   Property
         contactPID

   Data type
         page\_id

   Description
         PID of the contact page which will be linked from the favorites list
         (leave empty to disable this link)

   Default
         none


.. container:: table-row

   Property
         pages

   Data type
         string

   Description
         Starting point: comma-separated list of PIDs that contain the realty
         records to be displayed;  **usually this is selected via flexforms**

   Default


.. container:: table-row

   Property
         recursive

   Data type
         integer

   Description
         recursion level for the starting point/pages list;  **usually this is
         selected via flexforms**

   Default
         0


.. container:: table-row

   Property
         staticSqlFilter

   Data type
         string

   Description
         static SQL filter (will be appended to the WHERE clause using " AND ")

   Default


.. container:: table-row

   Property
         checkboxesFilter

   Data type
         string

   Description
         name of the DB field to create the search filter checkboxes from

   Default


.. container:: table-row

   Property
         orderBy

   Data type
         string

   Description
         which DB field is used for the default sorting in the list view

   Default
         tstamp


.. container:: table-row

   Property
         sortCriteria

   Data type
         string

   Description
         DB fields by which a FE user can sort the list view

   Default


.. container:: table-row

   Property
         displayedSearchWidgetFields

   Data type
         String

   Description
         list of search fields which should be displayed in the search widget
         available fields are: site, priceRanges, uid, objectNumber, city,
         district, objectType, rent, livingArea, houseType, numberOfRooms

   Default
         sites


.. container:: table-row

   Property
         singleViewPartsToDisplay

   Data type
         string

   Description
         keys of the single View parts to display, should be set via flexforms

   Default
         heading,address,description,documents,furtherDescription,price,overvie
         wTable,imageThumbnails,addToFavoritesButton,contactButton,offerer,prin
         tPageButton,backButton


.. container:: table-row

   Property
         singlePID

   Data type
         page\_id

   Description
         PID of the page for the single view (leave empty to use the same page
         as the list view)

   Default


.. container:: table-row

   Property
         favoritesPID

   Data type
         page\_id

   Description
         PID of the page with the favorites list

   Default


.. container:: table-row

   Property
         filterFormTargetPID

   Data type
         page\_id

   Description
         PID of the target page for the search form and the city selector

   Default


.. container:: table-row

   Property
         editorPID

   Data type
         page\_id

   Description
         PID of the page with the FE editor

   Default


.. container:: table-row

   Property
         imageUploadPID

   Data type
         page\_id

   Description
         PID of the page with the image upload

   Default


.. container:: table-row

   Property
         objectsByOwnerPID

   Data type
         page\_id

   Description
         PID of the target page for the list of objects by one owner

   Default


.. container:: table-row

   Property
         offererImageMaxWidth

   Data type
         Integer

   Description
         the maximum width for the offerer image

   Default
         150


.. container:: table-row

   Property
         offererImageMaxHeight

   Data type
         integer

   Description
         the maximum height for the offerer image

   Default
         100


.. container:: table-row

   Property
         userGroupsForOffererList

   Data type
         string

   Description
         Comma-separated list of FE user group UIDs for the offerer list

   Default


.. container:: table-row

   Property
         displayedContactInformation

   Data type
         string

   Description
         Comma-separated list of contact information to display

   Default
         offerer\_label,telephone


.. container:: table-row

   Property
         displayedContactInformationSpecial

   Data type
         string

   Description
         Comma-separated list of contact information to display of the offerers
         in the groups in groupsWithSpeciallyDisplayedContactInformation

   Default
         offerer\_label,telephone


.. container:: table-row

   Property
         groupsWithSpeciallyDisplayedContactInformation

   Data type
         string

   Description
         Comma-separated list of user group UIDs of which to display special
         offerer information

   Default


.. container:: table-row

   Property
         sysFolderForFeCreatedRecords

   Data type
         page\_id

   Description
         PID of the system folder for FE-created records

   Default


.. container:: table-row

   Property
         sysFolderForFeCreatedAuxiliaryRecords

   Data type
         page\_id

   Description
         PID of the system folder for FE-created auxiliary records

   Default


.. container:: table-row

   Property
         feEditorRedirectPid

   Data type
         page\_id

   Description
         PID of the FE page to redirect to after saving a FE-created record

   Default


.. container:: table-row

   Property
         feEditorNotifyEmail

   Data type
         string

   Description
         e-mail address that receives a message if a new record has been FE-
         created

   Default


.. container:: table-row

   Property
         feEditorTemplateFile

   Data type
         string

   Description
         location of the HTML template file for the FE editor and image upload

   Default
         EXT:realty/Resources/Private/Templates/FrontEnd/Editor.html


.. container:: table-row

   Property
         showGoogleMaps

   Data type
         boolean

   Description
         whether Google Maps should be displayed in the list view

   Default
         0


.. container:: table-row

   Property
         defaultCountryUID

   Data type
         Integer

   Description
         default country for objects that have no country set (a UID from the
         static\_countries table, 54 = Germany)

   Default
         54


.. container:: table-row

   Property
         showIdSearchInFilterForm

   Data type
         string

   Description
         Show ID search in search view. If set to 'uid' the UID search form
         will be displayed, if set to 'objectNumber' the object number search
         form will be shown. If left empty the search field will be hidden.

   Default


.. container:: table-row

   Property
         advertisementPID

   Data type
         page\_id

   Description
         the page ID with an advertisement form for realty objects, leave empty
         to disable the link

   Default


.. container:: table-row

   Property
         advertisementParameterForObjectUid

   Data type
         string

   Description
         he GET parameter name that will contain the UID of realty object for
         the "advertise" link, e.g. "tx\_foo[uid]"

   Default


.. container:: table-row

   Property
         advertisementExpirationInDays

   Data type
         integer

   Description
         the number of days after which an advertisement expires, set to 0 to
         have no expiration

   Default


.. container:: table-row

   Property
         priceOnlyIfAvailable

   Data type
         boolean

   Description
         whether the price (buying price or rent) should only be visible if an
         object is vacant or reserved, but not if it is sold of rented

   Default
         0


.. container:: table-row

   Property
         enableNextPreviousButtons

   Data type
         boolean

   Description
         whether to show the next and previous buttons

   Default
         0


.. ###### END~OF~TABLE ######

[tsref:plugin.tx\_realty\_pi1]


Setup for the list view
"""""""""""""""""""""""

For the list view, there are some additional configuration option that
can only be set using the TS setup (not with flexforms) in the form
plugin.tx\_realty\_pi1.listView. *property = value.*

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         results\_at\_a\_time

   Data type
         integer

   Description
         the number of realty objects that will be displayed per page

   Default
         10


.. container:: table-row

   Property
         maxPages

   Data type
         integer

   Description
         how many pages should be displayed in the list view page navigation

   Default
         5


.. container:: table-row

   Property
         descFlag

   Data type
         boolean

   Description
         the default sort order in the list view: 0 = ascending, 1 = descending

   Default
         1


.. ###### END~OF~TABLE ######

[tsref:plugin.tx\_realty\_pi1.listView]


Constants for the Realty Manager front-end plug-in in plugin.tx\_realty\_pi1
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

You can configure the plug-in using your TS template constant in the
form plugin.tx\_realty\_pi1. *property = value.*

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         cssFile

   Data type
         string

   Description
         location of the general CSS file (set as empty to not include the
         file)

   Default
         EXT:realty/pi1/tx\_realty\_pi1.tpl.css


.. container:: table-row

   Property
         cssFileScreen

   Data type
         string

   Description
         location of the screen-only CSS file (leave empty to include no CSS
         file)

   Default
         EXT:realty/pi1/tx\_realty\_pi1\_screen.css


.. container:: table-row

   Property
         cssFilePrint

   Data type
         string

   Description
         location of the print-only CSS file (leave empty to include no CSS
         file)

   Default
         EXT:realty/pi1/tx\_realty\_pi1\_print.css


.. ###### END~OF~TABLE ######
