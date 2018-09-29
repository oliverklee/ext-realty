/**
 * This file provides JavaScript functions for the Realty Manager.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */

var TYPO3 = TYPO3 || {};
TYPO3.realty = TYPO3.realty || {};

/**
 * In the front-end editor, hides/shows fields depending on whether currently
 * "rent" or "sale" is selected for the edited object.
 */
TYPO3.realty.updateHideAndShow = function () {
    if (jQuery("#tx_realty_frontEndEditor__object_type_0").prop("checked")) {
        jQuery(".rent").show();
        jQuery(".sale").hide();
    } else {
        jQuery(".rent").hide();
        jQuery(".sale").show();
    }

    if (jQuery("#tx_realty_frontEndEditor__contact_data_source_0").prop("checked")) {
        jQuery("#contact-data").show();
    } else {
        jQuery("#contact-data").hide();
    }

    if (jQuery("#tx_realty_frontEndEditor__has_coordinates").prop("checked")) {
        jQuery(".coordinates").show();
    } else {
        jQuery(".coordinates").hide();
    }
};

/**
 * Updates the districts selector via AJAX, depending on which city is selected.
 *
 * If no city is selected, the district selector will be hidden.
 */
TYPO3.realty.updateDistrictsInSearchWidget = function () {
    var $districtSelectorWidget = jQuery("#tx_realty_pi1_searchWidget_district");
    var $loader = jQuery("#tx-realty-pi1-loading");

    var cityUid = jQuery("#tx_realty_pi1-city").val();
    if (cityUid === "0") {
        $districtSelectorWidget.hide();
        return;
    }

    $districtSelectorWidget.hide();
    $loader.show();

    var url = "/index.php?eID=realty&type=withNumber&city=" + encodeURIComponent(cityUid);
    var $districtsDropDown = jQuery('#tx_realty_pi1-district');
    $districtsDropDown.load(url, function () {
        $districtSelectorWidget.show();
        $loader.hide();
    });
};

/**
 * Updates the district element in the editor, depending on whether a city is
 * selected.
 *
 * If no city is selected, the district element will be hidden.
 */
function updateDistrictsInEditor() {
    if (!jQuery("tx_realty_frontEndEditor_city")
        || !jQuery("tx_realty_frontEndEditor_district_wrapper")
        || !jQuery("tx_realty_frontEndEditor_district")
        || !jQuery("tx_realty_frontEndEditor_new_district_wrapper")
    ) {
        return;
    }

    var cityUid = jQuery("tx_realty_frontEndEditor_city").value;
    if (cityUid == "0") {
        Element.hide(jQuery("tx_realty_frontEndEditor_district_wrapper"));
        Element.hide(jQuery("tx_realty_frontEndEditor_new_district_wrapper"));
        return;
    }

    new Ajax.Updater(
        "tx_realty_frontEndEditor_district",
        "/index.php?eID=realty&city=" + encodeURI(cityUid),
        {
            method: "get",
            onLoading: function () {
                jQuery("tx_realty_frontEndEditor_district").disabled = true;
                Element.show(jQuery("tx_realty_frontEndEditor_district_wrapper"));
                Element.show(jQuery("tx_realty_frontEndEditor_new_district_wrapper"));
            },
            onComplete: function () {
                jQuery("tx_realty_frontEndEditor_district").disabled = false;
            }
        }
    );
}

/**
 * Appends a district so that it is available for selection in the FE editor.
 *
 * @param {integer} uid the UID of the district to add, must be > 0
 * @param {string} title the title of the district, must not be empty
 */
function appendDistrictInEditor(uid, title) {
    var container = jQuery("tx_realty_frontEndEditor_district");
    if (!container) {
        return;
    }
    var optionElement = new Element("option", {"value": uid});
    optionElement.appendChild(document.createTextNode(title));

    container.appendChild(optionElement);
}

TYPO3.realty.initializeFrontEndEditor = function () {
    var $editorForm = jQuery('#tx_realty_frontEndEditor');
    if ($editorForm.length === 0) {
        return;
    }

    TYPO3.realty.updateHideAndShow();
    jQuery(".js-realty-update-editor").click(TYPO3.realty.updateHideAndShow);
};

TYPO3.realty.initializeGoogleMaps = function () {
    var $map = jQuery('#tx_realty_map');
    if ($map.length === 0 || typeof TYPO3.realty.initializeMapMarkers !== 'function') {
        return;
    }

    TYPO3.realty.initializeMapMarkers();
};

TYPO3.realty.initializeSearchWidget = function () {
    var $districts = jQuery('#tx_realty_pi1_searchWidget_district');
    if ($districts.length === 0) {
        return;
    }

    jQuery('#tx_realty_pi1-city').change(TYPO3.realty.updateDistrictsInSearchWidget);
};

TYPO3.realty.initializeBackButton = function () {
    var $backButton = jQuery('.js-realty-back');
    if ($backButton.length === 0 || $backButton.attr('href') !== '#') {
        return;
    }

    $backButton.click(function () {
        history.back();
        return false;
    });
};

TYPO3.realty.initializePrintButton = function () {
    var $printButton = jQuery('.js-realty-print');
    $printButton.click(function () {
        window.print();
        return false;
    });
};

TYPO3.realty.initializeFavoritesButtons = function () {
    var $favoritesButtons = jQuery('.js-realty-favorites');
    $favoritesButtons.click(function () {
        jQuery('form#tx_realty_pi1_list_view').submit();
        return false;
    });
};

TYPO3.realty.initializeSortButton = function () {
    var $sortButton = jQuery('.js-realty-sort');
    $sortButton.click(function () {
        jQuery('form#tx_realty_pi1_sorting').submit();
        return false;
    });
};

TYPO3.realty.initializeConfirms = function () {
    var $confirmButtons = jQuery('.js-realty-confirm');
    $confirmButtons.click(function () {
        var confirmMessage = jQuery(this).data('confirm-message');
        return confirm(confirmMessage);
    });
};

TYPO3.realty.initializeDeleteImageButtons = function () {
    var $deleteImageButtons = jQuery('.js-realty-delete-image');
    $deleteImageButtons.click(function () {
        var $button = jQuery(this);
        var $deletedImageCollector = jQuery('#tx_realty_frontEndImageUpload__imagesToDelete');
        var imageId = $button.data('image-id');

        var confirmMessage = $button.data('confirm-message');
        if (confirm(confirmMessage)) {
            $deletedImageCollector.val($deletedImageCollector.val() + ',' + imageId);
            $button.parent().find('span').addClass('deleted');
            $button.addClass('deleted');
            $button.prop('disabled', true);
        }

        return false;
    });
};

jQuery(document).ready(function () {
    if (jQuery('.tx-realty-pi1').length === 0) {
        return;
    }

    TYPO3.realty.initializeFrontEndEditor();
    TYPO3.realty.initializeGoogleMaps();
    TYPO3.realty.initializeSearchWidget();
    TYPO3.realty.initializeBackButton();
    TYPO3.realty.initializePrintButton();
    TYPO3.realty.initializeFavoritesButtons();
    TYPO3.realty.initializeSortButton();
    TYPO3.realty.initializeConfirms();
    TYPO3.realty.initializeDeleteImageButtons();
});
