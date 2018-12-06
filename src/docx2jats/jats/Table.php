<?php namespace docx2jats\jats;

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Row as JatsRow;

class Table extends Element {
	
	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}
	
	public function setContent() {
		
		// TODO create and append table label and caption
		
		$tableNode = $this->ownerDocument->createElement('table');
		$this->appendChild($tableNode);
		
		foreach ($this->getDataObject()->getContent() as $content) {
			$row = new JatsRow($content);
			$tableNode->appendChild($row);
			$row->setContent();
		}
	}
}
