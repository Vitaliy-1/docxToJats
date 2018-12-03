<?php namespace docx2jats\objectModel\body;

use docx2jats\objectModel\DataObject;

/**
 * Class Par
 * @package docx2jats\objectModel\body
 * @brief represent paragraph in OOXML, includes: regular paragraph, lists, heading and other parapgraph styles
 */
class Par extends DataObject {
	const DOCX_PAR_REGULAR = 1;
	const DOCX_PAR_HEADING = 2;
	const DOCX_PAR_LIST = 3;
	
	private $type = array(); // const
	private $properties = array();
	private $text = array();
	public static $headings = array("1", "2", "3", "4", "5", "6", "heading", "heading1", "heading2", "heading3", "heading4", "heading5", "heading6");
	
	/* @var $headingLevel int */
	private $headingLevel;
	
	/* @var $numberingLevel int */
	private $numberingLevel;
	
	/* @var $numberingId int */
	private $numberingId;
	
	public function __construct(\DOMElement $domElement) {
		parent::__construct($domElement);
		$this->defineType();
		$this->properties = $this->setProperties('w:pPr/child::node()');
		$this->text = $this->setContent('w:r|w:hyperlink');
		$this->type = $this->defineType();
		$this->headingLevel = $this->setHeadingLevel();
		$this->numberingLevel = $this->setNumberingLevel();
		$this->numberingId = $this->setNumberingId();
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
	 * @return array
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
	 * @return array
	 */
	private function defineType() {
		$type = array();
		$styles = $this->getXpath()->query('w:pPr/w:pStyle/@w:val', $this->getDomElement());
		if ($this->isOnlyChildNode($styles)) {
			if (in_array(strtolower($styles[0]->nodeValue), self::$headings)) {
				$type[] = self::DOCX_PAR_HEADING;
			}
			
		}
		
		$numberingNode = $this->getXpath()->query('w:pPr/w:numPr', $this->getDomElement());
		if ($this->isOnlyChildNode($numberingNode)) {
			$type[] = self::DOCX_PAR_LIST;
		}
		
		if (empty($type)) {
			$type[] = self::DOCX_PAR_REGULAR;
		}
		
		return $type;
	}
	
	/**
	 * @return int $level
	 */
	private function setHeadingLevel() {
		$level = 0;
		$styleString = '';
		if (in_array(self::DOCX_PAR_HEADING, $this->type )) {
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
	public function getHeadingLevel(): int {
		return $this->headingLevel;
	}
	
	/**
	 * @return int
	 */
	private function setNumberingLevel(): int {
		$numberingLevel = 0;
		$numberString = '';
		if (in_array(self::DOCX_PAR_LIST, $this->type)) {
			$numberNode = $this->getXpath()->query('w:pPr/w:numPr/w:ilvl/@w:val', $this->getDomElement());
			if ($this->isOnlyChildNode($numberNode)) {
				$numberString = $numberNode[0]->nodeValue;
			}
		}
		
		if (empty($numberString)) return $numberingLevel;
		
		$numberingLevel = intval($numberString);
		
		return $numberingLevel;
	}
	
	/**
	 * @return int
	 */
	public function getNumberingLevel(): int {
		return $this->numberingLevel;
	}
	
	/**
	 * @return int
	 */
	private function setNumberingId(): int {
		$numberingId = 0;
		$numberString = '';
		if (in_array(self::DOCX_PAR_LIST, $this->type)) {
			$numberNode = $this->getXpath()->query('w:pPr/w:numPr/w:numId/@w:val', $this->getDomElement());
			if ($this->isOnlyChildNode($numberNode)) {
				$numberString = $numberNode[0]->nodeValue;
			}
		}
		
		if (empty($numberString)) return $numberingId;
		
		$numberingId = intval($numberString);
		
		return $numberingId;
	}
	
	
	/**
	 * @return int
	 */
	public function getNumberingId(): int {
		return $this->numberingId;
	}
}
