<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Row.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent JATS XML paragraph; can't be nested. To be included into body, sections, lists and table cells.
 */

use docx2jats\objectModel\body\Field;
use docx2jats\objectModel\DataObject;
use docx2jats\jats\Text as JatsText;

class Par extends Element {

	public function __construct(DataObject $dataObject)
	{
		parent::__construct($dataObject);

	}

	public function setContent() {

		foreach ($this->getDataObject()->getContent() as $content) {
			if (get_class($content) === 'docx2jats\objectModel\body\Field') {
				if ($content->getType() === Field::DOCX_FIELD_CSL) {
					$lastKey = array_key_last($content->getRefIds());
					foreach ($content->getRefIds() as $key => $id) {
						$refEl = $this->ownerDocument->createElement('xref', $id);
						$refEl->setAttribute('ref-type', 'bibr');
						$refEl->setAttribute('rid', 'bib' . $id);
						$this->appendChild($refEl);
						if ($key !== $lastKey) {
							$refEl = $this->ownerDocument->createTextNode(' ');
							$this->appendChild($refEl);
						}
					}
				}
			} else {
				JatsText::extractText($content, $this);
			}
		}
	}
}
