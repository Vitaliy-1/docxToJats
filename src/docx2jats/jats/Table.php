<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Table.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent JATS XML table
 */

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Row as JatsRow;

class Table extends Element {

	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}

	public function setContent() {

		// TODO create and append table label and caption

		$tableNode = $this->ownerDocument->createElement('table');
		$this->appendChild($tableNode);

		foreach ($this->getDataObject()->getContent() as $content) {
			$row = new JatsRow($content);
			$tableNode->appendChild($row);
			$row->setContent();
		}
	}
}
