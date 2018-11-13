<?php namespace docx2jats\objectModel;

use docx2jats\objectModel\Document;
use docx2jats\objectModel\body\Text;

abstract class DataObject {
	
	private $domElement;
	private $xpath;
	
	public function __construct(\DOMElement $domElement)   {
		$this->domElement = $domElement;
		$this->xpath = Document::$xpath;
	}
	
	protected function getXpath(): \DOMXPath {
		return $this->xpath;
	}
	
	protected function setProperty(string $xpathExpression): array {
		$styleNodes = $this->getXpath()->evaluate($xpathExpression, $this->domElement);
		$properties = $this->extractPropertyRecursion($styleNodes);
		
		return $properties;
	}
	
	/**
	 * @return \DOMElement
	 */
	protected function getDomElement(): \DOMElement {
		return $this->domElement;
	}
	
	/**
	 * @param \DOMNodeList $domNodeList
	 * @return bool
	 */
	protected function isOnlyChildNode(\DOMNodeList $domNodeList): bool {
		if ($domNodeList->count() === 1) {
			return true;
		}
		return false;
	}
	
	/**
	 * @param $styleNodes
	 * @return array
	 */
	private function extractPropertyRecursion($styleNodes): array
	{
		$properties = array();
		foreach ($styleNodes as $styleNode) {
			if ($styleNode->hasAttributes()) {
				foreach ($styleNode->attributes as $attr) {
					$properties[$styleNode->nodeName][$attr->nodeName] = $attr->nodeValue;
				}
			} elseif  ($styleNode->hasChildNodes()) {
				$children = $this->getXpath()->query('child::node()', $styleNode);
				$rPr = $this->extractPropertyRecursion($children);
				$properties[$styleNode->nodeName] = $rPr;
			}
		}
		return $properties;
	}
}
