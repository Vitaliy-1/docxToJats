<?php namespace docx2jats\jats;

use docx2jats\DOCXArchive;

class Document extends \DOMDocument {
	private $docxArchive;
	
	public function __construct(DOCXArchive $docxArchive) {
		parent::__construct('1.0', 'utf-8');
		$this->docxArchive = $docxArchive;
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		
		$this->setBasicStructure();
		$this->extractContent();
	}
	
	public function getJatsFile() {
		$this->save("test_jats.xml");
	}
	
	private function setBasicStructure() {
		$article = $this->createElement('article');
		$this->appendChild($article);
		
		$front = $this->createElement('front');
		$article->appendChild($front);
		$body = $this->createElement('body');
		$article->appendChild($body);
		$back = $this->createElement('back');
		$article->appendChild($back);
	}
	
	private function extractContent() {
		$document = $this->docxArchive->getDocument();
		if (!empty($document->getContent())) {
			foreach ($document->getContent() as $content) {
			
			}
		}
	}
}
