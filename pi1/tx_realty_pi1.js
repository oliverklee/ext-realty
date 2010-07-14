/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Saskia Metzler <saskia@merlin.owl.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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
 * Sets the current full-size image to the current thumbnail and sets the class
 * attribute of the thumbnail accordingly.
 *
 * @param string ID of the link tag that wraps the current image tag, must not
 *               be empty
 * @param string link to the full-size version of the current image, must not
 *               be empty
 */
function showFullsizeImage(id, linkToFullsizeImage) {
	var fullsizeImage = document.getElementById("tx_realty_fullsizeImage");
	var currentImage = document.getElementById(id).getElementsByTagName("img")[0];
	var thumbnails = document.getElementById("tx_realty_thumbnailTable").getElementsByTagName("a");

	fullsizeImage.removeAttribute("width");
	fullsizeImage.removeAttribute("height");
	fullsizeImage.alt = currentImage.title;
	fullsizeImage.title = currentImage.title;
	fullsizeImage.src = linkToFullsizeImage;
	for (i = 0; i < (thumbnails.length); i++) {
		thumbnails[i].className = "tx-realty-pi1-thumbnail";
	};
	document.getElementById(id).className += "-current";
	document.getElementById("tx_realty_fullsizeImageCaption").firstChild.nodeValue = currentImage.title;
}

/**
 * Marks the current attachment as deleted if the confirm becomes submitted.
 *
 * @param string ID of the list item with the attachment to delete, must not be
 *               empty
 * @param string localized confirm message for whether really to mark an
 *               attachment for deletion
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

	if ($("tx_realty_frontEndEditor_show_address").checked) {
		$$(".exact-address").invoke("show");
		$$(".rough-address").invoke("hide");
	} else {
		$$(".exact-address").invoke("hide");
		$$(".rough-address").invoke("show");
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
			onLoading: function() {
				$("tx_realty_pi1-district").disabled = true;
				Element.show($("tx_realty_pi1_searchWidget_district"));
				Element.show($("tx-realty-pi1-loading"));
			},
			onComplete: function() {
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
			onLoading: function() {
				$("tx_realty_frontEndEditor_district").disabled = true;
				Element.show($("tx_realty_frontEndEditor_district_wrapper"));
				Element.show($("tx_realty_frontEndEditor_new_district_wrapper"));
			},
			onComplete: function() {
				$("tx_realty_frontEndEditor_district").disabled = false;
			}
		}
	);
}

/**
 * Appends a district so that it is available for selection in the FE editor.
 *
 * @param integer uid the UID of the district to add, must be > 0
 * @param string title the title of the district, must not be empty
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
