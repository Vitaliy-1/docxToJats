<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Row.php
 *
 * Copyright (c) 2018-2020 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent JATS XML paragraph; can't be nested. To be included into body, sections, lists and table cells.
 */

use docx2jats\objectModel\body\Field;
use docx2jats\objectModel\DataObject;
use docx2jats\jats\Text as JatsText;

class Par extends Element {

	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}

	public function setContent() {
		$prevTextRefs = [];
		foreach ($this->getDataObject()->getContent() as $content) {
			if (get_class($content) === 'docx2jats\objectModel\body\Field') {
				// Write links to references from Zotero and Mendeley plugin for MS Word
				if ($content->getType() === Field::DOCX_FIELD_CSL) {
					$lastKey = array_key_last($content->getRefIds());
					foreach ($content->getRefIds() as $key => $id) {
						$refEl = $this->ownerDocument->createElement('xref', $id);
						$refEl->setAttribute('ref-type', 'bibr');
						$refEl->setAttribute('rid', Reference::JATS_REF_ID_PREFIX . $id);
						$this->appendChild($refEl);
						if ($key !== $lastKey) {
							$refEl = $this->ownerDocument->createTextNode(' ');
							$this->appendChild($refEl);
						}
					}
				}
				// Write links to table and figures
				elseif ($content->getType() === Field::DOCX_FIELD_BOOKMARK_REF) {
					$refEl = $this->ownerDocument->createElement('xref');
					$this->appendChild($refEl);
					foreach ($content->getContent() as $text) { /* @var $text \docx2jats\objectModel\body\Text */
						JatsText::extractText($text, $refEl);
					}
					if ($tableIdRef = $content->tableIdRef) {
						$refEl->setAttribute('ref-type', 'table');
						$refEl->setAttribute('rid', Table::JATS_TABLE_ID_PREFIX . $tableIdRef);
					} elseif ($figureIdRef = $content->figureIdRef) {
						$refEl->setAttribute('ref-type', 'fig');
						$refEl->setAttribute('rid', Figure::JATS_FIGURE_ID_PREFIX . $figureIdRef);
					}
				}
				$prevTextRefs = []; // restart track of refs from Mendeley LW plugin
			} else {
				// Write links to references from Mendeley plugin for LibreOffice Writer
				/* @var $content \docx2jats\objectModel\body\Text */
				if ($content->hasCSLRefs) {
					$currentRefs = $content->refIds;
					foreach ($currentRefs as $currentRefId) {
						if (!in_array($currentRefId, $prevTextRefs)) {
							$refEl = $this->ownerDocument->createElement('xref', $currentRefId);
							$this->appendChild($refEl);
							$refEl->setAttribute('ref-type', 'bibr');
							$refEl->setAttribute('rid', Reference::JATS_REF_ID_PREFIX . $currentRefId);
						}
					}
					$prevTextRefs = $currentRefs;
				}
				// Write other text
				else {
					JatsText::extractText($content, $this);
					$prevTextRefs = []; // restart track of refs from Mendeley LW plugin
				}
			}
		}
	}
}
