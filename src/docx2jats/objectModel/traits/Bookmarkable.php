<?php namespace docx2jats\objectModel\traits;

use docx2jats\objectModel\body\Reference;
use docx2jats\objectModel\Document;

/**
 * @file src/docx2jats/objectModel/trait/Bookmarkable.php
 *
 * Copyright (c) 2018-2022 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Use this trait for Objects which can contain bookmark
 */

trait Bookmarkable
{
	/**
	 * Mendeley plugin for LibreOffice Writer allows to export citation data to the OOXML
	 * the link to the ref is between w:bookmarkStart and w:bookmarkEnd elements, the children of the w:p
	 * Mendeley includes prefix Mendeley_Bookmark_... as an attribute value of w:name attribute of w:bookmarkStart element
	 * TODO implement bookmarks that span on several paragraphs
	 * @var $bookmarked bool whether Text is inside a bookmark
	 * @var $bookmarkData array of bookmarks
	 */
	public $bookmarked = false;
	protected $bookmarkData = array();
	public $hasCSLRefs = false;
	public $refIds = array();

	abstract protected function getDomElement(): \DOMElement;

	abstract protected function getOwnerDocument(): ?Document;

	/**
	 * Determine whether this element is inside a bookmarks
	 * Check previous siblings, find w:bookmarkStart or w:bookmarkEnd
	 * @param \DOMElement|null $rootElement Root element for the bookmark, always text run w:r
	 */
	protected function setBookmarks(?\DOMElement $rootElement = null): void {
		if (!$rootElement) {
			$rootElement = $this->getDomElement();
		}

		$prevSibling = $rootElement->previousSibling;
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
	protected function prevBookmarks(?\DOMElement $prevSibling, array $bookMarks): array {
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
	 * TODO search in other places
	 */
	public function searchBookmarkContentByName(string $bookmarkName): ?string {
		$customProps = $this->getOwnerDocument()->docPropsCustom();
		if (!$customProps) return null; // custom.xml or analog doesn't exist
		$propertyEls = $customProps->getElementsByTagName('property');
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

		$xpath = Document::$docPropsCustomXpath;
		if (!$xpath) return null;
		$resultString = '';
		foreach ($contentEls as $contentEl) {
			$lpwstr = $xpath->query('./vt:lpwstr[1]', $contentEl)[0];
			$resultString .= $lpwstr->nodeValue;
		}

		return $resultString;
	}

	public function getRefIds(): array
	{
		return $this->refIds;
	}

	function getBookmarkData(): array
	{
		return $this->bookmarkData;
	}
}
