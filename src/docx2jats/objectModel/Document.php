<?php namespace docx2jats\objectModel;

/**
 * @file src/docx2jats/objectModel/Document.php
 *
 * Copyright (c) 2018-2020 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief representation of an article; extracts all main elements from DOCX document.xml
 */

use docx2jats\jats\Figure;
use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Par;
use docx2jats\objectModel\body\Table;
use docx2jats\objectModel\body\Image;
use docx2jats\objectModel\body\Reference;

class Document {
	const SECT_NESTED_LEVEL_LIMIT = 5; // limit the number of possible levels for sections

	// Represent styling for OOXMl structure elements
	const DOCX_STYLES_PARAGRAPH = "paragraph";
	const DOCX_STYLES_CHARACTER = "character";
	const DOCX_STYLES_NUMBERING = "numbering";
	const DOCX_STYLES_TABLE = "table";

	static $xpath;
	private $content;
	private static $minimalHeadingLevel;

	/* @var $relationships \DOMDocument contains relationships between document elements, e.g. the link and its target */
	private $relationships;
	static $relationshipsXpath;

	/* @var $styles \DOMDocument represents document styles, e.g., paragraphs or lists styling */
	private $styles;
	static $stylesXpath;

	/* @var $numbering \DOMDocument represents info about list/heading level and style */
	private $numbering;
	static $numberingXpath;

	private $references = array();
	private $refCount = 0;

	// Set unique IDs for tables and figure in order of appearance
	private $currentFigureId = 1;
	private $currentTableId = 1;

	/**
	 * @var $parsHaveBookmarks array
	 * @brief Key numbers of paragraphs that contain bookmarks inside the content
	 * is used to speed up a search
	 */
	private $elsHaveBookmarks = array();
	private $elsAreTables = array();
	private $elsAreFigures = array();

