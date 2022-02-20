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

abstract class InfoBlock extends DataObject
{
    protected $label = null;
    protected $title = null;
    protected $bookmarkIds = array();
    protected $bookmarkText = ''; // TODO Check if there are situation where bookmark text is needed for JATS
    protected $references = array();

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
        $textNodes = Document::$xpath->query('./w:r/w:t', $el);
        foreach ($textNodes as $key => $textNode) {
            if ($key == 0) {
                $label .= $textNode->nodeValue;
            } else {
                $title .= $textNode->nodeValue;
            }
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
        return (!empty($this->references));
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @param \DOMElement $el
     * @return void
     * Identifies references in block element's textual data
     * Mendeley puts references in bookmarkStart element's name attribute, e.g.: <w:bookmarkStart w:id="3" w:name="Mendeley_Bookmark_6APrKwxfpO"/>
     * Zotero embraces references with fldChar elements (start - end), inside instructions, which start with CLS_CITATION, e.g.:
     * <w:instrText xml:space="preserve"> ADDIN ZOTERO_ITEM CSL_CITATION
     */
    protected function parseReferences(\DOMElement $el)
    {

    }

    abstract function getId(): int;
}
