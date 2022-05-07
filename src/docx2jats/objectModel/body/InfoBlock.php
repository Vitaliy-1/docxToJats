<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/InfoBlock.php
 *
 * Copyright (c) 2018-2022 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Block with information inside the document flow
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;
use docx2jats\objectModel\traits\Bookmarkable;

abstract class InfoBlock extends DataObject
{
	use Bookmarkable;

    protected $label = null;
    protected $title = null;
    protected $bookmarkIds = array();
    protected $bookmarkText = ''; // TODO Check if there are situation where bookmark text is needed for JATS

    /**
     * @param \DOMElement $el
     * @brief retrieve data from caption DOMElement
     */
    public function setCaption(\DOMElement $el): void
    {
        $label = '';
        $title = '';

        // Check if caption has references and parse them
        $this->parseReferences($el);

        // Identifying and parsing block element's label and title
        $runs = Document::$xpath->query('./w:r', $el);
        foreach ($runs as $key => $run) {
			$textNodes = Document::$xpath->query('./w:t', $run);
			if (!count($textNodes)) continue;

			// Just assuming that the first text run is a label, default for MS Word and Libreoffice Writer
            if ($key == 0) {
                $label .= $textNodes[0]->nodeValue;
            }

			// This detects if text run contains a bookmark
			$refCount = count($this->refIds);
	        $this->setBookmarks($run);
			if (count($this->refIds) > $refCount) continue; // Don't parse text if a text run

	        $title .= $textNodes[0]->nodeValue;
        }

        $labelNumber = Document::$xpath->query('./w:fldSimple//w:t', $el)[0];
        if (!is_null($labelNumber)) {
            $label .= $labelNumber->nodeValue;
        }

        if (!empty($label)) {
            $this->label = $label;
        }

        if (!empty($title)) {
            $this->title = trim($title);
        }

        // Caption may have bookmarks that are pointed from outside the table, retrieve their IDs;
        // TODO Check if other bookmark types may be inserted in captions
        $bookmarkStartEls = Document::$xpath->query('w:bookmarkStart', $el);
        foreach ($bookmarkStartEls as $bookmarkStartEl) {
            /* @var $bookmarkStartEl \DOMElement */
            if ($bookmarkStartEl->hasAttribute('w:name')) {
                $this->bookmarkIds[] = $bookmarkStartEl->getAttribute('w:name');
            }
        }
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getBookmarkIds(): array
    {
        return $this->bookmarkIds;
    }

    public function hasReferences(): bool
    {
        return (!empty($this->refIds));
    }

    /**
     * @param \DOMElement $el
     * @return void
     * Identifies references in block element's textual data
     * Mendeley puts references in bookmarkStart element's name attribute, e.g.: <w:bookmarkStart w:id="3" w:name="Mendeley_Bookmark_6APrKwxfpO"/>
     * Zotero embraces references with fldChar elements (start - end), inside instructions, which start with CLS_CITATION, e.g.:
     * <w:instrText xml:space="preserve"> ADDIN ZOTERO_ITEM CSL_CITATION
     * TODO Refactor and remove artificial Field element
     */
    protected function parseReferences(\DOMElement $el): void
    {
        $contentNodes = $this->getXpath()->query('./w:r', $el);

        // Iterating through all text runs to find fields with CSL citations
        $field = null;
        foreach ($contentNodes as $contentNode) {
            if (!$field && Field::complexFieldStarts($contentNode)) {
                $field = new Field($contentNode, $this->getOwnerDocument(), $this);
            } elseif ($field && !Field::complexFieldLast($contentNode)) {
                $field->addContent($contentNode);
            } elseif ($field && Field::complexFieldLast($contentNode)) {
                $field->addContent($contentNode);
                // Field data is recorded, write a reference if exists
                if ($field->getType() === Field::DOCX_FIELD_CSL) {
                    $this->addRefIds($field->getRefIds());
                }
            }
        }

		// Try to find Mendeley references
	    if (empty($this->bookmarkIds)) return;
    }

	protected function addRefIds(array $ids) {
		$this->refIds = array_merge(
			$this->refIds,
			$ids
		);
	}

    abstract function getId(): int;
}
