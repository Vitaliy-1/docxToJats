<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Row.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent JATS XML paragraph; can't be nested. To be included into body, sections, lists and table cells.
 */

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Text as JatsText;

class Par extends Element {

	public function __construct(DataObject $dataObject)
	{
		parent::__construct($dataObject);

	}

	public function setContent() {

		foreach ($this->getDataObject()->getContent() as $content) {
			JatsText::extractText($content, $this);
		}
	}
}
