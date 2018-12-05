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
		/*
		foreach ($this->getDataObject()->getContent() as $content) {
			if (empty($content->getType())) {
				$textNode = $this->ownerDocument->createTextNode($content->getContent());
				$this->appendChild($textNode);
			} elseif (in_array(Text::DOCX_TEXT_BOLD, $content->getType())) {
				$boldNode = $this->ownerDocument->createElement('bold', $content->getContent());
				$this->appendChild($boldNode);
			} elseif (in_array(Text::DOCX_TEXT_ITALIC, $content->getType())) {
				$italicNode = $this->ownerDocument->createElement('italic', $content->getContent());
				$this->appendChild($italicNode);
			} elseif (in_array(Text::DOCX_TEXT_EXTLINK, $content->getType())) {
				$linkNode = $this->ownerDocument->createElement('ext-link', $content->getContent());
				$this->appendChild($linkNode);
			} elseif (in_array(Text::DOCX_TEXT_SUBSCRIPT, $content->getType())) {
				$subNode = $this->ownerDocument->createElement('sub', $content->getContent());
				$this->appendChild($subNode);
			} elseif (in_array(Text::DOCX_TEXT_SUPERSCRIPT, $content->getType())) {
				$supNode = $this->ownerDocument->createElement('sup', $content->getContent());
				$this->appendChild($supNode);
			}
		}
		*/
		
	}
}
