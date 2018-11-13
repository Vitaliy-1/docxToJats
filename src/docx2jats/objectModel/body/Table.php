<?php namespace docx2jats\objectModel\body;

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Row;

class Table extends DataObject {
	
	private $properties = array();
	private $rows = array();
	
	public function __construct(\DOMElement $domElement) {
		parent::__construct($domElement);
		$this->properties = $this->setProperty('w:tblPr/child::node()');
		$this->rows = $this->setContent('w:tr');
	}
	
	private function setContent(string $xpathExpression) {
		$content = array();
		
		$contentNodes = $this->getXpath()->query($xpathExpression, $this->getDomElement());
		if ($contentNodes->count() > 0) {
			foreach ($contentNodes as $contentNode) {
				$row = new Row($contentNode);
				$content[] = $row;
			}
		}
		
		return $content;
	}
	
	public function getContent() {
		return $this->rows;
	}
}
