<?php namespace docx2jats\objectModel\body;

/**
* @file src/docx2jats/objectModel/body/Image.php
*
* Copyright (c) 2018-2019 Vitalii Bezsheiko
* Distributed under the GNU GPL v3.
*
* @brief parses data from OOXML drawings; supports only pictures
*/

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;

class Image extends DataObject {

	/* @var $link string */
	private $link;
	private ?string $label = null;
	private ?string $title = null;

	public function __construct(\DOMElement $domElement, $ownerDocument) {
		parent::__construct($domElement, $ownerDocument);

		$this->link = $this->extractLink();

	}

	private function extractLink(): ?string {
		$link = null;
		$relationshipId = null;

		$this->getXpath()->registerNamespace("a", "http://schemas.openxmlformats.org/drawingml/2006/main");
		$linkElement = $this->getFirstElementByXpath(".//a:blip", $this->getDomElement());
		if ($linkElement && $linkElement->hasAttribute("r:embed")) {
			$relationshipId = $linkElement->getAttribute("r:embed");
		}

		if ($relationshipId) {
			$link = Document::getRelationshipById($relationshipId);
		}

		return $link;
	}

	public function getLink(): ?string {
		return $this->link;
	}

	public function getFileName(): ?string {
		$name = basename($this->link);
		return $name;
	}

	/**
	 * @param \DOMElement $el
	 * @brief retrieve data from caption DOMElement
	 */
	public function setCaption(\DOMElement $el): void {
		$label = '';
		$title = '';

		$textNodes = Document::$xpath->query('./w:r/w:t', $el);
		foreach ($textNodes as $key => $textNode) {
			if ($key == 0) {
				$label .= $textNode->nodeValue;
			} else {
				$title .= $textNode->nodeValue;
			}
		}

		$labelNumber = Document::$xpath->query('./w:fldSimple//w:t', $el)[0];
		if (!is_null($labelNumber)) {
			$label .= $labelNumber->nodeValue;
		}

		if (!empty($label)) {
			$this->label = $label;
		}

		if (!empty($title)) {
			$this->title = trim($title);
		}
	}

	/**
	 * @return string|null
	 */
	public function getLabel(): ?string {
		return $this->label;
	}

	/**
	 * @return string|null
	 */
	public function getTitle(): ?string {
		return $this->title;
	}
}
