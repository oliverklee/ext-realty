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
