<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/Text.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represents text with formatting
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;

class Text extends DataObject {
	const DOCX_TEXT_BOLD = 1;
	const DOCX_TEXT_ITALIC = 2;
	const DOCX_TEXT_SUPERSCRIPT = 3;
	const DOCX_TEXT_SUBSCRIPT = 4;
	const DOCX_TEXT_STRIKETHROUGH = 5;
	const DOCX_TEXT_EXTLINK = 6;

	private $properties;
	private $text;
	private $type = array();
	private $link;

	/**
	 * Mendeley plugin for LibreOffice Writer allows to export citation data to the OOXML
	 * the link to the ref is between w:bookmarkStart and w:bookmarkEnd elements, the children of the w:p
	 * Mendeley includes prefix Mendeley_Bookmark_... as an attribute value of w:name attribute of w:bookmarkStart element
	 * TODO implement bookmarks that span on several paragraphs
	 * @var $bookmarked bool whether Text is inside a bookmark
	 * @var $bookmarkData array of bookmarks
	 */
	public $bookmarked = false;
	private $bookmarkData = array();
	public $hasCSLRefs = false;
	public $refIds = array();

	public function __construct(\DOMElement $domElement, Document $ownerDocument) {
		parent::__construct($domElement, $ownerDocument);
		$this->properties = $this->setProperties('w:rPr/child::node()');
		$this->text = $this->setText('w:t');
		$this->type = $this->setType();
		$this->setBookmarks();
	}

	/**
	 * @return string
	 */

	private function setText(string $xpathExpression) {
		$stringText = '';
		$contentNodes = $this->getXpath()->evaluate($xpathExpression, $this->getDomElement());
		/* @var $contentNode \DOMElement */
		foreach ($contentNodes as $contentNode) {
			$stringText = $stringText . $contentNode->nodeValue;
		}

		return $stringText;
	}

	/**
	 * @return string
	 */
	public function getContent(): string {
		return $this->text;
	}

	/**
	 * @return array
	 */
	public function getProperties(): array {
		return $this->properties;
	}

	/**
	 * For a toggle property, (see ECMA-376 Part 1, 17.7.3), determine
	 * its enabled state.
	 *
	 * @return bool
	 */
	private function togglePropertyEnabled(\DOMElement $property): bool {
		if ($property->hasAttribute('w:val')) {
			$attrValue = $property->getAttribute('w:val');
			return ($attrValue == '1' || $attrValue == 'true');
		} else {
			return true; // No value means it's enabled
		}
	}

	/**
	 * @return array
	 */
	private function setType() {
		$type = array();

		$properties = $this->getXpath()->query('w:rPr/child::node()', $this->getDomElement());
		foreach ($properties as $property) {
			switch($property->nodeName) {
				case "w:b":
					if ($this->togglePropertyEnabled($property)) {
						$type[] = $this::DOCX_TEXT_BOLD;
					}
					break;
				case "w:i":
					if ($this->togglePropertyEnabled($property)) {
						$type[] = $this::DOCX_TEXT_ITALIC;
					}
					break;
				case "w:vertAlign":
					if ($property->hasAttribute('w:val')) {
						$attrValue = $property->getAttribute('w:val');
						if ($attrValue === "superscript") {
							$type[] = $this::DOCX_TEXT_SUPERSCRIPT;
						} elseif ($attrValue === "subscript") {
							$type[] = $this::DOCX_TEXT_SUBSCRIPT;
						}
					}
					break;
				case "w:strike":
					if ($this->togglePropertyEnabled($property)) {
						$type[] = $this::DOCX_TEXT_STRIKETHROUGH;
					}
					break;
			}
		}

		return $type;
	}

	public function addType(string $type): void {
		$this->type[] = $type;
	}

	/**
	 * @return array
	 */
	public function getType(): array {
		return $this->type;
	}

	function setLink(): void {
		$parent = $this->getDomElement()->parentNode;
		if ($parent->tagName == "w:hyperlink") {
			$ref = $parent->getAttribute("r:id");
			// TODO link by other attributes for identification, e.g. w:anchor
			if ($ref) {
				$this->link = Document::getRelationshipById($ref);
			}
		}
	}

	public function getLink(): ?string {
		return $this->link;
	}

	function getBookmarkData(): array {
		return $this->bookmarkData;
	}

