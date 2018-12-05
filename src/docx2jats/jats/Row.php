<?php namespace docx2jats\jats;

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Cell as JatsCell;

class Row extends Element {
	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}
	
	public function setContent() {
		foreach ($this->getDataObject()->getContent() as $content) {
			$cell = new JatsCell($content);
			$this->appendChild($cell);
			$cell->setContent();
		}
	}
}
