<?php namespace docx2jats\objectModel\body;

use docx2jats\objectModel\DataObject;

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
	
	public function __construct(\DOMElement $domElement, $cellNumber) {
		parent::__construct($domElement);
		
		$this->cellNumber = $cellNumber;
		$this->isMerged = $this->defineMerged();
		$this->extractRowspanNumber();
	}
	
	public function defineMerged(): bool {
		$mergeNodes = $this->getXpath()->query('w:tcPr/w:vMerge', $this->getDomElement());
		
		if ($mergeNodes->count() == 0) {
			return false;
		}
		
		return true;
	}
	
	private function extractRowspanNumber() {
		$rowMergedNode = $this->getXpath()->query('w:tcPr/w:vMerge[@w:val=\'restart\']', $this->getDomElement());
		$this->rowspan = 1;
		
		if ($rowMergedNode->count() > 0) {
			$this->extractRowspanRecursion($this->getDomElement());
		}
	}
	
	private function extractRowspanRecursion(\DOMElement $node) {
		$cellNodeListInNextRow = $this->getXpath()->query('parent::w:tr/following-sibling::w:tr[1]/w:tc', $node);
		
		
		$numberOfCells = 0; // counting number of cells in a row
		$mergedNode = null; // retrieving possibly merged cell node
		
		foreach ($cellNodeListInNextRow as $cellNodeInNextRow) {
			
			$colspanNode = $this->getXpath()->query('w:tcPr/w:gridSpan/@w:val', $cellNodeInNextRow);
			if ($colspanNode->count() == 0) {
				$numberOfCells ++;
			} else {
				$numberOfCells += intval($colspanNode[0]->nodeValue);
			}
			
			if ($numberOfCells == $this->cellNumber) {
				$mergedNode = $cellNodeInNextRow;
				break;
			}
			
		}
		
		// check if the node is actually merged
		if ($mergedNode) {
			$isActuallyMerged = $this->getXpath()->query('w:tcPr/w:vMerge', $mergedNode);
			if ($isActuallyMerged->count() > 0) {
				
				$this->rowspan ++;
				$this->extractRowspanRecursion($mergedNode);
			}
		}
	}
}