	/**
	 * Determine whether this element is inside a bookmarks
	 * Check previous siblings, find w:bookmarkStart or w:bookmarkEnd
	 */
	private function setBookmarks(): void {
		$prevSibling = $this->getDomElement()->previousSibling;
		$bookMarks = array_fill_keys(['started', 'ended'], []);
		$bookMarks = $this->prevBookmarks($prevSibling, $bookMarks);
		$bookMarksActive = array_diff($bookMarks['started'], $bookMarks['ended']);
		if (empty($bookMarksActive)) return;

		$this->bookmarked = true;
		$allBookmarks = $this->getOwnerDocument()->bookMarks;
		foreach ($bookMarksActive as $bookMarkActive) {
			$name = $allBookmarks[$bookMarkActive];
			$this->bookmarkData[$bookMarkActive]['name'] = $name ;
			$content = $this->searchBookmarkContentByName($name);
			if (empty(trim($content)) || strpos($content, 'CSL_CITATION') === false) continue;

			$this->bookmarkData[$bookMarkActive]['content'] = $content;
			list($instructions, $rawCSL) = Reference::extractRawCSL($content);
			if (empty($rawCSL)) continue;

			$citations = Reference::findRefsCSL($rawCSL);
			if (empty($citations)) continue;
			$this->hasCSLRefs = true;

			foreach ($citations as $citation) {
				if (!$ref = Reference::cslExists($citation, $this->getOwnerDocument())) {
					$this->getOwnerDocument()->addReference($citation);
					$this->refIds[] = $citation->getId();
				} else {
					$this->refIds[] = $ref->getId();
				}
			}
		}
		$this->refIds = array_unique($this->refIds); // TODO explore why Mendeley LW plugin includes duplicates of refs into a single text run
	}

	/**
	 * @param \DOMElement $prevSibling
	 * @param array $bookMarks
	 * @return array
	 * @brief recursively find started and ended bookmarks
	 */
	private function prevBookmarks(?\DOMElement $prevSibling, array $bookMarks): array {
		if (is_null($prevSibling)) return $bookMarks; // reached end
		if ($prevSibling->nodeType !== XML_ELEMENT_NODE) return $this->prevBookmarks($prevSibling->previousSibling, $bookMarks);

		if ($prevSibling->tagName === 'w:bookmarkStart') {
			$bookMarks['started'][] = $prevSibling->getAttribute('w:id');
		}

		if ($prevSibling->tagName === 'w:bookmarkEnd') {
			$bookMarks['ended'][] = $prevSibling->getAttribute('w:id');
		}

		return $this->prevBookmarks($prevSibling->previousSibling, $bookMarks);
	}

	/**
	 * @param int $id
	 * @return string
	 * @brief search for the bookmarks content in the docProps/custom.xml by bookmarkStart ID
	 * particularly this is for searching of CSL Mendeley references
	 */
	public function searchBookmarkContentByName(string $bookmarkName): ?string {
		$xpath = Document::$docPropsCustomXpath;
		$propertyEls = $this->getOwnerDocument()->docPropsCustom()->getElementsByTagName('property');
		$contentEls = [];
		$nameLen = strlen($bookmarkName);
		foreach ($propertyEls as $propertyEl) { /* @var $propertyEl \DOMElement */
			if ($propertyEl->hasAttribute('name')) {
				$attrValue = $propertyEl->getAttribute('name');
				// attribute value consists of a name and unique ending
				if (substr($attrValue, 0, $nameLen) === $bookmarkName) {
					$contentEls[] = $propertyEl;
				}
			}
		}

		if (empty($contentEls)) return null;

		/**
		 * Needs to be sorted as property elements can be present inside the parent in any order;
		 * Consider 2 values of name attr: Mendeley_Bookmark_3XXM13m2wL_14 Mendeley_Bookmark_3XXM13m2wL_2,
		 * Sorting is done by comparing trailing numbers that appear after last underscore
		 */
		usort($contentEls, function ($a, $b) use ($nameLen) {
			$aInt = filter_var(substr($a->getAttribute('name'), $nameLen), FILTER_SANITIZE_NUMBER_INT);
			$bInt = filter_var(substr($b->getAttribute('name'), $nameLen), FILTER_SANITIZE_NUMBER_INT);
			if ($aInt === $bInt) return 0;
			return ($aInt < $bInt) ? -1 : 1;
		});

		$resultString = '';
		foreach ($contentEls as $contentEl) {
			$lpwstr = $xpath->query('./vt:lpwstr[1]', $contentEl)[0];
			$resultString .= $lpwstr->nodeValue;
		}

		return $resultString;
	}
}
