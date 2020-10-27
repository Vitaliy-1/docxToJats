<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/Table.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief contains table data
 */

use docx2jats\jats\Element;
use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Row;
use docx2jats\objectModel\Document;

class Table extends DataObject {

	private $properties = array();
	private $rows = array();
	public static $caption = array("caption");
	private ?string $label = null;
	private ?string $title = null;

	public function __construct(\DOMElement $domElement, Document $ownerDocument) {
		parent::__construct($domElement, $ownerDocument);
		$this->properties = $this->setProperties('w:tblPr/child::node()');
		$this->rows = $this->setContent('w:tr');
	}

	private function setContent(string $xpathExpression) {
		$content = array();

		$contentNodes = $this->getXpath()->query($xpathExpression, $this->getDomElement());
		if ($contentNodes->count() > 0) {
			foreach ($contentNodes as $contentNode) {
				$row = new Row($contentNode, $this->getOwnerDocument(), $this);
				$content[] = $row;
			}
		}

		return $content;
	}

	public function getContent() {
		return $this->rows;
	}

	/**
	 * @param array $captions
	 * @param \DOMElement $parNode
	 * @return array
	 * @brief identify the type of caption: table or figure
	 */
	static function captionType(array $captions, \DOMElement $parNode): array {

		$fldSimpleInstr = Document::$xpath->query('./w:fldSimple/@w:instr', $parNode)[0];

		if (!is_null($fldSimpleInstr)) {
			$stringForSearch = strtolower($fldSimpleInstr->nodeValue);
			if (strpos($stringForSearch, 'table') !== false) {
				$captions['table'][] = $parNode;
			} elseif (strpos($stringForSearch, 'figure') !== false) {
				$captions['figure'][] = $parNode;
			}
		} else {
			$simpleName = Document::$xpath->query('./w:r/w:t', $parNode)[0];
			if (!is_null($simpleName)) {
				$captions[strtolower(trim($simpleName->nodeValue))]; // MS Word allows not to include the label
			}
		}

		return $captions;
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
