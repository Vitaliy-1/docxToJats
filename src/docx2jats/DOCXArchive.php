<?php namespace docx2jats;

use docx2jats\objectModel\Document;

class DOCXArchive extends \ZipArchive {
	
	private $ooxmlDocument;
	private $document;
	
	public function __construct(string $filepath) {
		if ($this->open($filepath)) {
			$document = $this->locateName("word/document.xml");
			$data = $this->getFromIndex($document);
			$this->close();
			$xml = new \DOMDocument();
			$xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
			$this->ooxmlDocument = $xml;
			$document = new Document($this->ooxmlDocument);
			$this->document = $document;
		}
	}
	
	public function getDocumentOoxml(): \DOMDocument {
		return $this->ooxmlDocument;
	}
	
	public function getDocument(): Document {
		return $this->document;
	}
	
}