<?php namespace docx2jats\objectModel\body;

/**
* @file src/docx2jats/objectModel/body/Figure.php
*
* Copyright (c) 2018-2019 Vitalii Bezsheiko
* Distributed under the GNU GPL v3.
*
* @brief parses data from OOXML drawings; supports only pictures
*/

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;

class Figure extends DataObject {
	const DOCX_DRAWING_PICTURE = 1;

	// TODO not yet supported
	const DOCX_DRAWING_CHART = 2;

	/* @var $link string */
	private $link;

	/* @var $link string const */
	private $type;

	public function __construct(\DOMElement $domElement) {
		parent::__construct($domElement);

		$this->type = $this->extractType();
		$this->link = $this->extractLink();

	}


	private function extractType(): ?int {
		$type = 0;

		$drawing = $this->getFirstElementByXpath("w:r//w:drawing", $this->getDomElement());

		// Find pic:pic element, should be embedded picture according to OOXML guidelines
		if ($drawing) {
			// Sometimes these prefixes are not registered by default
			$this->getXpath()->registerNamespace("pic", "http://schemas.openxmlformats.org/drawingml/2006/picture");
			$this->getXpath()->registerNamespace("c", "http://schemas.openxmlformats.org/drawingml/2006/chart");

			$picture = $this->getFirstElementByXpath("//pic:pic", $drawing);
			if ($picture) {
				$type = self::DOCX_DRAWING_PICTURE;
				return $type;
			}

			$chart = $this->getFirstElementByXpath("//c:chart", $drawing);
			if ($chart) {
				$type = self::DOCX_DRAWING_CHART;
				return $type;
			}
		}

		return $type;

	}

	private function extractLink(): ?string {
		$link = null;
		$relationshipId = null;

		if ($this->type == self::DOCX_DRAWING_PICTURE) {
			$this->getXpath()->registerNamespace("a", "http://schemas.openxmlformats.org/drawingml/2006/main");
			$linkElement = $this->getFirstElementByXpath("w:r//w:drawing//a:blip", $this->getDomElement());
			if ($linkElement->hasAttribute("r:embed")) {
				$relationshipId = $linkElement->getAttribute("r:embed");
			}
		} elseif ($this->type == self::DOCX_DRAWING_CHART) {
			$linkElement = $this->getFirstElementByXpath("w:r//w:drawing//c:chart", $this->getDomElement());
			if ($linkElement->hasAttribute("r:id")) {
				$relationshipId = $linkElement->getAttribute("r:id");
			}
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
	 * @return string
	 */
	public function getType(): ?string {
		return $this->type;
	}
}