	public function __construct(array $params) {
		if (array_key_exists("relationships", $params)) {
			$this->relationships = $params["relationships"];
			self::$relationshipsXpath = new \DOMXPath($this->relationships);
		}

		if (array_key_exists("styles", $params)) {
			$this->styles = $params["styles"];
			self::$stylesXpath = new \DOMXPath($this->styles);
		}

		if (array_key_exists("numbering", $params)) {
			$this->numbering = $params["numbering"];
			self::$numberingXpath = new \DOMXPath($this->numbering);
		}

		self::$xpath = new \DOMXPath($params["ooxmlDocument"]);

		$childNodes = self::$xpath->query("//w:body/child::node()");

		$content = array();
		$unUsedCaption = null;
		foreach ($childNodes as $key => $childNode) {
			// Assign block elements, i.e., Figures, Tables, Paragraphs, depending on the context
			switch ($childNode->nodeName) {
				case "w:p":
					/**
					 * TODO add support for other drawings type, e.g., c:chart
					 * Figures are contained inside paragraphs, particularly - in text runs;
					 * there may be several images each inside own text run.
					 * In addition, LibreOffice Writer's DOCX export includes 2 duplicates of drawings for compatibility reasons
					 */
					if ($this->isDrawing($childNode)) {
						$drawingEls = null;
						$textRuns = self::$xpath->query('w:r', $childNode);
						foreach ($textRuns as $textRun) {
							// Retrieve only first one (LibreOffice Writer duplicates with a fallback option
							$checkDrawingEl = self::$xpath->query('.//w:drawing[1]', $textRun)[0];
							if ($checkDrawingEl) $drawingEls[] = $checkDrawingEl;
						}
						if (empty($drawingEls)) break;

						foreach ($drawingEls as $drawingEl) {
							// check if contains image, charts aren't supported
							self::$xpath->registerNamespace("pic", "http://schemas.openxmlformats.org/drawingml/2006/picture");
							$imageNodes = self::$xpath->query(".//pic:pic", $drawingEl);
							if ($imageNodes->length === 0) break;

							$figure = new Image($drawingEl, $this);
							$content[] = $figure;

							// Get coordinates for this figure
							$this->elsAreFigures[] = count($content) - 1;

							// Set unique ID
							$figure->setFigureId($this->currentFigureId++);

							// Set caption if exists
							if ($unUsedCaption) {
								$figure->setCaption($unUsedCaption);
								$unUsedCaption = null;
							}
						}

					} elseif ($this->isCaption($childNode)) {
						// Check if previous node is drawing or table
						$prevObject =& $content[array_key_last($content)];
						if (get_class($prevObject) === 'docx2jats\objectModel\body\Table' || get_class($prevObject) === 'docx2jats\objectModel\body\Image') {
							$prevObject->setCaption($childNode);
						} else {
							$unUsedCaption = $childNode;
						}
					} else {
						$par = new Par($childNode, $this);
						if (in_array(Par::DOCX_PAR_REF, $par->getType())) {
							if (!empty(trim($par->toString()))) {
								$reference = new Reference($par->toString());
								$this->addReference($reference);
							}
						} else {
							$content[] = $par;
						}

						if ($par->hasBookmarks) {
							$this->elsHaveBookmarks[] = count($content)-1;
						}
					}
					break;
				case "w:tbl":
					$table = new Table($childNode, $this);
					$content[] = $table;
					$this->elsAreTables[] = count($content)-1;

					// Set unique ID
					$table->setTableId($this->currentTableId++);
					// Set caption if exists
					if ($unUsedCaption) {
						$table->setCaption($unUsedCaption);
						$unUsedCaption = null;
					}
					break;
			}
		}

		$this->content = $this->addSectionMarks($content);
		self::$minimalHeadingLevel = $this->minimalHeadingLevel();
		$this->setInternalRefs();
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

	static function getRelationshipById(string $id): string {
		$element = self::$relationshipsXpath->query("//*[@Id='" .  $id ."']");
		$target = $element[0]->getAttribute("Target");
		return $target;
	}

	static function getElementStyling(string $constStyleType, string $id): ?string {
		/* @var $element \DOMElement */
		/* @var $name \DOMElement */
		if (self::$stylesXpath) {
			$element = self::$stylesXpath->query("/w:styles/w:style[@w:type='" . $constStyleType . "'][@w:styleId='" . $id . "']")[0];
			$name = self::$stylesXpath->query("w:name", $element)[0];
			return $name->getAttribute("w:val");
		} else {
			return null;
		}
	}

	static function getBuiltinStyle(string $constStyleType, string $id, array $builtinStyles): ?string {
		// Traverse the chain of styles to see if the named id style
		// inherits from one of the sought-for built-in styles and
		// return the one that matches.
		if (self::$stylesXpath) {
			do {
				$element = self::$stylesXpath->query("/w:styles/w:style[@w:type='" . $constStyleType . "'][@w:styleId='" . $id . "']")[0];

				$basedOn = self::$stylesXpath->query("w:basedOn", $element)[0];
				$id = $basedOn ? $basedOn->getAttribute("w:val") : null;

				$name = self::$stylesXpath->query("w:name", $element)[0];
				$styleName = $name->getAttribute("w:val");

				if (in_array(strtolower($styleName), $builtinStyles)) return $styleName;
			} while($id);

			return null;
		} else {

			// Fall back on using the original id as if it were the name
			if (in_array($id, $builtinStyles)) return $id;
			else return null;
		}
	}


	private function isDrawing($childNode): bool {
		$element = Document::$xpath->query("w:r//w:drawing", $childNode)[0];
		if ($element) return true;
		return false;
	}

	/**
	 * @param $childNode
	 * @return bool
	 * @brief determines if an element is caption
	 */
	function isCaption($childNode): bool {
		$elementStyle = Document::$xpath->query("w:pPr/w:pStyle/@w:val", $childNode)[0];
		if (is_null($elementStyle)) return false;

		if (Document::getBuiltinStyle(Document::DOCX_STYLES_PARAGRAPH, $elementStyle->nodeValue, Table::$caption)) {
			return true;
		}

		return false;
	}

	static function getNumberingTypeById(string $id, string $lvl): ?string {
		if (!self::$numberingXpath) return null; // the numbering styles are missing.

		$element = self::$numberingXpath->query("//*[@w:abstractNumId='" . $id . "']");
		if ($element->count() == 0) return null;

		$level = self::$numberingXpath->query("w:lvl[@w:ilvl='" . $lvl . "']", $element[0]);
		if ($level->count() == 0) return null;

		$type = self::$numberingXpath->query("w:numFmt/@w:val", $level[0]);
		if ($type->count() == 0) return null;

		return $type[0]->nodeValue;
	}

	public function addReference(Reference $reference) {
		$this->refCount++;
		$reference->setId($this->refCount);
		$this->references[$this->refCount] = $reference;
	}

	public function getReferences() : array {
		return $this->references;
	}

	public function getLastReference() : ?Reference {
		$lastId = array_key_last($this->references);
		return $this->references[$lastId];
	}

	/**
	 * @brief iterate through the content and establish internal links between element
	 * elsHaveBookmarks holds position in an array of each paragraph that includes a bookmark
	 * it's slightly faster than looping over the whole content
	 */
	private function setInternalRefs(): void {
		if (empty($this->elsHaveBookmarks)) return;

		// Find and map tables' and figures' bookmarks
		$refTableMap = $this->getBookmarkCaptionMapping($this->elsAreTables);
		$refFigureMap = $this->getBookmarkCaptionMapping($this->elsAreFigures);

		// Find bookmark refs
		foreach ($this->elsHaveBookmarks as $parKeyWithBookmark) {
			$par = $this->getContent()[$parKeyWithBookmark]; /* @var $par Par */
			foreach ($par->bookmarkPos as $fieldKeyWithBookmark) {
				$field = $par->getContent()[$fieldKeyWithBookmark]; /* @var $field \docx2jats\objectModel\body\Field */

				// Set links to tables
				foreach ($refTableMap as $tableId => $tableRefs) {
					if (in_array($field->getBookmarkId(), $tableRefs)) {
						$field->tableIdRef = $tableId;
					}
				}

				// Set links to Figures
				foreach ($refFigureMap as $figureId => $figureRefs) {
					if (in_array($field->getBookmarkId(), $figureRefs)) {
						$field->figureIdRef = $tableId;
					}
				}
			}
		}
	}

	/**
	 * @return array
	 * @brief (or not so brief) Map OOXML bookmark refs inside table and figures with correspondent table/figure IDs.
	 * In OOXML those bookmarks are stored inside captions
	 * This is used to set right link to these objects from the text
	 * Keep in mind that bookmarks also may be stored in an external file, e.g., Mendeley plugin for LibreOffice Writer
	 * stores links to references this way
	 */
	function getBookmarkCaptionMapping(array $keysInContent): array {
		$refMap = [];
		foreach ($keysInContent as $tableKey) {
			$table = $this->content[$tableKey]; /* @var $table Table|Image */
			if (empty($table->getBookmarkIds())) continue;
			$refMap[$table->getId()] = $table->getBookmarkIds();
		}

		return $refMap;
	}
}
