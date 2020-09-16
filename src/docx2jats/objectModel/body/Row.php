<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/Row.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represents table row
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Cell;
use docx2jats\objectModel\Document;

class Row extends DataObject {

	private $properties = array();
	private $cells = array();

	public function __construct(\DOMElement $domElement, Document $ownerDocument) {
		parent::__construct($domElement, $ownerDocument);
		$this->properties = $this->setProperties('w:trPr/child::node()');
		$this->cells = $this->setContent('w:tc');
	}

	private function setContent(string $xpathExpression) {
		$content = array();
		$contentNodes = $this->getXpath()->query($xpathExpression, $this->getDomElement());
		if ($contentNodes->count() > 0) {
			foreach ($contentNodes as $contentNode) {

				// calculating cell number
				$cellNumber = 1;
				$precedeSiblingNodes = $this->getXpath()->query('preceding-sibling::w:tc', $contentNode);
				foreach ($precedeSiblingNodes as $precedeSiblingNode) {
					$colspan = $this->getXpath()->query('w:tcPr/w:gridSpan/@w:val', $precedeSiblingNode);
					if ($colspan->count() == 0 || empty($colspan)) {
						$cellNumber ++;
					} else {
						$cellNumber += intval($colspan[0]->nodeValue);
					}
				}
				// Omit merged nodes
				$colspansMerged = $this->getXpath()->query('w:tcPr/w:vMerge[@w:val="continue"]', $contentNode);
				if (!$colspansMerged->count() > 0) {
					$cell = new Cell($contentNode, $cellNumber, $this->getOwnerDocument());
					$content[] = $cell;
				}
			}
		}

		return $content;
	}

	public function getContent() {
		return $this->cells;
	}
}
