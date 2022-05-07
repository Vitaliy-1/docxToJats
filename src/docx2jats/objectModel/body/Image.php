<?php namespace docx2jats\objectModel\body;

/**
* @file src/docx2jats/objectModel/body/Image.php
*
* Copyright (c) 2018-2020 Vitalii Bezsheiko
* Distributed under the GNU GPL v3.
*
* @brief parses data from OOXML drawings; supports only pictures
*/

use docx2jats\objectModel\Document;

// TODO create a common parent class for Image and Table
class Image extends InfoBlock {

	/* @var $link string */
	protected $link;
	protected $figureId = 0;

	public function __construct(\DOMElement $domElement, $ownerDocument) {
		parent::__construct($domElement, $ownerDocument);

		$this->link = $this->extractLink();
		$this->setCaptionLibre();
	}

	private function extractLink(): ?string {
		$link = null;
		$relationshipId = null;

		// Only pictures are supported
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
	 * @brief LibreOffice Writer saves figure caption inside the drawing element;
	 */
	private function setCaptionLibre(): void {
		$txbxContent = Document::$xpath->query('.//w:txbxContent', $this->getDomElement())[0];
		if (!$txbxContent) return;

		// Pick up the first paragraph that contains caption style
		foreach ($txbxContent->childNodes as $childEl) {
			if ($childEl->tagName === 'w:p' && $this->getOwnerDocument()->isCaption($childEl)) {
				$this->setCaption($childEl);
				return;
			}
		}
	}

	/**
	 * @param int $currentFigureId
	 */
	public function setFigureId(int $currentFigureId): void {
		$this->figureId = $currentFigureId;
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->figureId;
	}
}
