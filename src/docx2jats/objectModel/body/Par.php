<?php namespace docx2jats\objectModel\body;

use docx2jats\objectModel\DataObject;

class Par extends DataObject {
	const DOCX_PAR_REGULAR = 1;
	const DOCX_PAR_HEADING = 2;
	
	/* @var $type int */
	private $type; // const
	private $properties = array();
	private $text = array();
	private $headings = array("1", "2", "3", "4", "5", "6", "heading", "heading1", "heading2", "heading3", "heading4", "heading5", "heading6");
	
	/* @var $level int */
	private $level;
	
	public function __construct(\DOMElement $domElement) {
		parent::__construct($domElement);
		$this->defineType();
		$this->properties = $this->setProperty('w:pPr/child::node()');
		$this->text = $this->setContent('w:r|w:hyperlink');
		$this->type = $this->defineType();
		$this->level = $this->setLevel();
	}
	
	/**
	 * @return array
	 */
	public function getProperties(): array {
		return $this->properties;
	}
	
	
	/**
	 * @return array
	 */
	public function getContent(): array {
		return $this->text;
	}
	
	/**
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}
	
	protected function setContent(string $xpathExpression) {
		$content = array();
		$contentNodes = $this->getXpath()->query($xpathExpression, $this->getDomElement());
		foreach ($contentNodes as $contentNode) {
			if ($contentNode->nodeName === "w:r") {
				$text = new Text($contentNode);
				$content[] = $text;
			} elseif ($contentNode->nodeName === "w:hyperlink") {
				$children = $this->getXpath()->query('child::node()', $contentNode);
				foreach ($children as $child) {
					$href = new Text($child);
					$href->addType($href::DOCX_TEXT_EXTLINK);
					$content[] = $href;
				}
			}
		}
		
		return $content;
	}
	
	/**
	 * @return int
	 */
	private function defineType() {
		$type = $this::DOCX_PAR_REGULAR;
		$styles = $this->getXpath()->query('w:pPr/w:pStyle/@w:val', $this->getDomElement());
		if ($this->isOnlyChildNode($styles)) {
			if (in_array(strtolower($styles[0]->nodeValue), $this->headings)) {
				$type = $this::DOCX_PAR_HEADING;
			}
			
		}
		
		return $type;
	}
	
	/**
	 * @return int $level
	 */
	private function setLevel() {
		$level = 0;
		$styleString = '';
		if ($this->type === $this::DOCX_PAR_HEADING) {
			$styles = $this->getXpath()->query('w:pPr/w:pStyle/@w:val', $this->getDomElement());
			if ($this->isOnlyChildNode($styles)) {
				$styleString = $styles[0]->nodeValue;
			}
		}
		
		// Not a heading if empty
		if (empty($styleString)) return $level;
		
		preg_match_all('/\d+/', $styleString, $matches);
		
		// Treat headings without a number as the 1st level headings
		if (empty($matches[0])) return $level+1;
		
		$level = intval(implode('', $matches[0]));
		
		return $level;
	}
	
	/**
	 * @return int
	 */
	public function getLevel(): int {
		return $this->level;
	}
}
