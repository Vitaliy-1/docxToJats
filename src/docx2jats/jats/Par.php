<?php namespace docx2jats\jats;

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Text as JatsText;

class Par extends Element {
	
	public function __construct(DataObject $dataObject)
	{
		parent::__construct($dataObject);
		
	}
	
	public function setContent() {
		
		foreach ($this->getDataObject()->getContent() as $content) {
			JatsText::extractText($content, $this);
		}
	}
}
