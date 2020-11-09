<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Table.php
 *
 * Copyright (c) 2018-2020 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent JATS XML table
 */

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Row as JatsRow;

class Table extends Element {

	const JATS_TABLE_ID_PREFIX = 'tbl';

	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}

	public function setContent() {
		$dataObject = $this->getDataObject(); /* @var $dataObject \docx2jats\objectModel\body\Table */

		if ($dataObject->getId()) {
			$this->setAttribute('id', self::JATS_TABLE_ID_PREFIX . $dataObject->getId());
		}

		if ($dataObject->getLabel()) {
			$this->appendChild($this->ownerDocument->createElement('label', $dataObject->getLabel()));
		}

		if ($dataObject->getTitle()) {
			$captionNode = $this->ownerDocument->createElement('caption');
			$this->appendChild($captionNode);
			$captionNode->appendChild($this->ownerDocument->createElement('title', $dataObject->getTitle()));
		}

		$tableNode = $this->ownerDocument->createElement('table');
		$this->appendChild($tableNode);

		foreach ($dataObject->getContent() as $content) {
			$row = new JatsRow($content);
			$tableNode->appendChild($row);
			$row->setContent();
		}
	}
}
