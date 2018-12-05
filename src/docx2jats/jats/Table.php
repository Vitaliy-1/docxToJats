<?php namespace docx2jats\jats;

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Row as JatsRow;

class Table extends Element {
	
	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}
	
	public function setContent() {
		foreach ($this->getDataObject()->getContent() as $content) {
			$row = new JatsRow($content);
			$this->appendChild($row);
			$row->setContent();
		}
	}
}
