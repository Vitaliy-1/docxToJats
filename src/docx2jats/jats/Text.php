<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Text.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent document's text and its formatting
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Text as ObjectText;

class Text {

	public static function extractText(ObjectText $jatsText, \DOMElement $domElement) : void {

		// Get DOMDocument
		$domDocument = $domElement->ownerDocument;
		// Dealing with simple text (without any properties)
		$nodeTypes = $jatsText->getType();
		if (empty($nodeTypes)) {
			$textNode = $domDocument->createTextNode($jatsText->getContent());
			$domElement->appendChild($textNode);
			unset($nodeTypes);
		}
		// Renaming text properties into standard HTML node element
		$typeArray = array();
		if (isset($nodeTypes)) {
			foreach ($nodeTypes as $nodeType) {
				switch ($nodeType) {
					case ObjectText::DOCX_TEXT_ITALIC:
						$typeArray[] = "italic";
						break;
					case ObjectText::DOCX_TEXT_BOLD:
						$typeArray[] = "bold";
						break;
					case ObjectText::DOCX_TEXT_SUPERSCRIPT:
						$typeArray[] = "sup";
						break;
					case ObjectText::DOCX_TEXT_SUBSCRIPT:
						$typeArray[] = "sub";
						break;
					case ObjectText::DOCX_TEXT_EXTLINK:
						$typeArray[] = "ext-link";
						break;
				}
			}
		}
		// Dealing with text that has only one property, e.g. italic, bold, link
		if (count($typeArray) === 1) {
			foreach ($typeArray as $typeKey => $type) {
				if (!is_array($type)) {
					$nodeElement = $domDocument->createElement($type);
					$nodeElement->nodeValue = htmlspecialchars($jatsText->getContent());
					$domElement->appendChild($nodeElement);
					if ($type == "ext-link") {
						$nodeElement->setAttribute("xlink:href", $jatsText->getLink());
					}
				} else {
					foreach ($type as $insideKey => $insideType) {
						$nodeElement = $domDocument->createElement($insideKey);
						$nodeElement->nodeValue = htmlspecialchars(trim($jatsText->getContent()));
						$domElement->appendChild($nodeElement);
					}
				}
			}
			// Dealing with complex cases -> text with several properties
		} else {
			/* @var $prevElement array of DOMElements */
			$prevElements = array();
			foreach ($typeArray as $key => $type) {
				if (!is_array($type)) {
					$nodeElement = $domDocument->createElement($type);
				}

				array_push($prevElements, $nodeElement);

				if ($key === 0) {
					$domElement->appendChild($prevElements[0]);
				} elseif (($key === (count($typeArray) - 1))) {

					$nodeElement->nodeValue = htmlspecialchars($jatsText->getContent());
					if ($type == "ext-link"){
						$nodeElement->setAttribute("xlink:href", $jatsText->getLink());
					}

					foreach ($prevElements as $prevKey => $prevElement) {
						if ($prevKey !== (count($prevElements) -1)) {
							$prevElement->appendChild(next($prevElements));
						}
					}
				}
			}
		}
	}
}
