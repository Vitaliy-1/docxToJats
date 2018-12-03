<?php namespace docx2jats\objectModel;

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Par;
use docx2jats\objectModel\body\Table;

class Document {
	const SECT_NESTED_LEVEL_LIMIT = 5; // limit the number of possible levels for sections
	
	public static $xpath;
	private $content;
	private static $minimalHeadingLevel;
	
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
		
		$this->content = $this->addSectionMarks($content);
		self::$minimalHeadingLevel = $this->minimalHeadingLevel();
	}
	
	/**
	 * @return array
	 */
	public function getContent(): array {
		return $this->content;
	}
	
	private function minimalHeadingLevel(): int {
		$minimalNumber = 7;
		foreach ($this->content as $dataObject) {
			if (get_class($dataObject) === "docx2jats\objectModel\body\Par" && in_array(Par::DOCX_PAR_LIST, $dataObject->getType())) {
				$number = $dataObject->getHeadingLevel();
				if ($number && $number < $minimalNumber) {
					$minimalNumber = $number;
				}
			}
		}
		
		return $minimalNumber;
	}
	
	/**
	 * @return int
	 */
	public static function getMinimalHeadingLevel(): int {
		return self::$minimalHeadingLevel;
	}
	
	/**
	 * @param array $content
	 * @return array
	 * @brief set marks for the section, number in order and specific ID for nested sections
	 */
	private function addSectionMarks(array $content): array {
		
		$flatSectionId = 0; // simple section id
		$dimensions = array_fill(0, self::SECT_NESTED_LEVEL_LIMIT, 0); // contains dimensional section id
		foreach ($content as $key => $object) {
			if (get_class($object) === "docx2jats\objectModel\body\Par" && $object->getType() && $object->getHeadingLevel()) {
				$flatSectionId++;
				$dimensions = $this->extractSectionDimension($object, $dimensions);
			}
			
			$object->setDimensionalSectionId($dimensions);
			$object->setFlatSectionId($flatSectionId);
		}
		
		return $content;
	}
	
	/**
	 * @param $object Par
	 * @param array $dimensions
	 * @param int $n
	 * @return array
	 * @brief for internal use, defines dimensional section id for a given Par heading
	 */
	private function extractSectionDimension(Par $object, array $dimensions): array
	{
		$number = $object->getHeadingLevel() - 1;
		$dimensions[$number]++;
		while ($number < self::SECT_NESTED_LEVEL_LIMIT) {
			$number++;
			$dimensions[$number] = 0;
		}
		return $dimensions;
	}
}
