/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This file provides JavaScript functions for the Realty Manager.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */

/**
 * Marks the current attachment as deleted if the confirm becomes submitted.
 *
 * @param {string} listItemId ID of the list item with the attachment to delete, must not be empty
 * @param {string} confirmMessage localized confirm message for whether really to mark an attachment for deletion
 */
function markAttachmentAsDeleted(listItemId, confirmMessage) {
    var listItem = document.getElementById(listItemId);
    var fileNameDiv = listItem.getElementsByTagName("span")[0];
    var deleteButton = listItem.getElementsByTagName("input")[0];

    if (confirm(confirmMessage)) {
        document.getElementById("tx_realty_frontEndImageUpload_imagesToDelete").value
            += "," + listItemId;
        fileNameDiv.setAttribute("class", "deleted");
        deleteButton.className += " deleted";
        deleteButton.disabled = true;
    }
}

/**
 * In the front-end editor, hides/shows fields depending on whether currently
 * "rent" or "sale" is selected for the edited object.
 */
function updateHideAndShow() {
    if ($("tx_realty_frontEndEditor_object_type_item").checked) {
        $$(".rent").invoke("show");
        $$(".sale").invoke("hide");
    } else {
        $$(".rent").invoke("hide");
        $$(".sale").invoke("show");
    }

    if ($("tx_realty_frontEndEditor_contact_data_source_item").checked) {
        $("contact-data").show();
    } else {
        $("contact-data").hide();
    }

    if ($("tx_realty_frontEndEditor_has_coordinates").checked) {
        $$(".coordinates").invoke("show");
    } else {
        $$(".coordinates").invoke("hide");
    }
}

/**
 * Updates the districts selector via AJAX, depending on which city is selected.
 *
 * If no city is selected, the district selector will be hidden.
 *
 * This function must only be called if Prototype is loaded.
 */
function updateDistrictsInSearchWidget() {
    if (!$("tx_realty_pi1-city") || !$("tx_realty_pi1-district")) {
        return;
    }

    var cityUid = $("tx_realty_pi1-city").value;
    if (cityUid == "0") {
        Element.hide($("tx_realty_pi1_searchWidget_district"));
        return;
    }

    new Ajax.Updater(
        "tx_realty_pi1-district",
        "/index.php?eID=realty&type=withNumber&city=" + encodeURI(cityUid),
        {
            method: "get",
            onLoading: function () {
                $("tx_realty_pi1-district").disabled = true;
                Element.show($("tx_realty_pi1_searchWidget_district"));
                Element.show($("tx-realty-pi1-loading"));
            },
            onComplete: function () {
                $("tx_realty_pi1-district").disabled = false;
                Element.hide($("tx-realty-pi1-loading"));
            }
        }
    );
}

/**
 * Updates the district element in the editor, depending on whether a city is
 * selected.
 *
 * If no city is selected, the district element will be hidden.
 */
function updateDistrictsInEditor() {
    if (!$("tx_realty_frontEndEditor_city")
        || !$("tx_realty_frontEndEditor_district_wrapper")
        || !$("tx_realty_frontEndEditor_district")
        || !$("tx_realty_frontEndEditor_new_district_wrapper")
    ) {
        return;
    }

    var cityUid = $("tx_realty_frontEndEditor_city").value;
    if (cityUid == "0") {
        Element.hide($("tx_realty_frontEndEditor_district_wrapper"));
        Element.hide($("tx_realty_frontEndEditor_new_district_wrapper"));
        return;
    }

    new Ajax.Updater(
        "tx_realty_frontEndEditor_district",
        "/index.php?eID=realty&city=" + encodeURI(cityUid),
        {
            method: "get",
            onLoading: function () {
                $("tx_realty_frontEndEditor_district").disabled = true;
                Element.show($("tx_realty_frontEndEditor_district_wrapper"));
                Element.show($("tx_realty_frontEndEditor_new_district_wrapper"));
            },
            onComplete: function () {
                $("tx_realty_frontEndEditor_district").disabled = false;
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
    var container = $("tx_realty_frontEndEditor_district");
    if (!container) {
        return;
    }
    var optionElement = new Element("option", {"value": uid});
    optionElement.appendChild(document.createTextNode(title));

    container.appendChild(optionElement);
}
