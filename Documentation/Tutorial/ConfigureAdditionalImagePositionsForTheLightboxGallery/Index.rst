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


Configure additional image positions for the Lightbox gallery
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you don't configure anything special, the Lightbox image gallery is
configured with the following TS setup variables:

::

   plugin.tx_realty_pi1 {
           # whether the lightbox should be enabled
           enableLightbox = 1
           # maximum X size of images in the single view
           singleImageMaxX = 137
           # maximum Y size of images in the single view
           singleImageMaxY = 137
           # maximum width of the images shown in the lightbox gallery
           lightboxImageWidthMax = 1024
           # maximum height of the images shown in the lightbox gallery
           lightboxImageHeightMax = 768
   }

When editing image records in the back end, you can move the records
to different image positions 1 to 4 using the drop-down in the record.
These image positions then will be displayed below the regular
Lightbox gallery in the front-end single view. You then can overwrite
the default settings mentioned above for single image positions:

::

   plugin.tx_realty_pi1.images {
                   1 {
                           enableLightbox = 0
                           singleImageMaxX = 200
                           singleImageMaxY = 200
                   }
                   2 {
                           lightboxImageWidthMax = 800
                           lightboxImageHeightMax = 600
                   }
           } {
   }

If you do not specify any values for an image position, the global
values will be used.
