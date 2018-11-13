<?php namespace docx2jats\objectModel;

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Par;
use docx2jats\objectModel\body\Table;

class Document {
	
	public static $xpath;
	private $content;
	
	public function __construct(\DOMDocument $domDocument) {
		self::$xpath = new \DOMXPath($domDocument);
		
		$childNodes = self::$xpath->query("//w:body/child::node()");
		
		$content = array();
		foreach ($childNodes as $childNode) {
			switch ($childNode->nodeName) {
				case "w:p":
					$par = new Par($childNode);
					$content[] = $par;
					break;
				case "w:tbl":
					$table = new Table($childNode);
					$content[] = $table;
					break;
			}
		}
		
		$this->content = $content;
	}
	
	/**
	 * @return array
	 */
	public function getContent(): array {
		return $this->content;
	}
	
}
