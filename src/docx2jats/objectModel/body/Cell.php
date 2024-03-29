<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/Cell.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief parses data from table cell
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;

class Cell extends DataObject {

	private $properties = array();
	private $paragraphs = array();

	/* @var $colspan int */
	private $colspan;

	 /* @var $rowspan int */
	private $rowspan;

	/* @var $isMerged bool */
	private $isMerged;

	/* @var $cellNuber int */
	private $cellNumber;

	public function __construct(\DOMElement $domElement, int $cellNumber, Document $ownerDocument, Row $parent) {
		parent::__construct($domElement, $ownerDocument, $parent);

		$this->cellNumber = $cellNumber;
		$this->isMerged = $this->defineMerged();
		$this->colspan = $this->extractColspanNumber();
		$this->extractRowspanNumber();
		$this->paragraphs = $this->setParagraphs();
		$this->properties = $this->setProperties('w:tcPr');
	}

	/**
	 * @return bool
	 */
	public function defineMerged(): bool {
		$mergeNodes = $this->getXpath()->query('w:tcPr/w:vMerge', $this->getDomElement());

		if ($mergeNodes->count() == 0) {
			return false;
		}

		return true;
	}

	/**
	 * @return void
	 */
	private function extractRowspanNumber(): void {
		$rowMergedNode = $this->getXpath()->query('w:tcPr/w:vMerge[@w:val=\'restart\']', $this->getDomElement());
		$this->rowspan = 1;

		if ($rowMergedNode->count() > 0) {
			$this->extractRowspanRecursion($this->getDomElement());
		}
	}

	/**
	 * @param \DOMElement $node
	 * @return void
	 */
	private function extractRowspanRecursion(\DOMElement $node): void {
		$cellNodeListInNextRow = $this->getXpath()->query('parent::w:tr/following-sibling::w:tr[1]/w:tc', $node);


		$numberOfCells = 0; // counting number of cells in a row
		$mergedNode = null; // retrieving possibly merged cell node

		foreach ($cellNodeListInNextRow as $cellNodeInNextRow) {

			$colspanNode = $this->getXpath()->query('w:tcPr/w:gridSpan/@w:val', $cellNodeInNextRow);
			if ($colspanNode->count() == 0) {
				$numberOfCells ++;
			} else {
				$numberOfCells += intval($colspanNode[0]->nodeValue) - 1;
			}

			/*
			echo $this->getColspan();
			echo " : " . $numberOfCells . "-" . $this->cellNumber . "\n";
			*/
			if ($numberOfCells == $this->cellNumber) {
				$mergedNode = $cellNodeInNextRow;
				break;
			}
		}

		// check if the node is actually merged
		if ($mergedNode) {
			$isActuallyMerged = $this->getXpath()->query('w:tcPr/w:vMerge', $mergedNode);
			if ($isActuallyMerged->count() > 0) {
				if ($isActuallyMerged[0]->getAttribute('w:val') !== 'restart') {
					$this->rowspan ++;
					$this->extractRowspanRecursion($mergedNode);
				}
			}
		}
	}

	/**
	 * @return int
	 */
	private function extractColspanNumber(): int {
		$colspan = 1;

		$colspanAttr = $this->getXpath()->query('w:tcPr/w:gridSpan/@w:val', $this->getDomElement());
		if ($this->isOnlyChildNode($colspanAttr)) {
			$colspan = $colspanAttr[0]->nodeValue;
		}
		return $colspan;
	}

	/**
	 * @return array
	 */
	public function getContent(): array {
		return $this->paragraphs;
	}

	/**
	 * @return int
	 */
	public function getColspan(): int {
		return $this->colspan;
	}

	/**
	 * @return int
	 */
	public function getRowspan(): int {
		return $this->rowspan;
	}
}
