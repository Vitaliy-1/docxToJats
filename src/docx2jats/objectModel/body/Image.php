<?php namespace docx2jats\objectModel\body;

/**
* @file src/docx2jats/objectModel/body/Image.php
*
* Copyright (c) 2018-2020 Vitalii Bezsheiko
* Distributed under the GNU GPL v3.
*
* @brief parses data from OOXML drawings; supports only pictures
*/

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;

// TODO create a common parent class for Image and Table
class Image extends DataObject {

	/* @var $link string */
	private $link;
	private $label = null;
	private $title = null;
	private $figureId = 0;
	private $bookmarkIds = array();
	private $bookmarkText = ''; // TODO Check if there are situation where bookmark text is needed for JATS

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

		// Caption may have bookmarks that are pointed from outside the table, retrieve their IDs;
		// TODO Check if other bookmark types may be inserted in captions
		$bookmarkStartEls = Document::$xpath->query('w:bookmarkStart', $el);
		foreach ($bookmarkStartEls as $bookmarkStartEl) { /* @var $bookmarkStartEl \DOMElement */
			if ($bookmarkStartEl->hasAttribute('w:name')) {
				$this->bookmarkIds[] = $bookmarkStartEl->getAttribute('w:name');
			}
		}
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

	/**
	 * @return array
	 */
	public function getBookmarkIds(): array {
		return $this->bookmarkIds;
	}
}
