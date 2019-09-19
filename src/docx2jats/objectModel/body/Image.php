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

	public function __construct(\DOMElement $domElement) {
		parent::__construct($domElement);

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

}
